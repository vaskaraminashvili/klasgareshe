<?php

namespace App\Services;

use App\Data\ExportProgressSnapshot;
use App\Data\FullReportSnapshot;
use App\Data\PeriodFigures;
use App\Data\ReportDayPoint;
use App\Data\ReportHighlight;
use App\Data\ReportSubjectRow;
use App\Data\ReportWeekOption;
use App\Data\WeeklyReportSnapshot;
use App\Enums\DailyGoal;
use App\Enums\ReportScope;
use App\Enums\SchoolSubject;
use App\Models\User;
use App\Notifications\WeeklyReportNotification;
use App\Repositories\BadgeRepository;
use App\Repositories\PlaySessionRepository;
use App\Repositories\UserRepository;
use App\Repositories\UserStatRepository;
use App\Repositories\WeekPlanRepository;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class ProgressReportService
{
    public const WEEKLY_EMAIL_PREF = 'weekly_report';

    public const LOOKBACK_WEEKS = 8;

    public const SEASON_WEEKS = 12;

    public function __construct(
        private UserStatRepository $stats,
        private WeekPlanRepository $plans,
        private WeekPlanService $week,
        private ScreenTimeService $time,
        private PlaySessionRepository $sessions,
        private BadgeRepository $badges,
        private UserStatService $userStats,
        private FriendshipService $friends,
        private UserRepository $users,
    ) {}

    public function currentWeekStart(User $user, ?CarbonInterface $at = null): CarbonImmutable
    {
        $now = $at !== null
            ? CarbonImmutable::instance($at)->setTimezone($this->time->timezone($user))
            : $this->time->nowFor($user);

        return $now->startOfWeek(CarbonImmutable::MONDAY);
    }

    public function previousCompletedWeekStart(User $user, ?CarbonInterface $at = null): CarbonImmutable
    {
        return $this->currentWeekStart($user, $at)->subWeek();
    }

    public function weekSnapshot(User $user, ?string $weekStart = null, ?string $selectedDate = null): WeeklyReportSnapshot
    {
        $start = $this->parseWeekStart($user, $weekStart);
        $end = $start->addDays(6);
        $previousStart = $start->subWeek();
        $figures = $this->figures($user, $start, $end->endOfDay(), $previousStart, $previousStart->addDays(6)->endOfDay());
        $days = $this->dayPoints($user, $start, $selectedDate);
        $selected = $this->selectedDay($days, $selectedDate);
        $mastery = $this->week->subjectMastery($user);
        $packMap = $this->plans->completedCountBySubjectBetween($user, $start->toDateString(), $end->toDateString());
        $packMax = max(1, ...array_values($packMap + [0]));
        $subjects = [];

        foreach ($mastery as $row) {
            $packs = $packMap[$row->subject->value] ?? 0;
            $subjects[] = new ReportSubjectRow(
                subject: $row->subject,
                label: $row->label,
                emoji: $row->emoji,
                tile: $row->tile,
                progressClass: $row->progressClass,
                packs: $packs,
                masteryPercent: $row->percent,
                barPercent: (int) round(($packs / $packMax) * 100),
                nextItemId: $row->nextItemId,
                href: $row->playHref(),
            );
        }

        $activities = [];

        if ($selected !== null) {
            foreach ($this->plans->completedPacksOnDate($user, $selected->date) as $pack) {
                $subject = SchoolSubject::tryFrom($pack['subject']);
                $activities[] = [
                    'title' => $pack['title'],
                    'subtitle' => $subject instanceof SchoolSubject ? $subject->label() : $pack['subject'],
                ];
            }
        }

        $minutesValue = $figures->minutes;
        $minutesLabel = $figures->minutesTracked && $minutesValue !== null
            ? (string) $minutesValue
            : '—';

        return new WeeklyReportSnapshot(
            kidName: $user->name,
            parentEmail: $user->email,
            emailWeekly: $this->wantsWeeklyEmail($user),
            headline: $this->weekHeadline($user->name, $figures),
            subline: $this->weekSubline($figures),
            weekChip: (string) __('reports.week_n', ['n' => $start->isoWeek()]),
            figures: $figures,
            days: $days,
            subjects: $subjects,
            highlights: $this->weekHighlights($user, $figures, $start, $end),
            concerns: $this->weekConcerns($user, $days, $subjects),
            weekOptions: $this->weekOptions($user),
            selectedDay: $selected,
            selectedActivities: $activities,
            totalMinutesLabel: $minutesLabel,
            minutesHint: $this->minutesHint($user, $figures),
        );
    }

    public function fullSnapshot(User $user, ReportScope $scope = ReportScope::Month): FullReportSnapshot
    {
        [$start, $end, $prevStart, $prevEnd] = $this->scopeBounds($user, $scope);
        $figures = $this->figures($user, $start, $end, $prevStart, $prevEnd);
        $mastery = $this->week->subjectMastery($user);
        $packMap = $this->plans->completedCountBySubjectBetween($user, $start->toDateString(), $end->toDateString());
        $packMax = max(1, ...array_values($packMap + [0]));
        $subjects = [];

        foreach ($mastery as $row) {
            $packs = $packMap[$row->subject->value] ?? 0;
            $subjects[] = new ReportSubjectRow(
                subject: $row->subject,
                label: $row->label,
                emoji: $row->emoji,
                tile: $row->tile,
                progressClass: $row->progressClass,
                packs: $packs,
                masteryPercent: $row->percent,
                barPercent: (int) round(($packs / $packMax) * 100),
                nextItemId: $row->nextItemId,
                href: $row->playHref(),
            );
        }

        return new FullReportSnapshot(
            scope: $scope,
            kidName: $user->name,
            rangeEyebrow: (string) __('reports.full_eyebrow', ['name' => $user->name]),
            headline: (string) __('reports.full_headline.'.$scope->value, ['name' => $user->name]),
            subline: $this->fullSubline($figures),
            scopeChip: (string) __('reports.scope_chip.'.$scope->value),
            figures: $figures,
            trend: $this->trend($user, $scope, $start, $end),
            subjects: $subjects,
            kpis: $this->kpis($user, $figures, $start, $end, $prevStart, $prevEnd),
            timeline: $this->timeline($user, $start, $end),
            insights: $this->fullInsights($user, $figures, $subjects),
            paceChip: $this->paceChip($figures),
            daysChip: (string) __('reports.days_chip', ['days' => $figures->activeDays]),
            minutesHint: $this->minutesHint($user, $figures),
        );
    }

    public function exportSnapshot(User $user, ReportScope $scope = ReportScope::Month): ExportProgressSnapshot
    {
        $full = $this->fullSnapshot($user, $scope);

        return new ExportProgressSnapshot(
            scope: $scope,
            kidName: $user->name,
            rangeLabel: $full->figures->rangeLabel,
            coverKicker: (string) __('reports.export_kicker'),
            coverTitle: (string) __('reports.export_story', ['name' => $user->name]),
            coverMeta: (string) __('reports.export_cover_meta', [
                'days' => $full->figures->activeDays,
                'xp' => number_format($full->figures->xp),
                'badges' => $full->figures->badgesEarned,
            ]),
            figures: $full->figures,
            badgeTotal: $this->badges->countCatalog(),
            minutesTracked: $full->figures->minutesTracked,
        );
    }

    /**
     * @return array{title: string, body: string}
     */
    public function homeTip(User $user, ?WeeklyReportSnapshot $week = null): array
    {
        $week ??= $this->weekSnapshot($user);
        $quiet = null;

        foreach ($week->days as $day) {
            if ($quiet === null || $day->xp < $quiet->xp) {
                $quiet = $day;
            }
        }

        if ($week->figures->xp > 0 && $quiet !== null && $quiet->xp === 0) {
            return [
                'title' => (string) __('reports.home_tip_title'),
                'body' => (string) __('reports.home_tip_quiet', [
                    'name' => $user->name,
                    'day' => $quiet->name,
                ]),
            ];
        }

        if ($week->figures->hasPrevious && $week->figures->vsPreviousPercent > 0) {
            return [
                'title' => (string) __('reports.home_tip_title'),
                'body' => (string) __('reports.home_tip_up', [
                    'name' => $user->name,
                    'pct' => $week->figures->vsPreviousPercent,
                ]),
            ];
        }

        return [
            'title' => (string) __('reports.home_tip_title'),
            'body' => (string) __('reports.home_tip_generic', ['name' => $user->name]),
        ];
    }

    public function wantsWeeklyEmail(User $user): bool
    {
        $prefs = $user->notification_preferences;

        if (! is_array($prefs) || ! array_key_exists(self::WEEKLY_EMAIL_PREF, $prefs)) {
            return true;
        }

        return (bool) $prefs[self::WEEKLY_EMAIL_PREF];
    }

    public function setWeeklyEmail(User $user, bool $enabled): User
    {
        $prefs = is_array($user->notification_preferences) ? $user->notification_preferences : [];
        $prefs[self::WEEKLY_EMAIL_PREF] = $enabled;

        return $this->users->update($user, ['notification_preferences' => $prefs]);
    }

    public function emailWeek(User $user, ?string $weekStart = null): void
    {
        $user->notify(new WeeklyReportNotification($this->weekSnapshot($user, $weekStart), $user->id));
    }

    public function figures(
        User $user,
        CarbonImmutable $start,
        CarbonImmutable $end,
        CarbonImmutable $prevStart,
        CarbonImmutable $prevEnd,
    ): PeriodFigures {
        $this->time->closeStale($user);

        $from = $start->toDateString();
        $to = $end->toDateString();
        $xp = $this->stats->sumXpBetween($user, $from, $to);
        $packs = $this->plans->completedCountBetween($user, $from, $to);
        $active = count($this->stats->playedDatesBetween($user, $from, $to));
        $span = max(1, (int) $start->startOfDay()->diffInDays($end->startOfDay()) + 1);
        $tracked = $this->sessions->earliestStartedAt($user) !== null;
        $minutes = $tracked
            ? intdiv($this->time->usedSecondsBetween($user, $start, $end), 60)
            : null;
        $accuracy = $this->accuracyPercent($user, $from, $to);
        $badges = $this->badges->earnedCountBetween($user, $from, $to);
        $goal = ($user->daily_goal ?? DailyGoal::Regular)->minutes() * 5 * $span;
        $prevXp = $this->stats->sumXpBetween($user, $prevStart->toDateString(), $prevEnd->toDateString());
        $hasPrevious = $prevXp > 0 || $this->stats->sumXpBetween($user, '1970-01-01', $prevEnd->toDateString()) > 0;

        return new PeriodFigures(
            from: $from,
            to: $to,
            rangeLabel: $start->format('d.m').' — '.$end->format('d.m'),
            xp: $xp,
            packs: $packs,
            activeDays: $active,
            spanDays: $span,
            minutes: $minutes,
            minutesTracked: $tracked,
            accuracyPercent: $accuracy,
            badgesEarned: $badges,
            goalXp: max(1, $goal),
            goalPercent: (int) min(999, round(($xp / max(1, $goal)) * 100)),
            vsPreviousPercent: $this->deltaPercent($xp, $prevXp),
            hasPrevious: $hasPrevious && $prevXp > 0,
        );
    }

    /**
     * @return list<CarbonImmutable>
     */
    private function scopeBounds(User $user, ReportScope $scope): array
    {
        $now = $this->time->nowFor($user);

        return match ($scope) {
            ReportScope::Week => $this->pair($this->currentWeekStart($user, $now), 6),
            ReportScope::Month => $this->monthPair($now),
            ReportScope::Season => $this->pair($now->startOfWeek(CarbonImmutable::MONDAY)->subWeeks(self::SEASON_WEEKS - 1), (self::SEASON_WEEKS * 7) - 1),
            ReportScope::All => $this->allPair($user, $now),
        };
    }

    /**
     * @return list<CarbonImmutable>
     */
    private function pair(CarbonImmutable $start, int $extraDays): array
    {
        $end = $start->addDays($extraDays)->endOfDay();
        $prevStart = $start->subDays($extraDays + 1);
        $prevEnd = $start->subDay()->endOfDay();

        return [$start->startOfDay(), $end, $prevStart->startOfDay(), $prevEnd];
    }

    /**
     * @return list<CarbonImmutable>
     */
    private function monthPair(CarbonImmutable $now): array
    {
        $start = $now->startOfMonth();
        $end = $now->endOfMonth();
        $prevStart = $start->subMonth()->startOfMonth();
        $prevEnd = $prevStart->endOfMonth();

        return [$start, $end, $prevStart, $prevEnd];
    }

    /**
     * @return list<CarbonImmutable>
     */
    private function allPair(User $user, CarbonImmutable $now): array
    {
        $first = $this->stats->firstPlayedOn($user) ?? $user->created_at?->toDateString() ?? $now->toDateString();
        $start = CarbonImmutable::parse($first, $this->time->timezone($user))->startOfDay();
        $span = max(0, (int) $start->diffInDays($now->startOfDay()));
        $prevEnd = $start->subDay()->endOfDay();
        $prevStart = $start->subDays($span + 1)->startOfDay();

        return [$start, $now->endOfDay(), $prevStart, $prevEnd];
    }

    private function parseWeekStart(User $user, ?string $weekStart): CarbonImmutable
    {
        $current = $this->currentWeekStart($user);

        if ($weekStart === null || $weekStart === '') {
            return $current;
        }

        try {
            $parsed = CarbonImmutable::parse($weekStart, $this->time->timezone($user))->startOfWeek(CarbonImmutable::MONDAY);
        } catch (\Throwable) {
            return $current;
        }

        $oldest = $current->subWeeks(self::LOOKBACK_WEEKS - 1);

        if ($parsed->lt($oldest) || $parsed->gt($current)) {
            return $current;
        }

        return $parsed;
    }

    /**
     * @return list<ReportDayPoint>
     */
    private function dayPoints(User $user, CarbonImmutable $start, ?string $selectedDate): array
    {
        $end = $start->addDays(6);
        $xpMap = $this->stats->xpByDateBetween($user, $start->toDateString(), $end->toDateString());
        $today = $this->time->nowFor($user)->toDateString();
        $tracked = $this->sessions->earliestStartedAt($user) !== null;
        $days = [];

        for ($i = 0; $i < 7; $i++) {
            $day = $start->addDays($i);
            $date = $day->toDateString();
            $now = $this->time->nowFor($user);
            $from = $day->startOfDay();
            $to = $day->isSameDay($now) ? $now : $day->endOfDay();
            $iso = $day->dayOfWeekIso;
            $minutes = $tracked ? intdiv($this->time->usedSecondsBetween($user, $from, $to), 60) : null;
            $packs = $this->plans->completedCountBetween($user, $date, $date);
            $top = $this->topSubjectLabel($user, $date);

            $days[] = new ReportDayPoint(
                date: $date,
                letter: (string) __('screen-time.dow_'.$iso),
                name: (string) __('home.weekdays.'.$iso),
                xp: $xpMap[$date] ?? 0,
                minutes: $minutes,
                packs: $packs,
                topSubject: $top,
                today: $date === $today,
                selected: $selectedDate === $date,
            );
        }

        return $days;
    }

    /**
     * @param  list<ReportDayPoint>  $days
     */
    private function selectedDay(array $days, ?string $selectedDate): ?ReportDayPoint
    {
        foreach ($days as $day) {
            if ($selectedDate !== null && $day->date === $selectedDate) {
                return $day;
            }
        }

        return null;
    }

    private function topSubjectLabel(User $user, string $date): string
    {
        $counts = [];

        foreach ($this->plans->completedPacksOnDate($user, $date) as $pack) {
            $counts[$pack['subject']] = ($counts[$pack['subject']] ?? 0) + 1;
        }

        if ($counts === []) {
            return '';
        }

        arsort($counts);
        $key = (string) array_key_first($counts);
        $subject = SchoolSubject::tryFrom($key);

        return $subject instanceof SchoolSubject ? $subject->label() : '';
    }

    /**
     * @return list<ReportWeekOption>
     */
    private function weekOptions(User $user): array
    {
        $current = $this->currentWeekStart($user);
        $options = [];

        for ($i = 0; $i < self::LOOKBACK_WEEKS; $i++) {
            $start = $current->subWeeks($i);
            $end = $start->addDays(6);
            $from = $start->toDateString();
            $to = $end->toDateString();

            $options[] = new ReportWeekOption(
                start: $from,
                label: (string) __('reports.week_n', ['n' => $start->isoWeek()]),
                rangeLabel: $start->format('d.m').' — '.$end->format('d.m'),
                xp: $this->stats->sumXpBetween($user, $from, $to),
                activeDays: count($this->stats->playedDatesBetween($user, $from, $to)),
                current: $i === 0,
            );
        }

        return $options;
    }

    /**
     * @return list<ReportHighlight>
     */
    private function weekHighlights(User $user, PeriodFigures $figures, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $highlights = [];
        $stat = $this->userStats->ensureFor($user);

        if ($figures->activeDays === 7) {
            $highlights[] = new ReportHighlight(
                key: 'streak',
                title: (string) __('reports.hl_streak_title'),
                subtitle: (string) __('reports.hl_streak_sub'),
                body: (string) __('reports.hl_streak_body', ['name' => $user->name, 'streak' => $stat->current_streak]),
                emoji: '🔥',
                tile: 'tile-sun',
                chip: (string) __('reports.hl_streak_chip'),
                ctaHref: route('xp-progress'),
                ctaLabel: (string) __('reports.hl_streak_cta'),
            );
        }

        $earned = $this->badges->earnedBetween($user, $start->toDateString(), $end->toDateString());

        if ($earned->isNotEmpty()) {
            $names = [];

            foreach ($earned as $row) {
                $badge = $row->badge;

                if ($badge === null) {
                    continue;
                }

                $names[] = (string) __('badges.items.'.$badge->slug.'.name');
            }

            $highlights[] = new ReportHighlight(
                key: 'badges',
                title: (string) __('reports.hl_badges_title', ['count' => $earned->count()]),
                subtitle: implode(' · ', array_slice($names, 0, 2)),
                body: (string) __('reports.hl_badges_body', ['names' => implode(', ', $names)]),
                emoji: '🏅',
                tile: 'tile-mint',
                chip: (string) __('reports.hl_badges_chip'),
                ctaHref: route('badges'),
                ctaLabel: (string) __('reports.hl_badges_cta'),
            );
        }

        if ($figures->hasPrevious && $figures->vsPreviousPercent > 0) {
            $highlights[] = new ReportHighlight(
                key: 'xp',
                title: (string) __('reports.hl_xp_title'),
                subtitle: (string) __('reports.hl_xp_sub', ['pct' => $figures->vsPreviousPercent]),
                body: (string) __('reports.hl_xp_body', ['name' => $user->name, 'pct' => $figures->vsPreviousPercent]),
                emoji: '⭐',
                tile: 'tile-violet',
                chip: '+'.$figures->vsPreviousPercent.'%',
                ctaHref: route('xp-progress'),
                ctaLabel: (string) __('reports.hl_xp_cta'),
            );
        }

        return $highlights;
    }

    /**
     * @param  list<ReportDayPoint>  $days
     * @param  list<ReportSubjectRow>  $subjects
     * @return list<ReportHighlight>
     */
    private function weekConcerns(User $user, array $days, array $subjects): array
    {
        $concerns = [];
        $quiet = null;

        foreach ($days as $day) {
            if ($quiet === null || $day->xp < $quiet->xp) {
                $quiet = $day;
            }
        }

        $anyXp = false;

        foreach ($days as $day) {
            if ($day->xp > 0) {
                $anyXp = true;
                break;
            }
        }

        if ($anyXp && $quiet !== null && $quiet->xp === 0) {
            $concerns[] = new ReportHighlight(
                key: 'quiet-day',
                title: (string) __('reports.watch_quiet_title', ['day' => $quiet->name]),
                subtitle: (string) __('reports.watch_quiet_sub'),
                body: (string) __('reports.watch_quiet_body', ['day' => $quiet->name]),
                emoji: '🦉',
                tile: 'tile-sun',
                chip: (string) __('reports.watch_remind'),
                ctaHref: route('screen-time'),
                ctaLabel: (string) __('reports.watch_remind'),
            );
        }

        $weak = null;

        foreach ($subjects as $row) {
            if ($weak === null || $row->packs < $weak->packs) {
                $weak = $row;
            }
        }

        if ($weak !== null && $weak->packs === 0 && $anyXp) {
            $concerns[] = new ReportHighlight(
                key: 'subject',
                title: (string) __('reports.watch_subject_title', ['subject' => $weak->label]),
                subtitle: (string) __('reports.watch_subject_sub'),
                body: (string) __('reports.watch_subject_body', ['subject' => $weak->label]),
                emoji: '💡',
                tile: 'tile-coral',
                chip: (string) __('reports.watch_suggest'),
                ctaHref: $weak->href,
                ctaLabel: (string) __('reports.watch_suggest'),
            );
        }

        return $concerns;
    }

    /**
     * @return list<array{label: string, value: int, height: int}>
     */
    private function trend(User $user, ReportScope $scope, CarbonImmutable $start, CarbonImmutable $end): array
    {
        if ($scope === ReportScope::Week) {
            $points = [];
            $xpMap = $this->stats->xpByDateBetween($user, $start->toDateString(), $end->toDateString());
            $max = 1;

            for ($i = 0; $i < 7; $i++) {
                $day = $start->addDays($i);
                $value = $xpMap[$day->toDateString()] ?? 0;
                $max = max($max, $value);
                $points[] = [
                    'label' => (string) __('screen-time.dow_'.$day->dayOfWeekIso),
                    'value' => $value,
                    'height' => 0,
                ];
            }

            foreach ($points as $i => $point) {
                $points[$i]['height'] = (int) round(($point['value'] / $max) * 100);
            }

            return $points;
        }

        $cursor = $start->startOfWeek(CarbonImmutable::MONDAY);
        $last = $end->startOfWeek(CarbonImmutable::MONDAY);
        $weeks = [];

        while ($cursor->lte($last)) {
            $weekEnd = $cursor->addDays(6);
            $value = $this->stats->sumXpBetween($user, $cursor->toDateString(), $weekEnd->toDateString());
            $weeks[] = [
                'label' => (string) __('reports.week_n', ['n' => $cursor->isoWeek()]),
                'value' => $value,
                'height' => 0,
            ];
            $cursor = $cursor->addWeek();
        }

        $max = max(1, ...array_column($weeks, 'value') ?: [1]);

        foreach ($weeks as $i => $week) {
            $weeks[$i]['height'] = (int) round(($week['value'] / $max) * 100);
        }

        return $weeks;
    }

    /**
     * @return list<array{key: string, name: string, value: string, unit: string, delta: string, emoji: string, tile: string, body: string}>
     */
    private function kpis(
        User $user,
        PeriodFigures $figures,
        CarbonImmutable $start,
        CarbonImmutable $end,
        CarbonImmutable $prevStart,
        CarbonImmutable $prevEnd,
    ): array {
        $stat = $this->userStats->ensureFor($user);
        $sessionCount = $this->sessions->countOverlapping($user, $start, $end);
        $avg = ($figures->minutesTracked && $sessionCount > 0 && $figures->minutes !== null)
            ? (int) round($figures->minutes / $sessionCount)
            : null;
        $prevMinutes = $this->sessions->earliestStartedAt($user) !== null
            ? intdiv($this->time->usedSecondsBetween($user, $prevStart, $prevEnd), 60)
            : null;
        $prevAvg = null;

        if ($prevMinutes !== null) {
            $prevSessions = $this->sessions->countOverlapping($user, $prevStart, $prevEnd);
            $prevAvg = $prevSessions > 0 ? (int) round($prevMinutes / $prevSessions) : 0;
        }

        $friends = $this->friends->friendsLeaderboard($user);
        $badgeTotal = $this->badges->countCatalog();
        $badgeNow = $this->badges->earnedCount($user);
        $badgePrevRange = $this->badges->earnedCountBetween($user, $prevStart->toDateString(), $prevEnd->toDateString());

        return [
            [
                'key' => 'streak',
                'name' => (string) __('reports.kpi_streak'),
                'value' => (string) $stat->longest_streak,
                'unit' => (string) __('reports.kpi_days'),
                'delta' => (string) __('reports.kpi_current_streak', ['n' => $stat->current_streak]),
                'emoji' => '🔥',
                'tile' => 'tile-sun',
                'body' => (string) __('reports.kpi_streak_body', ['name' => $user->name, 'n' => $stat->longest_streak]),
            ],
            [
                'key' => 'session',
                'name' => (string) __('reports.kpi_session'),
                'value' => $avg !== null ? (string) $avg : '—',
                'unit' => (string) __('reports.kpi_min'),
                'delta' => $avg !== null && $prevAvg !== null
                    ? (string) __('reports.kpi_vs', ['n' => $this->signed($avg - $prevAvg)])
                    : (string) __('reports.minutes_untracked'),
                'emoji' => '⏱️',
                'tile' => 'tile-mint',
                'body' => (string) __('reports.kpi_session_body'),
            ],
            [
                'key' => 'badges',
                'name' => (string) __('reports.kpi_badges'),
                'value' => (string) $badgeNow,
                'unit' => '/ '.$badgeTotal,
                'delta' => (string) __('reports.kpi_badges_delta', ['n' => $figures->badgesEarned]),
                'emoji' => '🏅',
                'tile' => 'tile-violet',
                'body' => (string) __('reports.kpi_badges_body', ['n' => $badgePrevRange]),
            ],
            [
                'key' => 'friends',
                'name' => (string) __('reports.kpi_friends'),
                'value' => $friends->friendCount > 0 ? '#'.$friends->yourRank : '—',
                'unit' => $friends->friendCount > 0
                    ? (string) __('reports.kpi_of', ['n' => $friends->friendCount + 1])
                    : (string) __('reports.kpi_no_friends'),
                'delta' => (string) __('reports.kpi_friends_delta', ['n' => $friends->beatingCount]),
                'emoji' => '👫',
                'tile' => 'tile-coral',
                'body' => (string) __('reports.kpi_friends_body'),
            ],
        ];
    }

    /**
     * @return list<ReportHighlight>
     */
    private function timeline(User $user, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $events = [];

        foreach ($this->badges->earnedBetween($user, $start->toDateString(), $end->toDateString()) as $row) {
            $badge = $row->badge;

            if ($badge === null || $row->unlocked_at === null) {
                continue;
            }

            $events[] = new ReportHighlight(
                key: $badge->slug,
                title: (string) __('badges.items.'.$badge->slug.'.name'),
                subtitle: $row->unlocked_at->format('d.m').' · '.$this->ago($row->unlocked_at),
                body: (string) __('badges.items.'.$badge->slug.'.blurb'),
                emoji: $badge->emoji,
                tile: 'tile-mint',
                chip: (string) __('badges.rarity.'.$badge->rarity->value),
                ctaHref: route('badges'),
                ctaLabel: (string) __('reports.see_all'),
            );
        }

        return $events;
    }

    /**
     * @param  list<ReportSubjectRow>  $subjects
     * @return list<ReportHighlight>
     */
    private function fullInsights(User $user, PeriodFigures $figures, array $subjects): array
    {
        $insights = [];

        if ($figures->hasPrevious && $figures->vsPreviousPercent !== 0) {
            $insights[] = new ReportHighlight(
                key: 'trend',
                title: $figures->vsPreviousPercent > 0
                    ? (string) __('reports.insight_up_title', ['name' => $user->name])
                    : (string) __('reports.insight_down_title'),
                subtitle: '',
                body: (string) __('reports.insight_vs_body', ['pct' => abs($figures->vsPreviousPercent)]),
                emoji: $figures->vsPreviousPercent > 0 ? '📈' : '🦉',
                tile: 'tile-mint',
                chip: $this->signed($figures->vsPreviousPercent).'%',
                ctaHref: null,
                ctaLabel: '',
            );
        }

        $weak = null;

        foreach ($subjects as $row) {
            if ($weak === null || $row->packs < $weak->packs) {
                $weak = $row;
            }
        }

        if ($weak !== null && $weak->packs === 0 && $figures->packs > 0) {
            $insights[] = new ReportHighlight(
                key: 'subject',
                title: (string) __('reports.watch_subject_title', ['subject' => $weak->label]),
                subtitle: '',
                body: (string) __('reports.watch_subject_body', ['subject' => $weak->label]),
                emoji: '💡',
                tile: 'tile-coral',
                chip: (string) __('reports.insight_watch'),
                ctaHref: null,
                ctaLabel: '',
            );
        }

        if ($figures->accuracyPercent !== null) {
            $insights[] = new ReportHighlight(
                key: 'accuracy',
                title: (string) __('reports.insight_accuracy_title'),
                subtitle: '',
                body: (string) __('reports.insight_accuracy_body', ['pct' => $figures->accuracyPercent]),
                emoji: '📈',
                tile: 'tile-sky',
                chip: $figures->accuracyPercent.'%',
                ctaHref: null,
                ctaLabel: '',
            );
        }

        if ($insights === []) {
            $insights[] = new ReportHighlight(
                key: 'generic',
                title: (string) __('reports.insight_generic_title'),
                subtitle: '',
                body: (string) __('reports.home_tip_generic', ['name' => $user->name]),
                emoji: '🦉',
                tile: 'tile-sun',
                chip: (string) __('reports.insight_watch'),
                ctaHref: null,
                ctaLabel: '',
            );
        }

        return $insights;
    }

    private function weekHeadline(string $name, PeriodFigures $figures): string
    {
        if ($figures->xp === 0) {
            return (string) __('reports.headline_quiet', ['name' => $name]);
        }

        if ($figures->hasPrevious && $figures->vsPreviousPercent > 0) {
            return (string) __('reports.headline_great', ['name' => $name]);
        }

        return (string) __('reports.headline_ok', ['name' => $name]);
    }

    private function weekSubline(PeriodFigures $figures): string
    {
        if (! $figures->hasPrevious) {
            return (string) __('reports.subline_first', ['days' => $figures->activeDays]);
        }

        return (string) __('reports.subline_vs', [
            'sign' => $figures->vsPreviousPercent >= 0 ? '+' : '',
            'pct' => $figures->vsPreviousPercent,
            'days' => $figures->activeDays,
        ]);
    }

    private function fullSubline(PeriodFigures $figures): string
    {
        if (! $figures->hasPrevious) {
            return (string) __('reports.full_sub_first', ['days' => $figures->activeDays]);
        }

        return (string) __('reports.full_sub_vs', [
            'days' => $figures->activeDays,
            'sign' => $figures->vsPreviousPercent >= 0 ? '+' : '',
            'pct' => $figures->vsPreviousPercent,
        ]);
    }

    private function paceChip(PeriodFigures $figures): string
    {
        if ($figures->goalPercent >= 100) {
            return (string) __('reports.pace_ahead');
        }

        if ($figures->goalPercent >= 60) {
            return (string) __('reports.pace_on');
        }

        return (string) __('reports.pace_behind');
    }

    private function minutesHint(User $user, PeriodFigures $figures): string
    {
        if ($figures->minutesTracked) {
            return (string) __('reports.minutes_from', ['date' => $this->sessions->earliestStartedAt($user)?->format('d.m.Y') ?? '']);
        }

        return (string) __('reports.minutes_untracked');
    }

    private function accuracyPercent(User $user, string $from, string $to): ?int
    {
        $totals = $this->plans->accuracyTotalsBetween($user, $from, $to);

        if ($totals['asked'] < 1) {
            return null;
        }

        return (int) round(($totals['correct'] / $totals['asked']) * 100);
    }

    private function deltaPercent(int $current, int $previous): int
    {
        if ($previous > 0) {
            return (int) round((($current - $previous) / $previous) * 100);
        }

        return $current > 0 ? 100 : 0;
    }

    private function signed(int $value): string
    {
        return ($value > 0 ? '+' : '').$value;
    }

    private function ago(CarbonInterface $at): string
    {
        $days = (int) CarbonImmutable::instance($at)->startOfDay()->diffInDays(now()->startOfDay());

        if ($days === 0) {
            return (string) __('badges.today');
        }

        if ($days === 1) {
            return (string) __('badges.yesterday');
        }

        return (string) __('badges.days_ago', ['days' => $days]);
    }
}
