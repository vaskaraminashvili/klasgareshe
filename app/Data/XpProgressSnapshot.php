<?php

namespace App\Data;

final readonly class XpProgressSnapshot
{
    /**
     * @param  list<array{label: string, value: int}>  $chartDays
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
    ) {}
}
