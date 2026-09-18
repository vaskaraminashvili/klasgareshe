<?php

namespace App\Data;

final readonly class ParentControlsSnapshot
{
    /**
     * @param  list<string>  $subjectLabels
     */
    public function __construct(
        public string $kidName,
        public int $age,
        public string $gradeLabel,
        public int $weekXp,
        public int $weekActiveDays,
        public int $weekLessons,
        public string $weekRangeLabel,
        public string $email,
        public bool $emailVerified,
        public bool $allowFriendRequests,
        public bool $showOnLeaderboard,
        public array $subjectLabels,
        public string $pinChangedLabel,
        public int $dailyGoalMinutes,
        public bool $hasPin,
    ) {}
}
