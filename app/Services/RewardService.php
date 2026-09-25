<?php

namespace App\Services;

use App\Data\RewardClaimCard;
use App\Data\RewardClaimResult;
use App\Data\RewardsDashboardSnapshot;
use App\Enums\RewardClaimType;
use App\Enums\XpSource;
use App\Models\Badge;
use App\Models\LeagueSeasonPayout;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\XpEvent;
use App\Repositories\BadgeRepository;
use App\Repositories\LeaguePayoutRepository;
use App\Repositories\RewardRepository;
use App\Repositories\UserStatRepository;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class RewardService
{
    public const DAILY_BOX_XP = 40;

    public function __construct(
        private RewardRepository $claims,
        private UserStatService $stats,
        private UserStatRepository $statRows,
        private BadgeRepository $badges,
        private LevelCalculator $levels,
        private LeaguePayoutRepository $payouts,
    ) {}

    public function pendingCount(User $user): int
    {
        return count($this->claimCards($user)) + ($this->loginClaimedToday($user) ? 0 : 1);
    }

    public function dashboard(User $user): RewardsDashboardSnapshot
    {
        $stat = $this->stats->ensureFor($user);
        $level = $this->levels->forXp($stat->xp);
        $today = CarbonImmutable::now()->toDateString();
        $weekStart = CarbonImmutable::now()->startOfWeek(CarbonImmutable::MONDAY);
        $claims = $this->claimCards($user);
        $loginClaimed = $this->loginClaimedToday($user);
        $todayLoginXp = $loginClaimed
            ? $this->loginAmountOn($user, $today)
            : $this->stats->nextDailyLoginXp($user);

        return new RewardsDashboardSnapshot(
            xp: $stat->xp,
            coins: $stat->coins,
            todayXp: $this->statRows->sumXpBetween($user, $today, $today),
            weekXp: $this->statRows->sumXpBetween($user, $weekStart->toDateString(), $weekStart->addDays(6)->toDateString()),
            claimCount: count($claims) + ($loginClaimed ? 0 : 1),
            badgeCount: $this->badges->forUser($user)->count(),
            badgeTotal: max(1, $this->badges->countCatalog()),
            leagueLabel: $stat->league->label(),
            level: $level->level,
            xpToNext: $level->xpToNext,
            nextLevel: $level->nextLevel,
            claims: $claims,
            calendar: $this->calendar($user),
            calendarDay: $this->calendarProgress($user),
            loginClaimed: $loginClaimed,
            todayLoginXp: $todayLoginXp,
            today: $today,
            activity: $this->activity($user),
        );
    }

    public function claim(User $user, RewardClaimType $type, string $reference): RewardClaimResult
    {
        return match ($type) {
            RewardClaimType::DailyBox => $this->claimDailyBox($user, $reference),
            RewardClaimType::DailyLogin => $this->claimLogin($user, $reference),
            RewardClaimType::Badge => $this->claimBadge($user, $reference),
            RewardClaimType::Freeze => $this->claimFreeze($user, $reference),
            RewardClaimType::WeeklyPrize => $this->claimWeeklyPrize($user, $reference),
        };
    }

    /**
     * @return list<RewardClaimCard>
     */
    private function claimCards(User $user): array
    {
        $cards = [];
        $today = CarbonImmutable::now()->toDateString();

        if (! $this->claims->exists($user, RewardClaimType::DailyBox, $today)) {
            $cards[] = new RewardClaimCard(
                type: RewardClaimType::DailyBox->value,
                reference: $today,
                emoji: '🎁',
                title: (string) __('rewards.daily_box'),
                subtitle: (string) __('rewards.daily_box_sub', ['xp' => self::DAILY_BOX_XP]),
                action: (string) __('rewards.open_now'),
                buttonClass: 'btn-primary',
            );
        }

        foreach ($this->badges->unseenForUser($user) as $row) {
            $badge = $row->badge;

            if (! $badge instanceof Badge) {
                continue;
            }

            $cards[] = new RewardClaimCard(
                type: RewardClaimType::Badge->value,
                reference: $badge->slug,
                emoji: $badge->emoji !== '' ? $badge->emoji : '🏅',
                title: (string) __('badges.items.'.$badge->slug.'.name'),
                subtitle: (string) __('rewards.badge_just_earned'),
                action: (string) __('rewards.claim'),
                buttonClass: 'btn-secondary',
            );
        }

        foreach ($this->payouts->unclaimedPrizes($user) as $payout) {
            $cards[] = new RewardClaimCard(
                type: RewardClaimType::WeeklyPrize->value,
                reference: (string) $payout->league_week_id,
                emoji: '🎁',
                title: (string) __('rewards.weekly_prize'),
                subtitle: $this->weeklyPrizeSubtitle($payout),
                action: (string) __('rewards.claim'),
                buttonClass: 'btn-primary',
            );
        }

        if ($this->freezePending($user)) {
            $cards[] = new RewardClaimCard(
                type: RewardClaimType::Freeze->value,
                reference: '7',
                emoji: '🛡️',
                title: (string) __('rewards.streak_freeze'),
                subtitle: (string) __('rewards.streak_freeze_sub'),
                action: (string) __('rewards.claim'),
                buttonClass: 'btn-secondary',
            );
        }

        return $cards;
    }

    /**
     * @return list<array{letter: string, xp: int, state: string, emoji: string}>
     */
    private function calendar(User $user): array
    {
        $weekStart = CarbonImmutable::now()->startOfWeek(CarbonImmutable::MONDAY);
        $today = CarbonImmutable::now()->toDateString();
        $table = $this->stats->loginXpTable();
        $claimedToday = $this->statRows->hasXpEvent($user, XpSource::DailyLogin, $today);
        $todayXp = $claimedToday
            ? $this->loginAmountOn($user, $today)
            : $this->stats->nextDailyLoginXp($user);
        $todaySlot = $this->loginSlotForXp($table, $todayXp);
        $days = [];

        for ($i = 0; $i < 7; $i++) {
            $date = $weekStart->addDays($i)->toDateString();
            $iso = $i + 1;
            $letter = $this->stats->calendarLetter($iso);
            $amount = $this->loginAmountOn($user, $date);

            if ($amount > 0) {
                $days[] = [
                    'letter' => $letter,
                    'xp' => $amount,
                    'state' => 'collected',
                    'emoji' => '✓',
                ];

                continue;
            }

            if ($date < $today) {
                $days[] = [
                    'letter' => $letter,
                    'xp' => 0,
                    'state' => '',
                    'emoji' => '🎁',
                ];

                continue;
            }

            if ($date === $today) {
                $days[] = [
                    'letter' => $letter,
                    'xp' => $todayXp,
                    'state' => 'today',
                    'emoji' => '🎁',
                ];

                continue;
            }

            $ahead = (int) CarbonImmutable::parse($today)->diffInDays(CarbonImmutable::parse($date));
            $slot = min(7, $todaySlot + $ahead);
            $xp = $table[$slot] ?? $table[7];

            $days[] = [
                'letter' => $letter,
                'xp' => $xp,
                'state' => '',
                'emoji' => $slot === 7 ? '🏆' : '🎁',
            ];
        }

        return $days;
    }

    /**
     * @param  array<int, int>  $table
     */
    private function loginSlotForXp(array $table, int $xp): int
    {
        $slot = array_search($xp, $table, true);

        return is_int($slot) ? $slot : 1;
    }

    private function calendarProgress(User $user): int
    {
        $weekStart = CarbonImmutable::now()->startOfWeek(CarbonImmutable::MONDAY);
        $count = 0;

        for ($i = 0; $i < 7; $i++) {
            $date = $weekStart->addDays($i)->toDateString();

            if ($this->statRows->hasXpEvent($user, XpSource::DailyLogin, $date)) {
                $count++;
            }
        }

        return $count;
    }

    private function loginClaimedToday(User $user): bool
    {
        return $this->statRows->hasXpEvent($user, XpSource::DailyLogin, CarbonImmutable::now()->toDateString());
    }

    private function loginAmountOn(User $user, string $date): int
    {
        foreach ($this->statRows->xpEventsBetween($user, $date, $date) as $event) {
            if ($event->source === XpSource::DailyLogin && $event->context === $date) {
                return $event->amount;
            }
        }

        return 0;
    }

    /**
     * @return list<array{title: string, subtitle: string, emoji: string, tile: string, amount: int, spent: bool, when: string}>
     */
    private function activity(User $user): array
    {
        $rows = [];

        foreach ($this->statRows->recentXpEvents($user, 5) as $event) {
            $rows[] = [
                'title' => (string) __('rewards.earned_xp', ['xp' => $event->amount]),
                'subtitle' => $event->source->label(),
                'emoji' => $event->source->emoji(),
                'tile' => $event->source->tile(),
                'amount' => $event->amount,
                'spent' => false,
                'when' => $this->relativeWhen($event),
            ];
        }

        return $rows;
    }

    private function relativeWhen(XpEvent $event): string
    {
        $at = $event->created_at;

        if (! $at instanceof CarbonInterface) {
            return (string) __('rewards.today');
        }

        $days = (int) CarbonImmutable::parse($at)->startOfDay()->diffInDays(CarbonImmutable::now()->startOfDay());

        return match (true) {
            $days <= 0 => (string) __('rewards.today'),
            $days === 1 => (string) __('rewards.yesterday'),
            default => (string) __('rewards.days_ago', ['days' => $days]),
        };
    }

    private function freezePending(User $user): bool
    {
        $stat = $this->stats->ensureFor($user);

        return $stat->streak_freezes < 3
            && $this->statRows->hasXpEvent($user, XpSource::StreakMilestone, '7')
            && ! $this->claims->exists($user, RewardClaimType::Freeze, '7');
    }

    private function claimDailyBox(User $user, string $reference): RewardClaimResult
    {
        $today = CarbonImmutable::now()->toDateString();

        if ($reference !== $today) {
            return RewardClaimResult::ignored();
        }

        if (! $this->claims->recordOnce($user, RewardClaimType::DailyBox, $today)) {
            return RewardClaimResult::ignored();
        }

        $this->stats->awardXp(
            $user,
            XpSource::DailyBox,
            self::DAILY_BOX_XP,
            context: $today,
            countsAsPlay: false,
        );

        return new RewardClaimResult(paid: true, xp: self::DAILY_BOX_XP);
    }

    private function claimLogin(User $user, string $reference): RewardClaimResult
    {
        $today = CarbonImmutable::now()->toDateString();

        if ($reference !== $today && $reference !== '') {
            return RewardClaimResult::ignored();
        }

        $xp = $this->stats->awardDailyLogin($user);

        if ($xp < 1) {
            return RewardClaimResult::ignored();
        }

        $this->claims->recordOnce($user, RewardClaimType::DailyLogin, $today);

        return new RewardClaimResult(paid: true, xp: $xp);
    }

    private function claimBadge(User $user, string $slug): RewardClaimResult
    {
        $badge = $this->badges->findBySlug($slug);
        $row = $badge instanceof Badge ? $this->badges->findUserBadge($user, $badge) : null;

        if (! $badge instanceof Badge || ! $row instanceof UserBadge || $row->seen_at !== null) {
            return RewardClaimResult::ignored();
        }

        $this->claims->recordOnce($user, RewardClaimType::Badge, $slug);

        return new RewardClaimResult(paid: false, redirectSlug: $slug);
    }

    private function claimWeeklyPrize(User $user, string $reference): RewardClaimResult
    {
        if (! ctype_digit($reference)) {
            return RewardClaimResult::ignored();
        }

        $payout = $this->payouts->findForWeek($user, (int) $reference);

        if (! $payout instanceof LeagueSeasonPayout || ! $payout->hasPrize() || $payout->prizeClaimed()) {
            return RewardClaimResult::ignored();
        }

        if (! $this->claims->recordOnce($user, RewardClaimType::WeeklyPrize, $reference)) {
            return RewardClaimResult::ignored();
        }

        $xp = (int) $payout->prize_xp;

        if ($xp > 0) {
            $this->stats->awardXp(
                $user,
                XpSource::WeeklyPrize,
                $xp,
                context: 'weekly-prize:'.$reference,
                countsAsPlay: false,
                countsTowardLeague: false,
            );
        }

        $this->payouts->markPrizeClaimed($payout);

        return new RewardClaimResult(paid: true, xp: $xp);
    }

    private function weeklyPrizeSubtitle(LeagueSeasonPayout $payout): string
    {
        $xp = (int) $payout->prize_xp;

        if ($xp > 0) {
            return (string) __('rewards.weekly_prize_xp', ['xp' => $xp, 'rank' => $payout->finish_rank]);
        }

        return (string) __('rewards.weekly_prize_token', ['rank' => $payout->finish_rank]);
    }

    private function claimFreeze(User $user, string $reference): RewardClaimResult
    {
        if ($reference !== '7' || ! $this->freezePending($user)) {
            return RewardClaimResult::ignored();
        }

        if (! $this->claims->recordOnce($user, RewardClaimType::Freeze, '7')) {
            return RewardClaimResult::ignored();
        }

        $this->stats->grantFreeze($user);

        return new RewardClaimResult(paid: true);
    }
}
