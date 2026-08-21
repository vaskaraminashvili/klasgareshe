<?php

namespace App\Repositories;

use App\Enums\League;
use App\Enums\LeagueWeekStatus;
use App\Models\LeagueGroup;
use App\Models\LeagueGroupMember;
use App\Models\LeagueWeek;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LeagueRepository
{
    public function findOpenWeekStarting(string $startsOn): ?LeagueWeek
    {
        return LeagueWeek::query()
            ->whereDate('starts_on', $startsOn)
            ->where('status', LeagueWeekStatus::Open)
            ->first();
    }

    public function findWeekStarting(string $startsOn): ?LeagueWeek
    {
        return LeagueWeek::query()
            ->whereDate('starts_on', $startsOn)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createWeek(array $attributes): LeagueWeek
    {
        return LeagueWeek::query()->create($attributes);
    }

    /**
     * @return Collection<int, LeagueWeek>
     */
    public function openWeeks(): Collection
    {
        return LeagueWeek::query()
            ->where('status', LeagueWeekStatus::Open)
            ->orderBy('starts_on')
            ->get();
    }

    public function createGroup(LeagueWeek $week, League $tier, int $capacity = 12): LeagueGroup
    {
        return LeagueGroup::query()->create([
            'league_week_id' => $week->id,
            'tier' => $tier,
            'capacity' => $capacity,
        ]);
    }

    public function findOpenGroupWithSpace(LeagueWeek $week, League $tier): ?LeagueGroup
    {
        return LeagueGroup::query()
            ->where('league_week_id', $week->id)
            ->where('tier', $tier)
            ->withCount('members')
            ->get()
            ->first(fn (LeagueGroup $group): bool => $group->members_count < $group->capacity);
    }

    public function membershipForUserInWeek(User $user, LeagueWeek $week): ?LeagueGroupMember
    {
        return LeagueGroupMember::query()
            ->where('user_id', $user->id)
            ->whereHas('group', fn ($q) => $q->where('league_week_id', $week->id))
            ->with(['group.week', 'user'])
            ->first();
    }

    public function addMember(LeagueGroup $group, User $user, int $weekXp = 0): LeagueGroupMember
    {
        return LeagueGroupMember::query()->create([
            'league_group_id' => $group->id,
            'user_id' => $user->id,
            'week_xp' => $weekXp,
        ]);
    }

    public function incrementWeekXp(LeagueGroupMember $member, int $xp): LeagueGroupMember
    {
        $member->increment('week_xp', $xp);

        return $member->fresh() ?? $member;
    }

    /**
     * @return Collection<int, LeagueGroupMember>
     */
    public function membersRanked(LeagueGroup $group): Collection
    {
        return LeagueGroupMember::query()
            ->where('league_group_id', $group->id)
            ->with('user')
            ->orderByDesc('week_xp')
            ->orderBy('user_id')
            ->get();
    }

    /**
     * @return Collection<int, LeagueGroup>
     */
    public function groupsForWeek(LeagueWeek $week): Collection
    {
        return LeagueGroup::query()
            ->where('league_week_id', $week->id)
            ->with(['members.user'])
            ->get();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateMember(LeagueGroupMember $member, array $attributes): LeagueGroupMember
    {
        $member->update($attributes);

        return $member->fresh() ?? $member;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateWeek(LeagueWeek $week, array $attributes): LeagueWeek
    {
        $week->update($attributes);

        return $week->fresh() ?? $week;
    }

    /**
     * @return Collection<int, LeagueGroupMember>
     */
    public function recentHistoryFor(User $user, int $limit = 8): Collection
    {
        return LeagueGroupMember::query()
            ->where('user_id', $user->id)
            ->whereNotNull('finish_rank')
            ->with('group.week')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
