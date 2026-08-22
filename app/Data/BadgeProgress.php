<?php

namespace App\Data;

final readonly class BadgeProgress
{
    public function __construct(
        public int $current,
        public int $target,
        public int $percent,
        public bool $met,
    ) {}
}
