<?php

namespace App\Data;

final readonly class DailyMissionSnapshot
{
    /**
     * @param  list<WeekChecklistItem>  $items
     */
    public function __construct(
        public int $missionDone,
        public int $missionTotal,
        public int $hoursLeft,
        public int $weekCompleted,
        public int $weekTotal,
        public array $items,
        public int $streak,
    ) {}
}
