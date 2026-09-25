<?php

namespace App\Services;

use App\Data\HomeStats;
use App\Data\LeaderboardEntry;
use App\Data\LeaderboardSnapshot;
use App\Data\ProfileSnapshot;
use App\Data\StreakSnapshot;
use App\Data\XpProgressSnapshot;
use App\Enums\SchoolGrade;
use App\Enums\SchoolSubject;
use App\Enums\XpSource;
use App\Models\User;
use App\Models\UserStat;
use App\Repositories\PlaySessionRepository;
use App\Repositories\UserStatRepository;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class UserStatService
{
    /** @var list<string> */
    private const AVATARS = ['🐻', '🦊', '🐰', '🐼', '🐨', '🦄', '🐢', '🦁', '🐸', '🐯'];

    private const FREEZE_CAP = 3;

    /** @var array<int, int> */
    private const MILESTONE_XP = [3 => 20, 7 => 50, 14 => 100];

    /** @var array<int, int> */
    private const LOGIN_XP = [1 => 10, 2 => 20, 3 => 30, 4 => 40, 5 => 50, 6 => 70, 7 => 100];

    /** @var list<int> */
    private const MILESTONE_DAYS = [3, 7, 14, 30, 100];

    public function __construct(
        private UserStatRepository $stats,
        private LevelCalculator $levels,
        private LeagueSeasonService $leagues,
        private PlaySessionRepository $sessions,
    ) {}

    public function ensureFor(User $user): UserStat
    {
        return $this->stats->firstOrCreateFor($user);
    }

    public function homeSnapshot(User $user): HomeStats
    {
        $stat = $this->ensureFor($user);
        $week = $this->weekActivity($user);

        return new HomeStats(
            streak: $stat->current_streak,
            xp: $stat->xp,
            league: $stat->league,
            leagueLabel: $stat->league->label(),
            weekActiveDays: $week['activeDays'],
            weekDays: $week['days'],
        );
    }

    public function profileSnapshot(User $user, int $weekLessons = 0): ProfileSnapshot
    {
        $stat = $this->ensureFor($user);
        $level = $this->levels->forXp($stat->xp);
        $week = $this->weekActivity($user);
        $grade = $user->grade ?? SchoolGrade::First;

        return new ProfileSnapshot(
            name: $user->name,
            avatar: $this->avatarFor($user),
            age: $user->age,
            gradeLabel: $grade->label(),
            xp: $stat->xp,
            streak: $stat->current_streak,
            rank: $this->stats->rankFor($user),
            level: $level,
            league: $stat->league,
            leagueLabel: $stat->league->label(),
            weekXp: $week['xp'],
            weekActiveDays: $week['activeDays'],
            weekLessons: $weekLessons,
            weekRangeLabel: $week['rangeLabel'],
            weekDays: $week['days'],
        );
    }

    /**
     * @return array{
     *     days: list<array{letter: string, on: bool, today: bool}>,
     *     activeDays: int,
     *     xp: int,
     *     rangeLabel: string
     * }
     */
    private function weekActivity(User $user): array
    {
        $start = now()->startOfWeek(CarbonImmutable::MONDAY);
        $end = $start->addDays(6);
        $from = $start->toDateString();
        $to = $end->toDateString();
        $played = $this->stats->playedDatesBetween($user, $from, $to);
        $playedSet = array_flip($played);
        $today = now()->toDateString();
        $weekDays = [];

        for ($i = 0; $i < 7; $i++) {
            $day = $start->addDays($i);
            $date = $day->toDateString();

            $weekDays[] = [
                'letter' => $this->weekdayLetter($day->dayOfWeekIso),
                'on' => array_key_exists($date, $playedSet),
                'today' => $date === $today,
            ];
        }

        return [
            'days' => $weekDays,
            'activeDays' => count($played),
            'xp' => $this->stats->sumXpBetween($user, $from, $to),
            'rangeLabel' => $start->format('d.m').' — '.$end->format('d.m'),
        ];
    }

    public function xpProgressSnapshot(User $user): XpProgressSnapshot
    {
        $stat = $this->ensureFor($user);
        $level = $this->levels->forXp($stat->xp);
        $end = CarbonImmutable::now()->startOfDay();
        $start = $end->subDays(6);
        $xpMap = $this->stats->xpByDateBetween($user, $start->toDateString(), $end->toDateString());

        $chartDays = [];
        $activeDays = 0;
        $bestDayXp = 0;
        $bestDayLabel = '—';
        $quietDayLabel = '—';
        $quietDayXp = PHP_INT_MAX;

        for ($i = 0; $i < 7; $i++) {
            $day = $start->addDays($i);
            $date = $day->toDateString();
            $value = $xpMap[$date] ?? 0;
            $label = $this->weekdayLetter($day->dayOfWeekIso);
            $chartDays[] = ['label' => $label, 'value' => $value];

            if ($value > 0) {
                $activeDays++;
            }
            if ($value > $bestDayXp) {
                $bestDayXp = $value;
                $bestDayLabel = $label;
            }
            if ($value < $quietDayXp) {
                $quietDayXp = $value;
                $quietDayLabel = $label;
            }
        }

        $weekXp = array_sum(array_column($chartDays, 'value'));
        $todayXp = $xpMap[$end->toDateString()] ?? 0;
        $avgPerDay = $activeDays > 0 ? (int) round($weekXp / $activeDays) : 0;

        $prevEnd = $start->subDay();
        $prevStart = $prevEnd->subDays(6);
        $lastWeekXp = $this->stats->sumXpBetween($user, $prevStart->toDateString(), $prevEnd->toDateString());
        $vsLastWeek = $lastWeekXp > 0
            ? (int) round((($weekXp - $lastWeekXp) / $lastWeekXp) * 100)
            : ($weekXp > 0 ? 100 : 0);

        return new XpProgressSnapshot(
            level: $level,
            todayXp: $todayXp,
            weekXp: $weekXp,
            avgPerDay: $avgPerDay,
            vsLastWeekPercent: $vsLastWeek,
            bestDayXp: $bestDayXp,
            bestDayLabel: $bestDayLabel,
            quietDayLabel: $quietDayLabel,
            activeDays: $activeDays,
            chartDays: $chartDays,
            chartJson: json_encode($chartDays, JSON_THROW_ON_ERROR),
            sourceRows: $this->xpBreakdown($user, $start->toDateString(), $end->toDateString(), 'source'),
            subjectRows: $this->xpBreakdown($user, $start->toDateString(), $end->toDateString(), 'subject'),
        );
    }

    public function leaderboardSnapshot(User $user, int $limit = 50): LeaderboardSnapshot
    {
        $stat = $this->ensureFor($user);
        $level = $this->levels->forXp($stat->xp);
        $yourRank = $this->stats->rankFor($user);
        $total = $this->stats->countLearners();
        $percentileLabel = __('ranking.hidden_percentile');
        $xpToNext = 0;

        if ($yourRank !== null && $total > 0) {
            $percentile = (int) max(1, min(100, ceil(($yourRank / max(1, $total)) * 100)));
            $percentileLabel = __('ranking.top_percentile', ['percent' => $percentile]);

            if ($yourRank > 1) {
                $xpAbove = $this->stats->xpAtRank($yourRank - 1);
                $xpToNext = $xpAbove !== null ? max(0, $xpAbove - $stat->xp + 1) : 0;
            }
        }

        $online = array_flip($this->sessions->onlineUserIds());
        $entries = [];
        $rank = 0;
        foreach ($this->stats->topByXp($limit) as $row) {
            $rank++;
            $owner = $row->user;
            if ($owner === null) {
                continue;
            }
            $entries[] = new LeaderboardEntry(
                rank: $rank,
                userId: $owner->id,
                name: $owner->name,
                xp: $row->xp,
                level: $this->levels->forXp($row->xp)->level,
                streak: $row->current_streak,
                isYou: $owner->id === $user->id,
                avatar: $this->avatarFor($owner),
                country: is_string($owner->country) ? $owner->country : '',
                online: isset($online[$owner->id]),
                nickname: $owner->nickname,
            );
        }

        $podium = [];
        foreach ($entries as $entry) {
            if ($entry->rank <= 3) {
                $podium[] = $entry;
            }
        }

        return new LeaderboardSnapshot(
            totalPlayers: $total,
            yourRank: $yourRank,
            yourXp: $stat->xp,
            yourName: $user->name,
            yourLevel: $level->level,
            yourStreak: $stat->current_streak,
            yourAvatar: $this->avatarFor($user),
            xpToNextRank: $xpToNext,
            percentileLabel: $percentileLabel,
            podium: $podium,
            rows: $entries,
            level: $level,
        );
    }

    public function recordPlay(User $user, int $xp = 0, ?CarbonInterface $playedOn = null, bool $skipEvaluate = false): UserStat
    {
        unset($skipEvaluate);

        return $this->awardXp($user, XpSource::Pack, $xp, $playedOn);
    }

    public function awardXp(
        User $user,
        XpSource $source,
        int $amount,
        ?CarbonInterface $playedOn = null,
        ?SchoolSubject $subject = null,
        ?string $context = null,
        bool $countsAsPlay = true,
        bool $countsTowardLeague = true,
    ): UserStat {
        if ($amount < 0) {
            throw new InvalidArgumentException('XP cannot be negative.');
        }

        $at = CarbonImmutable::parse($playedOn ?? now());
        $on = $at->startOfDay();
        $onDate = $on->toDateString();

        if ($context !== null && $this->stats->hasXpEvent($user, $source, $context)) {
            return $this->ensureFor($user);
        }

        $stat = $this->ensureFor($user);
        $previousStreak = $stat->current_streak;

        if ($countsAsPlay) {
            $this->consumeFreezeForGap($user, $on);
            $stat = $this->ensureFor($user);
        }

        if ($amount > 0) {
            $this->stats->createXpEvent($user, $source, $amount, $subject, $context, $at);
            $this->stats->addDayXp($user, $onDate, $amount);
            if ($countsTowardLeague) {
                $this->leagues->addWeekXp($user, $amount);
            }
        } elseif ($countsAsPlay) {
            $this->stats->addDayXp($user, $onDate, 0);
        }

        $attributes = [
            'xp' => $stat->xp + $amount,
            'coins' => $stat->coins + $amount,
        ];
        $streakChanged = false;

        if ($countsAsPlay) {
            $lastDate = $stat->last_played_on?->toDateString();
            $streak = $stat->current_streak;

            if ($lastDate !== $onDate) {
                $streak = $lastDate === $on->subDay()->toDateString()
                    ? $stat->current_streak + 1
                    : 1;
            }

            $streakChanged = $streak !== $previousStreak;
            $attributes['current_streak'] = $streak;
            $attributes['longest_streak'] = max($stat->longest_streak, $streak);
            $attributes['last_played_on'] = $onDate;
        }

        $updated = $this->stats->update($stat, $attributes);

        if ($streakChanged) {
            $this->grantMilestones($user, (int) $updated->current_streak, $at);
        }

        return $this->ensureFor($user);
    }

    public function awardDailyLogin(User $user, ?CarbonInterface $on = null): int
    {
        $at = CarbonImmutable::parse($on ?? now());
        $date = $at->toDateString();

        if ($this->stats->hasXpEvent($user, XpSource::DailyLogin, $date)) {
            return 0;
        }

        $amount = self::LOGIN_XP[min(7, $this->loginStreakLength($user, $at))];
        $this->awardXp($user, XpSource::DailyLogin, $amount, $at, context: $date, countsAsPlay: false);

        return $amount;
    }

    public function nextDailyLoginXp(User $user, ?CarbonInterface $on = null): int
    {
        $at = CarbonImmutable::parse($on ?? now());
        $date = $at->toDateString();

        if ($this->stats->hasXpEvent($user, XpSource::DailyLogin, $date)) {
            return 0;
        }

        return self::LOGIN_XP[min(7, $this->loginStreakLength($user, $at))];
    }

    /**
     * @return array<int, int>
     */
    public function loginXpTable(): array
    {
        return self::LOGIN_XP;
    }

    public function grantFreeze(User $user): bool
    {
        $stat = $this->ensureFor($user);

        if ($stat->streak_freezes >= self::FREEZE_CAP) {
            return false;
        }

        $this->stats->update($stat, [
            'streak_freezes' => $stat->streak_freezes + 1,
        ]);

        return true;
    }

    public function useFreeze(User $user, ?CarbonInterface $on = null): bool
    {
        $on = CarbonImmutable::parse($on ?? now())->startOfDay();

        return $this->consumeFreezeForGap($user, $on);
    }

    /**
     * @param  list<array{name: string, avatar: string, streak: int, longest: int, isYou: bool, subtitle: string}>  $friendFlames
     */
    public function streakSnapshot(User $user, array $friendFlames = []): StreakSnapshot
    {
        $stat = $this->ensureFor($user);
        $week = $this->weekActivity($user);
        $today = CarbonImmutable::now()->toDateString();
        $playedToday = $stat->last_played_on?->toDateString() === $today;
        $monthStart = CarbonImmutable::now()->startOfMonth();
        $monthEnd = $monthStart->endOfMonth();
        $played = array_flip($this->stats->playedDatesBetween($user, $monthStart->toDateString(), $monthEnd->toDateString()));
        $firstPlayed = $this->stats->firstPlayedOn($user);
        $monthHits = count($played);

        $weekStart = CarbonImmutable::now()->startOfWeek(CarbonImmutable::MONDAY);
        $weekDays = [];
        foreach ($week['days'] as $i => $day) {
            $date = $weekStart->addDays($i);
            $on = $day['on'];
            $weekDays[] = [
                'letter' => $day['letter'],
                'on' => $on,
                'today' => $day['today'],
                'dayNum' => (int) $date->format('j'),
                'ico' => $on ? '🔥' : ($day['today'] ? '🎯' : '·'),
            ];
        }

        $chart = [];
        foreach ($weekDays as $day) {
            $chart[] = ['label' => $day['letter'], 'done' => $day['on']];
        }

        $pad = ((int) $monthStart->dayOfWeekIso) - 1;
        $cells = [];
        for ($i = 0; $i < $pad; $i++) {
            $cells[] = ['empty' => true, 'day' => 0, 'on' => false, 'miss' => false, 'today' => false];
        }

        $daysInMonth = (int) $monthEnd->format('j');
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = $monthStart->addDays($d - 1)->toDateString();
            $isToday = $date === $today;
            $on = array_key_exists($date, $played);
            $past = $date < $today;
            $started = $firstPlayed !== null && $date >= $firstPlayed;
            $cells[] = [
                'empty' => false,
                'day' => $d,
                'on' => $on,
                'miss' => $past && $started && ! $on,
                'today' => $isToday,
            ];
        }

        $milestones = $this->milestoneRows($stat->current_streak);
        $milestonesDone = count(array_filter($milestones, fn (array $row): bool => $row['status'] === 'done' || $row['status'] === 'current'));
        $lastDate = $stat->last_played_on?->toDateString();
        $missedYesterday = $lastDate === CarbonImmutable::now()->subDays(2)->toDateString();

        return new StreakSnapshot(
            current: $stat->current_streak,
            best: $stat->longest_streak,
            weekActiveDays: $week['activeDays'],
            weekDays: $weekDays,
            leagueLabel: $stat->league->label(),
            playedToday: $playedToday,
            grewFromYesterday: $playedToday && $stat->current_streak > 1,
            heroLine: $this->heroLine($stat->current_streak),
            checkInTitle: $playedToday
                ? (string) __('streak.checkin_done')
                : (string) __('streak.checkin_keep'),
            checkInMeta: $playedToday
                ? (string) __('streak.checkin_done_meta')
                : (string) __('streak.checkin_keep_meta'),
            monthLabel: (string) __('streak.month_map', ['month' => __('streak.months.'.CarbonImmutable::now()->month)]),
            monthHits: $monthHits,
            calendarCells: $cells,
            milestones: $milestones,
            milestonesDone: $milestonesDone,
            milestonesTotal: count(self::MILESTONE_DAYS),
            freezes: $stat->streak_freezes,
            freezeCap: self::FREEZE_CAP,
            canUseFreeze: $stat->streak_freezes > 0 && $missedYesterday && ! $playedToday,
            friendFlames: $friendFlames,
            chartJson: json_encode($chart, JSON_THROW_ON_ERROR),
        );
    }

    public function avatarFor(User|int $user): string
    {
        if ($user instanceof User) {
            if (is_string($user->avatar) && $user->avatar !== '') {
                return $user->avatar;
            }
            $id = $user->id;
        } else {
            $id = $user;
        }

        return self::AVATARS[$id % count(self::AVATARS)];
    }

    private function consumeFreezeForGap(User $user, CarbonImmutable $on): bool
    {
        $stat = $this->ensureFor($user);

        if ($stat->streak_freezes < 1) {
            return false;
        }

        $lastDate = $stat->last_played_on?->toDateString();
        $yesterday = $on->subDay()->toDateString();

        if ($lastDate === null || $lastDate === $on->toDateString() || $lastDate === $yesterday) {
            return false;
        }

        if ($lastDate !== $on->subDays(2)->toDateString()) {
            return false;
        }

        $streak = $stat->current_streak + 1;
        $this->stats->addDayXp($user, $yesterday, 0, frozen: true);
        $this->stats->update($stat, [
            'streak_freezes' => $stat->streak_freezes - 1,
            'current_streak' => $streak,
            'longest_streak' => max($stat->longest_streak, $streak),
            'last_played_on' => $yesterday,
        ]);

        return true;
    }

    private function grantMilestones(User $user, int $streak, CarbonInterface $at): void
    {
        foreach (self::MILESTONE_XP as $days => $xp) {
            if ($streak !== $days) {
                continue;
            }

            $this->awardXp(
                $user,
                XpSource::StreakMilestone,
                $xp,
                $at,
                context: (string) $days,
                countsAsPlay: false,
            );
        }
    }

    /**
     * @return list<array{days: int, name: string, hint: string, status: string, chip: string, ico: string, percent: int}>
     */
    private function milestoneRows(int $current): array
    {
        $rows = [];
        $foundCurrent = false;

        foreach (self::MILESTONE_DAYS as $days) {
            $remaining = max(0, $days - $current);
            $percent = (int) min(100, floor(($current / $days) * 100));
            $xp = self::MILESTONE_XP[$days] ?? 0;

            if ($current > $days) {
                $status = 'done';
                $chip = (string) __('streak.earned');
                $hint = $xp > 0
                    ? (string) __('streak.milestone_xp_unlocked', ['xp' => $xp])
                    : (string) __('streak.milestone_badge_unlocked');
            } elseif ($current === $days) {
                $status = 'current';
                $foundCurrent = true;
                $chip = (string) __('streak.today_chip');
                $hint = $xp > 0
                    ? (string) __('streak.milestone_xp_today', ['xp' => $xp])
                    : (string) __('streak.milestone_badge_unlocked');
            } elseif (! $foundCurrent) {
                $status = 'current';
                $foundCurrent = true;
                $chip = $remaining === 0
                    ? (string) __('streak.today_chip')
                    : (string) __('streak.days_short', ['n' => $remaining]);
                $hint = $xp > 0
                    ? (string) __('streak.milestone_xp_left', ['xp' => $xp, 'days' => $remaining])
                    : (string) __('streak.milestone_badge_left', ['days' => $remaining]);
            } else {
                $status = 'upcoming';
                $chip = (string) __('streak.days_short', ['n' => $remaining]);
                $hint = $xp > 0
                    ? (string) __('streak.milestone_xp_left', ['xp' => $xp, 'days' => $remaining])
                    : (string) __('streak.milestone_badge_left', ['days' => $remaining]);
            }

            $rows[] = [
                'days' => $days,
                'name' => (string) __('streak.milestones.'.$days),
                'hint' => $hint,
                'status' => $status,
                'chip' => $chip,
                'ico' => match (true) {
                    $days <= 3 => 'bronze',
                    $days <= 7 => 'silver',
                    $days <= 30 => 'gold',
                    default => 'diamond',
                },
                'percent' => $percent,
            ];
        }

        return $rows;
    }

    private function heroLine(int $streak): string
    {
        return match (true) {
            $streak <= 0 => (string) __('streak.hero_zero'),
            $streak === 1 => (string) __('streak.hero_one'),
            $streak >= 7 => (string) __('streak.hero_fire'),
            default => (string) __('streak.hero_keep'),
        };
    }

    /**
     * @return list<array{label: string, emoji: string, tile: string, amount: int, percent: int}>
     */
    private function xpBreakdown(User $user, string $from, string $to, string $group): array
    {
        $totals = [];

        foreach ($this->stats->xpEventsBetween($user, $from, $to) as $event) {
            if ($group === 'subject') {
                if (! $event->subject instanceof SchoolSubject) {
                    continue;
                }
                $key = $event->subject->value;
                $totals[$key]['amount'] = ($totals[$key]['amount'] ?? 0) + $event->amount;
                $totals[$key]['label'] = $event->subject->label();
                $totals[$key]['emoji'] = $event->subject->emoji();
                $totals[$key]['tile'] = $event->subject->tile();
            } else {
                $key = $event->source->value;
                $totals[$key]['amount'] = ($totals[$key]['amount'] ?? 0) + $event->amount;
                $totals[$key]['label'] = $event->source->label();
                $totals[$key]['emoji'] = $event->source->emoji();
                $totals[$key]['tile'] = $event->source->tile();
            }
        }

        uasort($totals, fn (array $a, array $b): int => $b['amount'] <=> $a['amount']);
        $sum = array_sum(array_column($totals, 'amount'));
        $rows = [];

        foreach ($totals as $row) {
            $rows[] = [
                'label' => $row['label'],
                'emoji' => $row['emoji'],
                'tile' => $row['tile'],
                'amount' => $row['amount'],
                'percent' => $sum > 0 ? (int) round(($row['amount'] / $sum) * 100) : 0,
            ];
        }

        return $rows;
    }

    private function weekdayLetter(int $isoDay): string
    {
        return match ($isoDay) {
            1 => 'M',
            2 => 'T',
            3 => 'W',
            4 => 'T',
            5 => 'F',
            6 => 'S',
            default => 'S',
        };
    }

    public function calendarLetter(int $isoDay): string
    {
        return $this->weekdayLetter($isoDay);
    }

    private function loginStreakLength(User $user, CarbonImmutable $at): int
    {
        $weekStart = $at->startOfWeek(CarbonImmutable::MONDAY);
        $date = $at->toDateString();
        $logged = array_flip($this->stats->eventDatesBetween(
            $user,
            XpSource::DailyLogin,
            $weekStart->toDateString(),
            $date,
        ));

        $consecutive = 1;

        for ($i = 1; $i < 7; $i++) {
            $prev = $at->subDays($i)->toDateString();

            if ($prev < $weekStart->toDateString() || ! isset($logged[$prev])) {
                break;
            }

            $consecutive++;
        }

        return $consecutive;
    }
}
