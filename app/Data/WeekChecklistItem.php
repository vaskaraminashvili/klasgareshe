<?php

namespace App\Data;

use App\Enums\SchoolSubject;

final readonly class WeekChecklistItem
{
    public function __construct(
        public int $id,
        public int $weekday,
        public SchoolSubject $subject,
        public string $title,
        public bool $completed,
        public bool $playable,
        public bool $current,
        public string $emoji,
        public ?string $completedAt,
    ) {}
}
