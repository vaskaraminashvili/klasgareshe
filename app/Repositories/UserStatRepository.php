<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\UserActivityDay;
use App\Models\UserStat;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

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
}
