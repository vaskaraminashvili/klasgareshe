<?php

namespace App\Data;

use App\Enums\League;

final readonly class ProfileSnapshot
{
    /**
     * @param  list<array{letter: string, on: bool, today: bool}>  $weekDays
     */
    public function __construct(
        public string $name,
        public string $avatar,
        public ?int $age,
        public string $gradeLabel,
        public int $xp,
        public int $streak,
        public int $rank,
        public LevelProgress $level,
        public League $league,
        public string $leagueLabel,
        public int $weekXp,
        public int $weekActiveDays,
        public int $weekLessons,
        public string $weekRangeLabel,
        public array $weekDays,
    ) {}
}
