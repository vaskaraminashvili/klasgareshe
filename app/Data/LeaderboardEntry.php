<?php

namespace App\Data;

final readonly class LeaderboardEntry
{
    public function __construct(
        public int $rank,
        public int $userId,
        public string $name,
        public int $xp,
        public int $level,
        public int $streak,
        public bool $isYou,
        public string $avatar,
    ) {}
}
