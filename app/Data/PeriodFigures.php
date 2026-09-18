<?php

namespace App\Data;

final readonly class PeriodFigures
{
    public function __construct(
        public string $from,
        public string $to,
        public string $rangeLabel,
        public int $xp,
        public int $packs,
        public int $activeDays,
        public int $spanDays,
        public ?int $minutes,
        public bool $minutesTracked,
        public ?int $accuracyPercent,
        public int $badgesEarned,
        public int $goalXp,
        public int $goalPercent,
        public int $vsPreviousPercent,
        public bool $hasPrevious,
    ) {}
}
