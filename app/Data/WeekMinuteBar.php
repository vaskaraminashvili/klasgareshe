<?php

namespace App\Data;

final readonly class WeekMinuteBar
{
    public function __construct(
        public string $letter,
        public string $date,
        public int $minutes,
        public int $percent,
        public bool $today,
    ) {}
}
