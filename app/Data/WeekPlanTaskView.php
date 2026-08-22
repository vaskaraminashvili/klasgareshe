<?php

namespace App\Data;

use App\Enums\SchoolSubject;

final readonly class WeekPlanTaskView
{
    public function __construct(
        public ?int $id,
        public SchoolSubject $subject,
        public string $title,
        public string $subtitle,
        public bool $completed,
        public bool $playable,
        public string $emoji,
        public string $tile,
        public string $inkClass,
    ) {}
}
