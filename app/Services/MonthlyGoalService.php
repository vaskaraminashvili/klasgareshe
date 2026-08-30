<?php

namespace App\Services;

use App\Data\MonthlyGoalRow;
use App\Data\MonthlyGoalsSnapshot;
use App\Enums\DailyGoal;
use App\Models\User;
use App\Repositories\BadgeRepository;
use App\Repositories\UserStatRepository;
use App\Repositories\WeekPlanRepository;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class MonthlyGoalService
{
    private const LESSONS_TARGET = 12;

    private const STREAK_TARGET = 20;

    private const BADGES_TARGET = 3;

    private const GOALS_TOTAL = 4;

    public function __construct(
        private UserStatRepository $stats,
        private WeekPlanRepository $plans,
        private BadgeRepository $badges,
    ) {}

    public function snapshot(User $user, ?CarbonInterface $month = null): MonthlyGoalsSnapshot
    {
        $anchor = CarbonImmutable::instance($month ?? now())->startOfMonth();
        $monthStart = $anchor->startOfMonth();
        $monthEnd = $anchor->endOfMonth();
        $today = CarbonImmutable::now()->startOfDay();
        $isCurrentMonth = $today->isSameMonth($monthStart);
        $elapsedEnd = $isCurrentMonth
            ? $today->min($monthEnd->startOfDay())
            : $monthEnd->startOfDay();
        $dayOfMonth = $elapsedEnd->day;
        $daysInMonth = $monthStart->daysInMonth;
        $daysLeft = $isCurrentMonth
            ? (int) max(0, $monthEnd->startOfDay()->diffInDays($today))
            : 0;

        $from = $monthStart->toDateString();
        $to = $monthEnd->toDateString();

        $xpEarned = $this->stats->sumXpBetween($user, $from, $to);
        $lessons = $this->plans->completedCountBetween($user, $from, $to);
        $badges = $this->badges->earnedCountBetween($user, $from, $to);
        $streak = $this->stats->firstOrCreateFor($user)->current_streak;

        $dailyGoal = $user->daily_goal ?? DailyGoal::Regular;
        $xpTarget = max(1, $dailyGoal->minutes() * 5 * $daysInMonth);

        $goals = [
            $this->goalRow(
                key: 'lessons',
                name: (string) __('monthly-goals.goal_lessons', ['target' => self::LESSONS_TARGET]),
                emoji: '📚',
                tile: 'tile-mint',
                progressClass: 'progress-mint',
                chipClass: 'chip-mint',
                current: $lessons,
                target: self::LESSONS_TARGET,
                unit: (string) __('monthly-goals.unit_lessons'),
            ),
            $this->goalRow(
                key: 'xp',
                name: (string) __('monthly-goals.goal_xp', ['target' => number_format($xpTarget)]),
                emoji: '⭐',
                tile: 'tile-sun',
                progressClass: 'progress-sun',
                chipClass: 'chip-sun',
                current: $xpEarned,
                target: $xpTarget,
                unit: (string) __('monthly-goals.unit_xp'),
            ),
            $this->goalRow(
                key: 'streak',
                name: (string) __('monthly-goals.goal_streak', ['target' => self::STREAK_TARGET]),
                emoji: '🔥',
                tile: 'tile-coral',
                progressClass: 'progress-coral',
                chipClass: 'chip-coral',
                current: $streak,
                target: self::STREAK_TARGET,
                unit: (string) __('monthly-goals.unit_days'),
            ),
            $this->goalRow(
                key: 'badges',
                name: (string) __('monthly-goals.goal_badges', ['target' => self::BADGES_TARGET]),
                emoji: '🏅',
                tile: 'tile-sky',
                progressClass: '',
                chipClass: 'chip-primary',
                current: $badges,
                target: self::BADGES_TARGET,
                unit: (string) __('monthly-goals.unit_badges'),
            ),
        ];

        $goalsHit = count(array_filter($goals, fn (MonthlyGoalRow $g): bool => $g->done));
        $avgPct = (int) round(array_sum(array_map(fn (MonthlyGoalRow $g): int => $g->percent, $goals)) / max(1, count($goals)));
        $monthPct = $avgPct;
        $xpPerDay = $dayOfMonth > 0 ? (int) round($xpEarned / $dayOfMonth) : 0;

        $prevStart = $monthStart->subMonth()->startOfMonth();
        $prevEnd = $prevStart->endOfMonth();
        $lastMonthXp = $this->stats->sumXpBetween($user, $prevStart->toDateString(), $prevEnd->toDateString());
        $vsLastMonth = $lastMonthXp > 0
            ? (int) round((($xpEarned - $lastMonthXp) / $lastMonthXp) * 100)
            : ($xpEarned > 0 ? 100 : 0);

        $weeklyBars = $this->weeklyBars($user, $monthStart, $monthEnd);
        $weeklyXpTotal = array_sum(array_column($weeklyBars, 'value'));

        $expectedPace = (int) round(($dayOfMonth / max(1, $daysInMonth)) * 100);
        $onTrack = $monthPct >= $expectedPace;
        $monthLabel = $this->monthName($monthStart);

        $projectedXp = $xpPerDay > 0 && $daysInMonth > 0
            ? (int) round($xpPerDay * $daysInMonth)
            : $xpEarned;

        if ($onTrack) {
            $insightTitle = (string) __('monthly-goals.insight_ahead_title');
            $insightBody = (string) __('monthly-goals.insight_ahead_body', [
                'projected' => number_format($projectedXp),
                'target' => number_format($xpTarget),
            ]);
            $insightChip = (string) __('monthly-goals.on_track');
        } else {
            $insightTitle = (string) __('monthly-goals.insight_behind_title');
            $insightBody = (string) __('monthly-goals.insight_behind_body', [
                'projected' => number_format($projectedXp),
                'target' => number_format($xpTarget),
            ]);
            $insightChip = (string) __('monthly-goals.behind');
        }

        return new MonthlyGoalsSnapshot(
            monthLabel: $monthLabel,
            monthPct: $monthPct,
            xpEarned: $xpEarned,
            goalsHit: $goalsHit,
            goalsTotal: self::GOALS_TOTAL,
            xpPerDay: $xpPerDay,
            daysLeft: $daysLeft,
            vsLastMonthPercent: $vsLastMonth,
            insightTitle: $insightTitle,
            insightBody: $insightBody,
            insightChip: $insightChip,
            weeklyXpTotal: $weeklyXpTotal,
            weeklyBars: $weeklyBars,
            goals: $goals,
            rewardPercent: (int) round(($goalsHit / self::GOALS_TOTAL) * 100),
            rewardDone: $goalsHit,
            rewardTotal: self::GOALS_TOTAL,
        );
    }

    private function goalRow(
        string $key,
        string $name,
        string $emoji,
        string $tile,
        string $progressClass,
        string $chipClass,
        int $current,
        int $target,
        string $unit,
    ): MonthlyGoalRow {
        $percent = $target > 0 ? (int) min(100, round(($current / $target) * 100)) : 0;
        $done = $current >= $target;
        $remaining = max(0, $target - $current);

        $statusLabel = match (true) {
            $done => (string) __('monthly-goals.status_done'),
            $percent >= 75 => (string) __('monthly-goals.status_on_track'),
            $percent >= 40 => (string) __('monthly-goals.status_on_pace'),
            $key === 'badges' && $remaining === 1 => (string) __('monthly-goals.status_one_badge'),
            $key === 'streak' => (string) __('monthly-goals.status_needs_days', ['days' => $remaining]),
            default => (string) __('monthly-goals.status_keep_going'),
        };

        return new MonthlyGoalRow(
            key: $key,
            name: $name,
            emoji: $emoji,
            tile: $tile,
            progressClass: $progressClass,
            chipClass: $chipClass,
            current: $current,
            target: $target,
            unit: $unit,
            percent: $percent,
            statusLabel: $statusLabel,
            done: $done,
        );
    }

    /**
     * @return list<array{label: string, value: int, height: int}>
     */
    private function weeklyBars(User $user, CarbonImmutable $monthStart, CarbonImmutable $monthEnd): array
    {
        $xpMap = $this->stats->xpByDateBetween(
            $user,
            $monthStart->toDateString(),
            $monthEnd->toDateString(),
        );

        $weeks = [0, 0, 0, 0];

        foreach ($xpMap as $date => $xp) {
            $day = CarbonImmutable::parse($date)->day;
            $index = min(3, intdiv($day - 1, 7));
            $weeks[$index] += $xp;
        }

        $max = max(1, ...$weeks);
        $bars = [];

        foreach ($weeks as $i => $value) {
            $bars[] = [
                'label' => (string) __('monthly-goals.week_n', ['n' => $i + 1]),
                'value' => $value,
                'height' => (int) round(($value / $max) * 100),
            ];
        }

        return $bars;
    }

    private function monthName(CarbonImmutable $month): string
    {
        $key = strtolower($month->format('F'));

        return (string) __('monthly-goals.months.'.$key);
    }
}
