<?php

namespace App\Services;

use App\Data\FriendsLeaderboardSnapshot;
use App\Data\FriendsProfileStrip;
use App\Data\LeaderboardEntry;
use App\Models\User;
use App\Repositories\FriendshipRepository;
use App\Repositories\UserRepository;
use Illuminate\Validation\ValidationException;

class FriendshipService
{
    public function __construct(
        private FriendshipRepository $friendships,
        private UserRepository $users,
        private UserStatService $userStats,
        private LevelCalculator $levels,
    ) {}

    /**
     * Send a friend request by nickname.
     *
     * v1: auto-accept immediately so kids can play together without a parent PIN.
     * Parent approval for friend requests comes later.
     */
    public function request(User $from, string $nickname): void
    {
        $nickname = trim($nickname);

        if ($nickname === '') {
            throw ValidationException::withMessages([
                'nickname' => (string) __('friends.errors.nickname_required'),
            ]);
        }

        $target = $this->users->findByNickname($nickname);

        if ($target === null) {
            throw ValidationException::withMessages([
                'nickname' => (string) __('friends.errors.not_found'),
            ]);
        }

        if ($target->id === $from->id) {
            throw ValidationException::withMessages([
                'nickname' => (string) __('friends.errors.self'),
            ]);
        }

        if (! $target->allow_friend_requests) {
            throw ValidationException::withMessages([
                'nickname' => (string) __('friends.errors.not_allowed'),
            ]);
        }

        if ($this->friendships->existsBetween($from, $target)) {
            throw ValidationException::withMessages([
                'nickname' => (string) __('friends.errors.already_friends'),
            ]);
        }

        $friendship = $this->friendships->createPending($from, $target);
        // v1 auto-accept — parent PIN approval will replace this later.
        $this->friendships->accept($friendship);
    }

    public function friendsLeaderboard(User $user): FriendsLeaderboardSnapshot
    {
        $youStat = $this->userStats->ensureFor($user);
        $friends = $this->friendships->acceptedFriends($user);
        $friendCount = $friends->count();

        $participants = collect([$user])->merge($friends)->unique('id')->values();

        $entries = [];
        foreach ($participants as $member) {
            $stat = $member->id === $user->id
                ? $youStat
                : $this->userStats->ensureFor($member);

            $entries[] = [
                'user' => $member,
                'xp' => $stat->xp,
                'streak' => $stat->current_streak,
            ];
        }

        usort($entries, function (array $a, array $b): int {
            if ($a['xp'] !== $b['xp']) {
                return $b['xp'] <=> $a['xp'];
            }

            return $a['user']->id <=> $b['user']->id;
        });

        $rows = [];
        $yourRank = 1;
        $beatingCount = 0;
        $xpBehindLeader = 0;
        $xpBehindName = '';
        $xpAheadNext = 0;
        $xpAheadName = '';

        foreach ($entries as $index => $entry) {
            $rank = $index + 1;
            /** @var User $member */
            $member = $entry['user'];
            $isYou = $member->id === $user->id;
            $level = $this->levels->forXp($entry['xp'])->level;

            $rows[] = new LeaderboardEntry(
                rank: $rank,
                userId: $member->id,
                name: $member->name,
                xp: $entry['xp'],
                level: $level,
                streak: $entry['streak'],
                isYou: $isYou,
                avatar: $this->userStats->avatarFor($member),
            );

            if ($isYou) {
                $yourRank = $rank;
                $beatingCount = max(0, count($entries) - $rank);

                if ($rank > 1) {
                    $leader = $entries[0];
                    $xpBehindLeader = max(0, $leader['xp'] - $entry['xp']);
                    $xpBehindName = $leader['user']->name;
                }

                if (isset($entries[$index + 1])) {
                    $below = $entries[$index + 1];
                    $xpAheadNext = max(0, $entry['xp'] - $below['xp']);
                    $xpAheadName = $below['user']->name;
                }
            }
        }

        $podium = array_values(array_filter(
            $rows,
            static fn (LeaderboardEntry $e): bool => $e->rank <= 3,
        ));

        $youLevel = $this->levels->forXp($youStat->xp);

        return new FriendsLeaderboardSnapshot(
            friendCount: $friendCount,
            onlineCount: $friendCount,
            beatingCount: $friendCount === 0 ? 0 : $beatingCount,
            yourRank: $friendCount === 0 ? 1 : $yourRank,
            yourXp: $youStat->xp,
            yourName: $user->name,
            yourLevel: $youLevel->level,
            yourStreak: $youStat->current_streak,
            yourAvatar: $this->userStats->avatarFor($user),
            xpBehindLeader: $xpBehindLeader,
            xpBehindName: $xpBehindName,
            xpAheadNext: $xpAheadNext,
            xpAheadName: $xpAheadName,
            podium: $podium,
            rows: $rows,
        );
    }

    public function profileStrip(User $user): FriendsProfileStrip
    {
        $snap = $this->friendsLeaderboard($user);
        $avatars = [];

        foreach ($snap->rows as $entry) {
            if ($entry->isYou) {
                continue;
            }

            $avatars[] = $entry->avatar;

            if (count($avatars) >= 4) {
                break;
            }
        }

        return new FriendsProfileStrip(
            count: $snap->friendCount,
            onlineCount: $snap->onlineCount,
            beatingCount: $snap->beatingCount,
            avatars: $avatars,
        );
    }
}
