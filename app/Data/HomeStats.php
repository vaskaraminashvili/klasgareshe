<?php

namespace App\Data;

use App\Enums\League;

final readonly class HomeStats
{
    /**
     * @param  list<array{letter: string, on: bool, today: bool}>  $weekDays
     */
    public function __construct(
        public int $streak,
        public int $xp,
        public League $league,
        public string $leagueLabel,
        public int $weekActiveDays,
        public array $weekDays,
    ) {}
}
