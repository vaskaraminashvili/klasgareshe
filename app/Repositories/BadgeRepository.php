<?php

namespace App\Repositories;

use App\Models\Badge;
use App\Models\User;
use App\Models\UserBadge;
use Illuminate\Support\Collection;

class BadgeRepository
{
    /**
     * @return Collection<int, Badge>
     */
    public function catalog(): Collection
    {
        return Badge::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function findBySlug(string $slug): ?Badge
    {
        return Badge::query()->where('slug', $slug)->first();
    }

    public function countCatalog(): int
    {
        return Badge::query()->count();
    }

    /**
     * @return Collection<int, UserBadge>
     */
    public function forUser(User $user): Collection
    {
        return UserBadge::query()
            ->where('user_id', $user->id)
            ->with('badge')
            ->orderBy('unlocked_at')
            ->get();
    }

    /**
     * @return Collection<int, UserBadge>
     */
    public function recentForUser(User $user, int $limit = 3): Collection
    {
        return UserBadge::query()
            ->where('user_id', $user->id)
            ->with('badge')
            ->orderByDesc('unlocked_at')
            ->limit($limit)
            ->get();
    }

    public function latestForUser(User $user): ?UserBadge
    {
        return UserBadge::query()
            ->where('user_id', $user->id)
            ->with('badge')
            ->orderByDesc('unlocked_at')
            ->first();
    }

    public function earnedCount(User $user): int
    {
        return UserBadge::query()->where('user_id', $user->id)->count();
    }

    public function earnedCountBetween(User $user, string $from, string $to): int
    {
        return UserBadge::query()
            ->where('user_id', $user->id)
            ->whereDate('unlocked_at', '>=', $from)
            ->whereDate('unlocked_at', '<=', $to)
            ->count();
    }

    public function findUserBadge(User $user, Badge $badge): ?UserBadge
    {
        return UserBadge::query()
            ->where('user_id', $user->id)
            ->where('badge_id', $badge->id)
            ->first();
    }

    public function firstUnseen(User $user): ?UserBadge
    {
        return UserBadge::query()
            ->where('user_id', $user->id)
            ->whereNull('seen_at')
            ->with('badge')
            ->join('badges', 'badges.id', '=', 'user_badges.badge_id')
            ->orderBy('badges.sort_order')
            ->orderBy('user_badges.id')
            ->select('user_badges.*')
            ->first();
    }

    public function award(User $user, Badge $badge): UserBadge
    {
        $existing = $this->findUserBadge($user, $badge);

        if ($existing instanceof UserBadge) {
            return $existing;
        }

        return UserBadge::query()->create([
            'user_id' => $user->id,
            'badge_id' => $badge->id,
            'unlocked_at' => now(),
            'seen_at' => null,
        ]);
    }

    public function markSeen(UserBadge $row): UserBadge
    {
        if ($row->seen_at !== null) {
            return $row;
        }

        $row->update(['seen_at' => now()]);

        return $row->fresh() ?? $row;
    }

    public function holderCount(Badge $badge): int
    {
        return UserBadge::query()->where('badge_id', $badge->id)->count();
    }

    public function userCount(): int
    {
        return User::query()->count();
    }

    /**
     * @return array<string, int>
     */
    public function earnedRarityCounts(User $user): array
    {
        $counts = [
            'common' => 0,
            'rare' => 0,
            'epic' => 0,
            'legend' => 0,
        ];

        foreach ($this->forUser($user) as $row) {
            $badge = $row->badge;

            if ($badge === null) {
                continue;
            }

            $key = $badge->rarity->value;
            $counts[$key] = $counts[$key] + 1;
        }

        return $counts;
    }
}
