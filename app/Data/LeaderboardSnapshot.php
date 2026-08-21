<?php

namespace App\Data;

final readonly class LeaderboardSnapshot
{
    /**
     * @param  list<LeaderboardEntry>  $podium
     * @param  list<LeaderboardEntry>  $rows
     */
    public function __construct(
        public int $totalPlayers,
        public int $yourRank,
        public int $yourXp,
        public string $yourName,
        public int $yourLevel,
        public int $yourStreak,
        public string $yourAvatar,
        public int $xpToNextRank,
        public string $percentileLabel,
        public array $podium,
        public array $rows,
        public LevelProgress $level,
    ) {}
}
