<?php

namespace App\Data;

use App\Enums\ReportScope;

final readonly class ExportProgressSnapshot
{
    public function __construct(
        public ReportScope $scope,
        public string $kidName,
        public string $rangeLabel,
        public string $coverKicker,
        public string $coverTitle,
        public string $coverMeta,
        public PeriodFigures $figures,
        public int $badgeTotal,
        public bool $minutesTracked,
    ) {}
}
