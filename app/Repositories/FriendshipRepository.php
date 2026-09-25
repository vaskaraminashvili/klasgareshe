<?php

namespace App\Repositories;

use App\Enums\FriendshipStatus;
use App\Enums\League;
use App\Enums\SchoolGrade;
use App\Models\Friendship;
use App\Models\User;
use App\Models\UserStat;
use Illuminate\Support\Collection;

class FriendshipRepository
{
    public function createPending(User $from, User $to): Friendship
    {
        return Friendship::query()->create([
            'user_id' => $from->id,
            'friend_id' => $to->id,
            'status' => FriendshipStatus::Pending,
            'accepted_at' => null,
        ]);
    }

    public function accept(Friendship $friendship): Friendship
    {
        $friendship->update([
            'status' => FriendshipStatus::Accepted,
            'accepted_at' => $friendship->accepted_at ?? now(),
        ]);

        return $friendship->fresh() ?? $friendship;
    }

    public function decline(Friendship $friendship): Friendship
    {
        $friendship->update([
            'status' => FriendshipStatus::Declined,
            'accepted_at' => null,
        ]);

        return $friendship->fresh() ?? $friendship;
    }

    /**
     * @return list<int>
     */
    public function acceptedFriendIds(User $user): array
    {
        $asRequester = Friendship::query()
            ->where('user_id', $user->id)
            ->where('status', FriendshipStatus::Accepted)
            ->pluck('friend_id');

        $asFriend = Friendship::query()
            ->where('friend_id', $user->id)
            ->where('status', FriendshipStatus::Accepted)
            ->pluck('user_id');

        $ids = [];

        foreach ($asRequester->merge($asFriend)->unique()->values() as $id) {
            $ids[] = (int) $id;
        }

        return $ids;
    }

    /**
     * @return Collection<int, Friendship>
     */
    public function pendingIncoming(User $user): Collection
    {
        return Friendship::query()
            ->with('user')
            ->where('friend_id', $user->id)
            ->where('status', FriendshipStatus::Pending)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @return Collection<int, Friendship>
     */
    public function pendingOutgoing(User $user): Collection
    {
        return Friendship::query()
            ->with('friend')
            ->where('user_id', $user->id)
            ->where('status', FriendshipStatus::Pending)
            ->orderByDesc('created_at')
            ->get();
    }

    public function existsBetween(User $a, User $b): bool
    {
        return Friendship::query()
            ->where(function ($query) use ($a, $b): void {
                $query->where('user_id', $a->id)->where('friend_id', $b->id);
            })
            ->orWhere(function ($query) use ($a, $b): void {
                $query->where('user_id', $b->id)->where('friend_id', $a->id);
            })
            ->exists();
    }

    public function countAccepted(User $user): int
    {
        return count($this->acceptedFriendIds($user));
    }

    /**
     * @return Collection<int, User>
     */
    public function acceptedFriends(User $user): Collection
    {
        $ids = $this->acceptedFriendIds($user);

        if ($ids === []) {
            return collect();
        }

        return User::query()->whereIn('id', $ids)->get();
    }

    public function findIncomingPending(User $user, int $id): ?Friendship
    {
        return Friendship::query()
            ->with('user')
            ->whereKey($id)
            ->where('friend_id', $user->id)
            ->where('status', FriendshipStatus::Pending)
            ->first();
    }

    /**
     * Anyone already connected (pending, accepted, or declined) plus self.
     *
     * @return list<int>
     */
    public function connectedIds(User $user): array
    {
        $asRequester = Friendship::query()->where('user_id', $user->id)->pluck('friend_id');
        $asFriend = Friendship::query()->where('friend_id', $user->id)->pluck('user_id');
        $ids = [$user->id];

        foreach ($asRequester->merge($asFriend)->unique()->values() as $id) {
            $ids[] = (int) $id;
        }

        return array_values(array_unique($ids));
    }

    /**
     * Same class and league, visible on the leaderboard, and still accepting requests.
     *
     * @return Collection<int, User>
     */
    public function suggested(User $user, SchoolGrade $grade, League $league, int $limit = 8): Collection
    {
        $blocked = $this->connectedIds($user);

        return User::query()
            ->visibleOnLeaderboard()
            ->where('allow_friend_requests', true)
            ->where(function ($query) use ($grade): void {
                $query->where('grade', $grade->value);
                if ($grade === SchoolGrade::First) {
                    $query->orWhereNull('grade');
                }
            })
            ->whereNotIn('id', $blocked)
            ->whereIn('id', UserStat::query()->where('league', $league->value)->select('user_id'))
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    public function deleteAllFor(User $user): void
    {
        Friendship::query()
            ->where('user_id', $user->id)
            ->orWhere('friend_id', $user->id)
            ->delete();
    }
}
