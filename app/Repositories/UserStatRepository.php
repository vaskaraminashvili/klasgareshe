<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\UserActivityDay;
use App\Models\UserStat;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class UserStatRepository
{
    public function firstOrCreateFor(User $user): UserStat
    {
        return UserStat::query()->firstOrCreate(
            ['user_id' => $user->id],
            UserStat::defaults(),
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(UserStat $stat, array $attributes): UserStat
    {
        $stat->update($attributes);

        return $stat->fresh() ?? $stat;
    }

    public function addDayXp(User $user, string $playedOn, int $xp): void
    {
        $day = UserActivityDay::query()
            ->where('user_id', $user->id)
            ->whereDate('played_on', $playedOn)
            ->first();

        if ($day === null) {
            $day = UserActivityDay::query()->create([
                'user_id' => $user->id,
                'played_on' => $playedOn,
                'xp_earned' => 0,
            ]);
        }

        if ($xp > 0) {
            $day->increment('xp_earned', $xp);
        }
    }

    /**
     * @return list<string>
     */
    public function playedDatesBetween(User $user, string $from, string $to): array
    {
        $dates = [];

        foreach (UserActivityDay::query()
            ->where('user_id', $user->id)
            ->whereDate('played_on', '>=', $from)
            ->whereDate('played_on', '<=', $to)
            ->orderBy('played_on')
            ->pluck('played_on') as $date) {
            $dates[] = $date instanceof CarbonInterface
                ? $date->toDateString()
                : CarbonImmutable::parse((string) $date)->toDateString();
        }

        return $dates;
    }

    /**
     * @return array<string, int> keyed by Y-m-d
     */
    public function xpByDateBetween(User $user, string $from, string $to): array
    {
        $map = [];

        foreach (UserActivityDay::query()
            ->where('user_id', $user->id)
            ->whereDate('played_on', '>=', $from)
            ->whereDate('played_on', '<=', $to)
            ->get(['played_on', 'xp_earned']) as $day) {
            $date = $day->played_on instanceof CarbonInterface
                ? $day->played_on->toDateString()
                : CarbonImmutable::parse((string) $day->played_on)->toDateString();
            $map[$date] = (int) $day->xp_earned;
        }

        return $map;
    }

    public function sumXpBetween(User $user, string $from, string $to): int
    {
        return (int) UserActivityDay::query()
            ->where('user_id', $user->id)
            ->whereDate('played_on', '>=', $from)
            ->whereDate('played_on', '<=', $to)
            ->sum('xp_earned');
    }

    public function countLearners(): int
    {
        return UserStat::query()->count();
    }

    /**
     * @return Collection<int, UserStat>
     */
    public function topByXp(int $limit = 50): Collection
    {
        return UserStat::query()
            ->with('user')
            ->orderByDesc('xp')
            ->orderBy('user_id')
            ->limit($limit)
            ->get();
    }

    public function rankFor(User $user): int
    {
        $stat = $this->firstOrCreateFor($user);

        return 1 + (int) UserStat::query()
            ->where(function ($query) use ($stat): void {
                $query->where('xp', '>', $stat->xp)
                    ->orWhere(function ($inner) use ($stat): void {
                        $inner->where('xp', $stat->xp)
                            ->where('user_id', '<', $stat->user_id);
                    });
            })
            ->count();
    }

    public function xpAtRank(int $rank): ?int
    {
        if ($rank < 1) {
            return null;
        }

        $row = UserStat::query()
            ->orderByDesc('xp')
            ->orderBy('user_id')
            ->skip($rank - 1)
            ->take(1)
            ->first();

        return $row?->xp;
    }
}
