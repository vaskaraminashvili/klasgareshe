<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\UserPlaySession;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class PlaySessionRepository
{
    /**
     * A heartbeat older than this is not "online now". Games poll every 15 seconds.
     */
    public const FRESH_SECONDS = 120;

    /**
     * Kids with an open play session and a recent heartbeat. This is the T07 signal —
     * there is no separate presence ping, so browsing without a game does not count.
     *
     * @return list<int>
     */
    public function onlineUserIds(?CarbonInterface $now = null): array
    {
        $now ??= now();
        $since = CarbonImmutable::parse($now)->subSeconds(self::FRESH_SECONDS);
        $ids = [];

        foreach (UserPlaySession::query()
            ->whereNull('ended_at')
            ->where('last_heartbeat_at', '>=', $since)
            ->pluck('user_id') as $id) {
            $ids[] = (int) $id;
        }

        return array_values(array_unique($ids));
    }

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
