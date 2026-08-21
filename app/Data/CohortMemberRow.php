<?php

namespace App\Data;

use App\Enums\LeagueOutcome;

final readonly class CohortMemberRow
{
    public function __construct(
        public int $rank,
        public int $userId,
        public string $name,
        public int $weekXp,
        public int $level,
        public int $streak,
        public bool $isYou,
        public string $avatar,
        public string $zone,
        public ?LeagueOutcome $outcome = null,
    ) {}
}
