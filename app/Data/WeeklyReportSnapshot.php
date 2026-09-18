<?php

namespace App\Data;

final readonly class WeeklyReportSnapshot
{
    /**
     * @param  list<ReportDayPoint>  $days
     * @param  list<ReportSubjectRow>  $subjects
     * @param  list<ReportHighlight>  $highlights
     * @param  list<ReportHighlight>  $concerns
     * @param  list<ReportWeekOption>  $weekOptions
     * @param  list<array{title: string, subtitle: string}>  $selectedActivities
     */
    public function __construct(
        public string $kidName,
        public string $parentEmail,
        public bool $emailWeekly,
        public string $headline,
        public string $subline,
        public string $weekChip,
        public PeriodFigures $figures,
        public array $days,
        public array $subjects,
        public array $highlights,
        public array $concerns,
        public array $weekOptions,
        public ?ReportDayPoint $selectedDay,
        public array $selectedActivities,
        public string $totalMinutesLabel,
        public string $minutesHint,
    ) {}
}
