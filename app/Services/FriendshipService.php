<?php

namespace App\Services;

use App\Data\FriendsLeaderboardSnapshot;
use App\Data\FriendsProfileStrip;
use App\Data\LeaderboardEntry;
use App\Enums\League;
use App\Enums\SchoolGrade;
use App\Enums\XpSource;
use App\Models\Badge;
use App\Models\User;
use App\Models\WeekPlanItem;
use App\Repositories\BadgeRepository;
use App\Repositories\FriendshipRepository;
use App\Repositories\PlaySessionRepository;
use App\Repositories\UserRepository;
use App\Repositories\UserStatRepository;
use App\Repositories\WeekPlanRepository;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class FriendshipService
{
    public function __construct(
        private FriendshipRepository $friendships,
        private UserRepository $users,
        private UserStatService $userStats,
        private LevelCalculator $levels,
        private PlaySessionRepository $sessions,
        private WeekPlanRepository $plans,
        private BadgeRepository $badgeRows,
        private UserStatRepository $statRows,
        private BadgeService $badges,
    ) {}

    /**
     * Send a friend request by nickname. It stays pending until the other kid's parent approves.
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

        $this->friendships->createPending($from, $target);
        app(NotificationService::class)->friendRequested($target, $from);
    }

    public function approve(User $parent, int $friendshipId): void
    {
        $this->assertParentUnlocked();
        $friendship = $this->friendships->findIncomingPending($parent, $friendshipId);

        if ($friendship === null) {
            throw ValidationException::withMessages([
                'friend' => (string) __('friends.errors.not_found'),
            ]);
        }

        $this->friendships->accept($friendship);
        $from = $friendship->user;

        if ($from instanceof User) {
            app(NotificationService::class)->friendAccepted($from, $parent);
            $this->badges->evaluate($from);
        }

        $this->badges->evaluate($parent);
    }

    public function decline(User $parent, int $friendshipId): void
    {
        $this->assertParentUnlocked();
        $friendship = $this->friendships->findIncomingPending($parent, $friendshipId);

        if ($friendship === null) {
            throw ValidationException::withMessages([
                'friend' => (string) __('friends.errors.not_found'),
            ]);
        }

        $this->friendships->decline($friendship);
    }

    private function assertParentUnlocked(): void
    {
        // Resolved lazily: ParentZoneService → ProgressReportService → FriendshipService.
        if (! app(ParentZoneService::class)->isUnlocked()) {
            throw ValidationException::withMessages([
                'friend' => (string) __('friends.errors.parent_required'),
            ]);
        }
    }

    public function friendsLeaderboard(User $user): FriendsLeaderboardSnapshot
    {
        $youStat = $this->userStats->ensureFor($user);
        $friends = $this->friendships->acceptedFriends($user);
        $friendCount = $friends->count();

        $participants = collect([$user])->merge($friends)->unique('id')->values();
        $online = array_flip($this->sessions->onlineUserIds());

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
                country: is_string($member->country) ? $member->country : '',
                online: isset($online[$member->id]),
                nickname: $member->nickname,
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
            beatingCount: $snap->beatingCount,
            avatars: $avatars,
        );
    }

    /**
     * @return list<array{name: string, avatar: string, streak: int, longest: int, isYou: bool, subtitle: string}>
     */
    public function friendFlames(User $user): array
    {
        $youStat = $this->userStats->ensureFor($user);
        $rows = [[
            'user' => $user,
            'streak' => $youStat->current_streak,
            'longest' => $youStat->longest_streak,
            'isYou' => true,
        ]];

        foreach ($this->friendships->acceptedFriends($user) as $friend) {
            $stat = $this->userStats->ensureFor($friend);
            $rows[] = [
                'user' => $friend,
                'streak' => $stat->current_streak,
                'longest' => $stat->longest_streak,
                'isYou' => false,
            ];
        }

        usort($rows, function (array $a, array $b): int {
            if ($a['streak'] !== $b['streak']) {
                return $b['streak'] <=> $a['streak'];
            }

            if ($a['longest'] !== $b['longest']) {
                return $b['longest'] <=> $a['longest'];
            }

            return $a['user']->id <=> $b['user']->id;
        });

        $flames = [];

        foreach ($rows as $index => $row) {
            /** @var User $member */
            $member = $row['user'];
            $rank = $index + 1;
            $subtitle = $row['isYou']
                ? ($rank === 1
                    ? (string) __('streak.you_lead')
                    : (string) __('streak.beat_to_place', ['place' => $rank - 1]))
                : (string) __('streak.longest_days', ['n' => $row['longest']]);

            $flames[] = [
                'name' => $row['isYou']
                    ? (string) __('streak.you_name', ['name' => $member->name])
                    : $member->name,
                'avatar' => $this->userStats->avatarFor($member),
                'streak' => $row['streak'],
                'longest' => $row['longest'],
                'isYou' => $row['isYou'],
                'subtitle' => $subtitle,
            ];
        }

        return $flames;
    }

    /**
     * @return list<array{id: int, name: string, avatar: string, when: string}>
     */
    public function incomingCards(User $user): array
    {
        $cards = [];

        foreach ($this->friendships->pendingIncoming($user) as $row) {
            $from = $row->user;

            if (! $from instanceof User) {
                continue;
            }

            $cards[] = [
                'id' => $row->id,
                'name' => $from->name,
                'avatar' => $this->userStats->avatarFor($from),
                'when' => $this->ago($row->created_at ?? now()),
            ];
        }

        return $cards;
    }

    /**
     * @return list<array{id: int, name: string, avatar: string}>
     */
    public function outgoingCards(User $user): array
    {
        $cards = [];

        foreach ($this->friendships->pendingOutgoing($user) as $row) {
            $to = $row->friend;

            if (! $to instanceof User) {
                continue;
            }

            $cards[] = [
                'id' => $row->id,
                'name' => $to->name,
                'avatar' => $this->userStats->avatarFor($to),
            ];
        }

        return $cards;
    }

    /**
     * @return list<array{id: int, name: string, avatar: string, level: int, streak: int}>
     */
    public function suggestions(User $user): array
    {
        $grade = $user->grade ?? SchoolGrade::First;
        $league = $this->userStats->ensureFor($user)->league ?? League::Bronze;
        $cards = [];

        foreach ($this->friendships->suggested($user, $grade, $league) as $candidate) {
            $stat = $this->userStats->ensureFor($candidate);
            $cards[] = [
                'id' => $candidate->id,
                'name' => $candidate->name,
                'avatar' => $this->userStats->avatarFor($candidate),
                'level' => $this->levels->forXp($stat->xp)->level,
                'streak' => $stat->current_streak,
                'nickname' => $candidate->nickname,
            ];
        }

        return $cards;
    }

    public function requestById(User $from, int $userId): void
    {
        $target = $this->users->findOrFail($userId);
        $this->request($from, $target->nickname);
    }

    /**
     * @return list<array{name: string, avatar: string, kind: string, detail: string, when: string, xp: int, streak: int, chip: string}>
     */
    public function todayActivity(User $user): array
    {
        $ids = $this->friendships->acceptedFriendIds($user);

        if ($ids === []) {
            return [];
        }

        $since = now()->startOfDay();
        $rows = [];

        foreach ($this->plans->completedSinceForUsers($ids, $since) as $progress) {
            $friend = $progress->user;
            $item = $progress->item;

            if (! $friend instanceof User || ! $item instanceof WeekPlanItem || $progress->completed_at === null) {
                continue;
            }

            $stat = $this->userStats->ensureFor($friend);
            $rows[] = [
                'at' => $progress->completed_at->getTimestamp(),
                'name' => $friend->name,
                'avatar' => $this->userStats->avatarFor($friend),
                'kind' => 'pack',
                'detail' => $item->subject->label(),
                'when' => $this->ago($progress->completed_at),
                'xp' => $this->statRows->xpForContext($friend, XpSource::Pack, 'pack-'.$item->id),
                'streak' => $stat->current_streak,
                'chip' => 'streak',
            ];
        }

        foreach ($this->badgeRows->unlockedSinceForUsers($ids, $since) as $award) {
            $friend = $award->user;
            $badge = $award->badge;

            if (! $friend instanceof User || ! $badge instanceof Badge || $award->unlocked_at === null) {
                continue;
            }

            $rows[] = [
                'at' => $award->unlocked_at->getTimestamp(),
                'name' => $friend->name,
                'avatar' => $this->userStats->avatarFor($friend),
                'kind' => 'badge',
                'detail' => (string) __('badges.items.'.$badge->slug.'.name'),
                'when' => $this->ago($award->unlocked_at),
                'xp' => 0,
                'streak' => 0,
                'chip' => 'new',
            ];
        }

        usort($rows, static fn (array $a, array $b): int => $b['at'] <=> $a['at']);
        $rows = array_slice($rows, 0, 6);
        $feed = [];

        foreach ($rows as $row) {
            unset($row['at']);
            $feed[] = $row;
        }

        return $feed;
    }

    private function ago(CarbonInterface $at): string
    {
        $minutes = (int) $at->diffInMinutes(now());

        if ($minutes < 1) {
            return (string) __('alerts.just_now');
        }

        if ($minutes < 60) {
            return (string) __('alerts.minutes_ago', ['n' => $minutes]);
        }

        $hours = (int) $at->diffInHours(now());

        if ($hours < 24) {
            return (string) __('alerts.hours_ago', ['n' => $hours]);
        }

        return (string) __('alerts.days_ago', ['n' => max(1, (int) $at->diffInDays(now()))]);
    }
}
