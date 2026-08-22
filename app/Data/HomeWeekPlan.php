<?php

namespace App\Data;

final readonly class HomeWeekPlan
{
    /**
     * @param  list<WeekPlanTaskView>  $tasks
     */
    public function __construct(
        public int $missionDone,
        public int $missionTotal,
        public int $hoursLeft,
        public string $heroTitle,
        public ?int $continueItemId,
        public string $continueTitle,
        public array $tasks,
        public int $weekCompleted,
        public int $weekTotal,
    ) {}
}
