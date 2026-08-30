<?php

namespace App\Data;

final readonly class MonthlyGoalsSnapshot
{
    /**
     * @param  list<array{label: string, value: int, height: int}>  $weeklyBars
     * @param  list<MonthlyGoalRow>  $goals
     */
    public function __construct(
        public string $monthLabel,
        public int $monthPct,
        public int $xpEarned,
        public int $goalsHit,
        public int $goalsTotal,
        public int $xpPerDay,
        public int $daysLeft,
        public int $vsLastMonthPercent,
        public string $insightTitle,
        public string $insightBody,
        public string $insightChip,
        public int $weeklyXpTotal,
        public array $weeklyBars,
        public array $goals,
        public int $rewardPercent,
        public int $rewardDone,
        public int $rewardTotal,
    ) {}
}
