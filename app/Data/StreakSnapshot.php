<?php

namespace App\Data;

final readonly class StreakSnapshot
{
    /**
     * @param  list<array{letter: string, on: bool, today: bool, dayNum: int, ico: string}>  $weekDays
     * @param  list<array{empty: bool, day: int, on: bool, miss: bool, today: bool}>  $calendarCells
     * @param  list<array{days: int, name: string, hint: string, status: string, chip: string, ico: string, percent: int}>  $milestones
     * @param  list<array{name: string, avatar: string, streak: int, longest: int, isYou: bool, subtitle: string}>  $friendFlames
     */
    public function __construct(
        public int $current,
        public int $best,
        public int $weekActiveDays,
        public array $weekDays,
        public string $leagueLabel,
        public bool $playedToday,
        public bool $grewFromYesterday,
        public string $heroLine,
        public string $checkInTitle,
        public string $checkInMeta,
        public string $monthLabel,
        public int $monthHits,
        public array $calendarCells,
        public array $milestones,
        public int $milestonesDone,
        public int $milestonesTotal,
        public int $freezes,
        public int $freezeCap,
        public bool $canUseFreeze,
        public array $friendFlames,
        public string $chartJson,
    ) {}
}
