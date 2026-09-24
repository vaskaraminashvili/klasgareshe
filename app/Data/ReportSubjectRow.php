<?php

namespace App\Data;

use App\Enums\SchoolSubject;

final readonly class ReportSubjectRow
{
    public function __construct(
        public SchoolSubject $subject,
        public string $label,
        public string $emoji,
        public string $tile,
        public string $progressClass,
        public int $packs,
        public int $masteryPercent,
        public int $barPercent,
        public ?int $nextItemId,
        public string $href = '',
    ) {}
}
