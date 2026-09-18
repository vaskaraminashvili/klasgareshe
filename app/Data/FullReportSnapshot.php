<?php

namespace App\Data;

use App\Enums\ReportScope;

final readonly class FullReportSnapshot
{
    /**
     * @param  list<array{label: string, value: int, height: int}>  $trend
     * @param  list<ReportSubjectRow>  $subjects
     * @param  list<array{key: string, name: string, value: string, unit: string, delta: string, emoji: string, tile: string, body: string}>  $kpis
     * @param  list<ReportHighlight>  $timeline
     * @param  list<ReportHighlight>  $insights
     */
    public function __construct(
        public ReportScope $scope,
        public string $kidName,
        public string $rangeEyebrow,
        public string $headline,
        public string $subline,
        public string $scopeChip,
        public PeriodFigures $figures,
        public array $trend,
        public array $subjects,
        public array $kpis,
        public array $timeline,
        public array $insights,
        public string $paceChip,
        public string $daysChip,
        public string $minutesHint,
    ) {}
}
