<?php

namespace App\Data;

final readonly class XpProgressSnapshot
{
    /**
     * @param  list<array{label: string, value: int}>  $chartDays
     * @param  list<array{label: string, emoji: string, tile: string, amount: int, percent: int}>  $sourceRows
     * @param  list<array{label: string, emoji: string, tile: string, amount: int, percent: int}>  $subjectRows
     */
    public function __construct(
        public LevelProgress $level,
        public int $todayXp,
        public int $weekXp,
        public int $avgPerDay,
        public int $vsLastWeekPercent,
        public int $bestDayXp,
        public string $bestDayLabel,
        public string $quietDayLabel,
        public int $activeDays,
        public array $chartDays,
        public string $chartJson,
        public array $sourceRows = [],
        public array $subjectRows = [],
    ) {}
}
