<?php

namespace App\Data;

final readonly class ReportDayPoint
{
    public function __construct(
        public string $date,
        public string $letter,
        public string $name,
        public int $xp,
        public ?int $minutes,
        public int $packs,
        public string $topSubject,
        public bool $today,
        public bool $selected,
    ) {}
}
