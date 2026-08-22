<?php

namespace App\Data;

final readonly class BadgeCollectionSnapshot
{
    /**
     * @param  list<BadgeCardView>  $earned
     * @param  list<BadgeCardView>  $inProgress
     * @param  list<BadgeCardView>  $locked
     */
    public function __construct(
        public int $earnedCount,
        public int $totalCount,
        public int $inProgressCount,
        public int $lockedCount,
        public int $percentComplete,
        public int $remaining,
        public int $commonCount,
        public int $rareCount,
        public int $epicCount,
        public int $legendCount,
        public int $holderPercentile,
        public ?BadgeCardView $featured,
        public array $earned,
        public array $inProgress,
        public array $locked,
    ) {}
}
