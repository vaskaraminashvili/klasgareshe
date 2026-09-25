<?php

namespace App\Repositories;

use App\Enums\League;
use App\Enums\LeagueOutcome;
use App\Models\LeagueSeasonPayout;
use App\Models\LeagueWeek;
use App\Models\User;
use Illuminate\Support\Collection;

class LeaguePayoutRepository
{
    public function exists(User $user, LeagueWeek $week): bool
    {
        return LeagueSeasonPayout::query()
            ->where('user_id', $user->id)
            ->where('league_week_id', $week->id)
            ->exists();
    }

    public function create(
        User $user,
        LeagueWeek $week,
        League $tier,
        int $finishRank,
        LeagueOutcome $outcome,
        int $stayBonusXp,
        ?int $prizeXp,
    ): LeagueSeasonPayout {
        return LeagueSeasonPayout::query()->create([
            'user_id' => $user->id,
            'league_week_id' => $week->id,
            'tier' => $tier,
            'finish_rank' => $finishRank,
            'outcome' => $outcome,
            'stay_bonus_xp' => $stayBonusXp,
            'prize_xp' => $prizeXp,
            'prize_claimed_at' => null,
            'paid_at' => now(),
        ]);
    }

    public function findForWeek(User $user, int $weekId): ?LeagueSeasonPayout
    {
        return LeagueSeasonPayout::query()
            ->where('user_id', $user->id)
            ->where('league_week_id', $weekId)
            ->first();
    }

    /**
     * @return Collection<int, LeagueSeasonPayout>
     */
    public function unclaimedPrizes(User $user): Collection
    {
        return LeagueSeasonPayout::query()
            ->where('user_id', $user->id)
            ->whereNotNull('prize_xp')
            ->whereNull('prize_claimed_at')
            ->orderByDesc('league_week_id')
            ->get();
    }

    public function markPrizeClaimed(LeagueSeasonPayout $payout): LeagueSeasonPayout
    {
        $payout->update(['prize_claimed_at' => now()]);

        return $payout->fresh() ?? $payout;
    }
}
