<?php

namespace App\Data;

final readonly class ScreenTimeSnapshot
{
    /**
     * @param  list<WeekMinuteBar>  $weekBars
     * @param  list<int>  $bedtimeDays
     */
    public function __construct(
        public int $usedTodaySeconds,
        public ?int $limitMinutes,
        public int $extraMinutesToday,
        public ?int $remainingSeconds,
        public int $barPercent,
        public bool $breakReminders,
        public bool $warnBeforeLimit,
        public bool $bedtimeEnabled,
        public string $bedtimeStart,
        public string $bedtimeEnd,
        public array $bedtimeDays,
        public string $timezone,
        public array $weekBars,
        public int $weekMinutes,
        public int $daysHitGoal,
        public int $dailyGoalMinutes,
        public bool $extraGrantedToday,
    ) {}

    public function usedTodayMinutes(): int
    {
        return intdiv($this->usedTodaySeconds, 60);
    }

    public function remainingMinutes(): ?int
    {
        if ($this->remainingSeconds === null) {
            return null;
        }

        return (int) ceil($this->remainingSeconds / 60);
    }
}
