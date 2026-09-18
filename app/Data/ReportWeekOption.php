<?php

namespace App\Data;

final readonly class ReportWeekOption
{
    public function __construct(
        public string $start,
        public string $label,
        public string $rangeLabel,
        public int $xp,
        public int $activeDays,
        public bool $current,
    ) {}
}
