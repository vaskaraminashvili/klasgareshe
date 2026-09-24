<?php

namespace App\Data;

final readonly class RewardsDashboardSnapshot
{
    /**
     * @param  list<RewardClaimCard>  $claims
     * @param  list<array{letter: string, xp: int, state: string, emoji: string}>  $calendar
     * @param  list<array{title: string, subtitle: string, emoji: string, tile: string, amount: int, spent: bool, when: string}>  $activity
     */
    public function __construct(
        public int $xp,
        public int $coins,
        public int $todayXp,
        public int $weekXp,
        public int $claimCount,
        public int $badgeCount,
        public int $badgeTotal,
        public string $leagueLabel,
        public int $level,
        public int $xpToNext,
        public int $nextLevel,
        public array $claims,
        public array $calendar,
        public int $calendarDay,
        public bool $loginClaimed,
        public int $todayLoginXp,
        public string $today,
        public array $activity,
    ) {}
}
