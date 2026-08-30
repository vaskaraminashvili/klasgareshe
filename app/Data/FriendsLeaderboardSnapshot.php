<?php

namespace App\Data;

final readonly class FriendsLeaderboardSnapshot
{
    /**
     * @param  list<LeaderboardEntry>  $podium
     * @param  list<LeaderboardEntry>  $rows
     */
    public function __construct(
        public int $friendCount,
        public int $onlineCount,
        public int $beatingCount,
        public int $yourRank,
        public int $yourXp,
        public string $yourName,
        public int $yourLevel,
        public int $yourStreak,
        public string $yourAvatar,
        public int $xpBehindLeader,
        public string $xpBehindName,
        public int $xpAheadNext,
        public string $xpAheadName,
        public array $podium,
        public array $rows,
    ) {}
}
