<?php

namespace App\Services;

use App\Data\BadgeCardView;
use App\Data\BadgeCollectionSnapshot;
use App\Data\BadgeProgress;
use App\Data\BadgeUnlockView;
use App\Data\RecentBadgeView;
use App\Enums\BadgeRule;
use App\Enums\SchoolGrade;
use App\Enums\SchoolSubject;
use App\Models\Badge;
use App\Models\User;
use App\Models\UserBadge;
use App\Repositories\BadgeRepository;
use App\Repositories\LeagueRepository;
use App\Repositories\UserStatRepository;
use App\Repositories\WeekPlanRepository;
use Carbon\CarbonInterface;

class BadgeService
{
    public function __construct(
        private BadgeRepository $badges,
        private WeekPlanRepository $plans,
        private UserStatRepository $stats,
        private LeagueRepository $leagues,
    ) {}

    /**
     * @return list<string>
     */
    public function evaluate(User $user): array
    {
        $earnedIds = [];

        foreach ($this->badges->forUser($user) as $row) {
            $earnedIds[$row->badge_id] = true;
        }

        $awarded = [];
        $bonus = 0;

        foreach ($this->badges->catalog() as $badge) {
            if (isset($earnedIds[$badge->id])) {
                continue;
            }

            if (! $this->progressFor($user, $badge)->met) {
                continue;
            }

            $this->badges->award($user, $badge);
            $awarded[] = $badge->slug;
            $bonus += $badge->xp_bonus;
        }

        if ($bonus > 0) {
            app(UserStatService::class)->recordPlay($user, $bonus, skipEvaluate: true);
        }

        return $awarded;
    }

    public function collection(User $user): BadgeCollectionSnapshot
    {
        $rows = $this->badges->forUser($user);
        $byBadgeId = [];

        foreach ($rows as $row) {
            $byBadgeId[$row->badge_id] = $row;
        }

        $earned = [];
        $inProgress = [];
        $locked = [];

        foreach ($this->badges->catalog() as $badge) {
            $card = $this->cardFor($user, $badge, $byBadgeId[$badge->id] ?? null);

            match ($card->status) {
                'got' => $earned[] = $card,
                'inprog' => $inProgress[] = $card,
                default => $locked[] = $card,
            };
        }

        $total = $this->badges->countCatalog();
        $earnedCount = count($earned);
        $percent = $total > 0 ? (int) floor(($earnedCount / $total) * 100) : 0;
        $rarity = $this->badges->earnedRarityCounts($user);
        $featured = $this->featuredCard($user, $byBadgeId);

        return new BadgeCollectionSnapshot(
            earnedCount: $earnedCount,
            totalCount: $total,
            inProgressCount: count($inProgress),
            lockedCount: count($locked),
            percentComplete: $percent,
            remaining: max(0, $total - $earnedCount),
            commonCount: $rarity['common'] ?? 0,
            rareCount: $rarity['rare'] ?? 0,
            epicCount: $rarity['epic'] ?? 0,
            legendCount: $rarity['legend'] ?? 0,
            holderPercentile: $this->collectionPercentile($earnedCount, $total),
            featured: $featured,
            earned: $earned,
            inProgress: $inProgress,
            locked: $locked,
        );
    }

    /**
     * @return list<RecentBadgeView>
     */
    public function recentRail(User $user, int $earnedLimit = 3): array
    {
        $cards = [];

        foreach ($this->badges->recentForUser($user, $earnedLimit) as $row) {
            $badge = $row->badge;

            if (! $badge instanceof Badge) {
                continue;
            }

            $unseen = $row->seen_at === null;
            $cards[] = new RecentBadgeView(
                slug: $badge->slug,
                name: $this->displayName($badge, true),
                emoji: $badge->emoji,
                medalClass: $badge->medalClass(),
                meta: $unseen ? (string) __('badges.new') : $this->relativeMeta($row),
                href: $unseen
                    ? route('badge-unlock', ['slug' => $badge->slug])
                    : route('badges'),
                locked: false,
                unseen: $unseen,
            );
        }

        $teaser = $this->lockedTeaser($user);

        if ($teaser instanceof RecentBadgeView) {
            $cards[] = $teaser;
        }

        return $cards;
    }

    public function firstUnseenSlug(User $user): ?string
    {
        $row = $this->badges->firstUnseen($user);
        $badge = $row?->badge;

        return $badge instanceof Badge ? $badge->slug : null;
    }

    public function markSeen(User $user, string $slug): void
    {
        $badge = $this->badges->findBySlug($slug);

        if ($badge === null) {
            return;
        }

        $row = $this->badges->findUserBadge($user, $badge);

        if ($row instanceof UserBadge) {
            $this->badges->markSeen($row);
        }
    }

    public function unlockView(User $user, string $slug): ?BadgeUnlockView
    {
        $badge = $this->badges->findBySlug($slug);

        if ($badge === null) {
            return null;
        }

        $row = $this->badges->findUserBadge($user, $badge);

        if (! $row instanceof UserBadge || $row->seen_at !== null) {
            return null;
        }

        $holders = $this->badges->holderCount($badge);
        $users = max(1, $this->badges->userCount());

        return new BadgeUnlockView(
            slug: $badge->slug,
            name: $this->displayName($badge, true),
            blurb: (string) __('badges.items.'.$badge->slug.'.blurb'),
            emoji: $badge->emoji,
            medalClass: $badge->medalClass(),
            rarityLabel: $badge->rarity->label(),
            xpBonus: $badge->xp_bonus,
            holderPercent: (int) max(1, min(100, round(($holders / $users) * 100))),
        );
    }

    public function earnedCount(User $user): int
    {
        return $this->badges->earnedCount($user);
    }

    public function catalogCount(): int
    {
        return $this->badges->countCatalog();
    }

    private function cardFor(User $user, Badge $badge, ?UserBadge $row): BadgeCardView
    {
        $earned = $row instanceof UserBadge;
        $progress = $this->progressFor($user, $badge);
        $status = $this->statusFor($badge, $earned, $progress);
        $unseen = $earned && $row->seen_at === null;
        $name = $this->displayName($badge, $earned);
        $hint = $earned
            ? (string) __('badges.items.'.$badge->slug.'.hint')
            : $this->lockedHint($badge);
        $href = '#';

        if ($earned && $unseen) {
            $href = route('badge-unlock', ['slug' => $badge->slug]);
        }

        return new BadgeCardView(
            slug: $badge->slug,
            name: $name,
            hint: $hint,
            status: $status,
            category: $badge->category->value,
            rarity: $badge->rarity->value,
            rarityLabel: $badge->rarity->label(),
            rarityClass: $badge->rarity->cssClass(),
            emoji: $earned || ! $badge->is_secret ? $badge->emoji : '❓',
            medalClass: $badge->medalClass(),
            isSecret: $badge->is_secret,
            unseen: $unseen,
            meta: $earned ? $this->relativeMeta($row) : $this->lockedMeta($badge, $progress),
            progressLabel: $this->progressLabel($badge, $progress),
            percent: $progress->percent,
            chipClass: $this->progressChipClass($progress->percent),
            href: $href,
        );
    }

    private function statusFor(Badge $badge, bool $earned, BadgeProgress $progress): string
    {
        if ($earned) {
            return 'got';
        }

        if ($badge->rule === BadgeRule::Locked || $badge->is_secret) {
            return 'lock';
        }

        if ($progress->current > 0 && $progress->current < $progress->target) {
            return 'inprog';
        }

        return 'lock';
    }

    private function progressFor(User $user, Badge $badge): BadgeProgress
    {
        $params = $badge->rule_params ?? [];
        $count = (int) ($params['count'] ?? 0);
        $subject = SchoolSubject::tryFrom((string) ($params['subject'] ?? ''));
        $grade = $user->grade ?? SchoolGrade::First;

        $current = 0;
        $target = max(1, $count);

        switch ($badge->rule) {
            case BadgeRule::PacksCompleted:
                $current = $this->plans->completedCount($user);
                break;
            case BadgeRule::SubjectPacksCompleted:
                $current = $subject instanceof SchoolSubject
                    ? $this->plans->completedCountForSubject($user, $subject)
                    : 0;
                break;
            case BadgeRule::StreakDays:
                $current = $this->stats->firstOrCreateFor($user)->current_streak;
                break;
            case BadgeRule::PerfectPacks:
                $current = $this->plans->perfectPackCount($user);
                break;
            case BadgeRule::ConsecutivePerfectPacks:
                $current = $this->plans->consecutivePerfectPackCount($user);
                break;
            case BadgeRule::SubjectsStarted:
                $current = $this->plans->distinctCompletedSubjectCount($user);
                break;
            case BadgeRule::SubjectCorrectAnswers:
                $current = $subject instanceof SchoolSubject
                    ? $this->plans->subjectCorrectCount($user, $subject)
                    : 0;
                break;
            case BadgeRule::MissionToday:
                $current = $this->plans->completedTodayCount($user);
                break;
            case BadgeRule::PlayAfterHour:
                $hour = (int) ($params['hour'] ?? 19);
                $current = $this->plans->completedAfterHourCount($user, $hour);
                break;
            case BadgeRule::LeagueTopFinish:
                $rank = (int) ($params['rank'] ?? 3);
                $current = $this->leagues->hasTopFinish($user, $rank) ? 1 : 0;
                $target = 1;
                break;
            case BadgeRule::WeekPacksCompleted:
                $current = $this->plans->completedCount($user);
                $weekTotal = $this->plans->itemsForGrade($grade)->count();
                $target = $count > 0 ? $count : max(1, $weekTotal);
                break;
            case BadgeRule::SubjectAllPerfect:
                $current = $this->plans->hasPerfectSubjectWeek($user, $grade) ? 1 : 0;
                $target = 1;
                break;
            case BadgeRule::Locked:
                $current = 0;
                $target = 1;
                break;
        }

        $current = max(0, $current);
        $target = max(1, $target);
        $met = $current >= $target;
        $percent = (int) min(100, floor(($current / $target) * 100));

        return new BadgeProgress($current, $target, $percent, $met);
    }

    private function displayName(Badge $badge, bool $revealed): string
    {
        if ($badge->is_secret && ! $revealed) {
            return (string) __('badges.secret_name');
        }

        return (string) __('badges.items.'.$badge->slug.'.name');
    }

    private function lockedHint(Badge $badge): string
    {
        if ($badge->is_secret) {
            return (string) __('badges.secret_hint');
        }

        return (string) __('badges.items.'.$badge->slug.'.hint');
    }

    private function lockedMeta(Badge $badge, BadgeProgress $progress): string
    {
        if ($badge->is_secret) {
            return (string) __('badges.secret_meta');
        }

        return (string) __('badges.items.'.$badge->slug.'.meta', [
            'current' => $progress->current,
            'target' => $progress->target,
        ]);
    }

    private function progressLabel(Badge $badge, BadgeProgress $progress): string
    {
        if ($badge->is_secret || $badge->rule === BadgeRule::Locked) {
            return '';
        }

        return (string) __('badges.items.'.$badge->slug.'.progress', [
            'current' => $progress->current,
            'target' => $progress->target,
        ]);
    }

    private function progressChipClass(int $percent): string
    {
        if ($percent >= 50) {
            return 'chip chip-primary';
        }

        if ($percent >= 20) {
            return 'chip chip-sun';
        }

        return 'chip';
    }

    private function relativeMeta(UserBadge $row): string
    {
        $at = $row->unlocked_at ?? $row->created_at;

        if (! $at instanceof CarbonInterface) {
            return (string) __('badges.today');
        }

        $days = (int) $at->copy()->startOfDay()->diffInDays(now()->startOfDay());

        return match (true) {
            $days <= 0 => (string) __('badges.today'),
            $days === 1 => (string) __('badges.yesterday'),
            default => (string) __('badges.days_ago', ['days' => $days]),
        };
    }

    /**
     * @param  array<int, UserBadge>  $byBadgeId
     */
    private function featuredCard(User $user, array $byBadgeId): ?BadgeCardView
    {
        $latest = $this->badges->latestForUser($user);

        if (! $latest instanceof UserBadge || ! $latest->badge instanceof Badge) {
            return null;
        }

        return $this->cardFor($user, $latest->badge, $byBadgeId[$latest->badge_id] ?? $latest);
    }

    private function lockedTeaser(User $user): ?RecentBadgeView
    {
        $earnedIds = [];

        foreach ($this->badges->forUser($user) as $row) {
            $earnedIds[$row->badge_id] = true;
        }

        foreach ($this->badges->catalog() as $badge) {
            if (isset($earnedIds[$badge->id]) || $badge->is_secret) {
                continue;
            }

            $progress = $this->progressFor($user, $badge);

            if ($this->statusFor($badge, false, $progress) === 'got') {
                continue;
            }

            return new RecentBadgeView(
                slug: $badge->slug,
                name: $this->displayName($badge, false),
                emoji: '🔒',
                medalClass: '',
                meta: $this->lockedMeta($badge, $progress),
                href: route('badges'),
                locked: true,
                unseen: false,
            );
        }

        return null;
    }

    private function collectionPercentile(int $earnedCount, int $total): int
    {
        if ($total <= 0 || $earnedCount <= 0) {
            return 0;
        }

        return (int) max(1, min(99, 100 - (int) floor(($earnedCount / $total) * 50)));
    }
}
