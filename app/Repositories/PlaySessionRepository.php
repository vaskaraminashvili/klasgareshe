<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\UserPlaySession;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class PlaySessionRepository
{
    public function create(User $user, CarbonInterface $startedAt): UserPlaySession
    {
        return UserPlaySession::query()->create([
            'user_id' => $user->id,
            'started_at' => $startedAt,
            'last_heartbeat_at' => $startedAt,
            'ended_at' => null,
            'seconds' => 0,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(UserPlaySession $session, array $attributes): UserPlaySession
    {
        $session->update($attributes);

        return $session->fresh() ?? $session;
    }

    public function openFor(User $user): ?UserPlaySession
    {
        return UserPlaySession::query()
            ->where('user_id', $user->id)
            ->whereNull('ended_at')
            ->orderByDesc('started_at')
            ->first();
    }

    /**
     * @return Collection<int, UserPlaySession>
     */
    public function overlapping(User $user, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return UserPlaySession::query()
            ->where('user_id', $user->id)
            ->where('started_at', '<=', $to)
            ->where(function ($query) use ($from): void {
                $query->whereNull('ended_at')
                    ->orWhere('ended_at', '>=', $from)
                    ->orWhere('last_heartbeat_at', '>=', $from);
            })
            ->orderBy('started_at')
            ->get();
    }

    public function earliestStartedAt(User $user): ?CarbonInterface
    {
        $row = UserPlaySession::query()
            ->where('user_id', $user->id)
            ->orderBy('started_at')
            ->first();

        return $row?->started_at;
    }

    public function countOverlapping(User $user, CarbonInterface $from, CarbonInterface $to): int
    {
        return $this->overlapping($user, $from, $to)->count();
    }
}
