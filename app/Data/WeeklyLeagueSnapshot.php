<?php

namespace App\Data;

use App\Enums\League;

final readonly class WeeklyLeagueSnapshot
{
    /**
     * @param  list<CohortMemberRow>  $members
     * @param  list<array{label: string, value: int}>  $chartDays
     * @param  list<array{weekLabel: string, tier: string, rank: int, outcome: string}>  $journey
     */
    public function __construct(
        public League $tier,
        public string $tierLabel,
        public int $yourRank,
        public int $yourWeekXp,
        public int $memberCount,
        public int $xpGapToNext,
        public int $promoteThresholdXp,
        public int $xpToPromote,
        public string $statusLabel,
        public string $endsInShort,
        public string $weekRangeLabel,
        public string $startsOn,
        public string $endsOn,
        public array $members,
        public array $chartDays,
        public string $chartJson,
        public int $bestDayXp,
        public string $bestDayLabel,
        public int $weekXpTotal,
        public int $vsLastWeekPercent,
        public array $journey,
        public LevelProgress $level,
    ) {}
}
