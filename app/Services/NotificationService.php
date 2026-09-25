<?php

namespace App\Services;

use App\Data\AlertCard;
use App\Enums\AlertType;
use App\Enums\League;
use App\Enums\ReminderTime;
use App\Models\Badge;
use App\Models\User;
use App\Notifications\KidzioAlert;
use App\Repositories\AppNotificationRepository;
use App\Repositories\PushSubscriptionRepository;
use App\Repositories\UserRepository;
use App\Repositories\UserStatRepository;
use App\Repositories\WeekPlanRepository;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Route;
use Throwable;

class NotificationService
{
    private const REMINDER_WINDOW_MINUTES = 14;

    public function __construct(
        private AppNotificationRepository $alerts,
        private PushSubscriptionRepository $push,
        private KidSetupService $setup,
        private ScreenTimeService $screenTime,
        private UserStatRepository $stats,
        private WeekPlanRepository $plans,
        private UserRepository $users,
    ) {}

    public function wants(User $user, AlertType $type): bool
    {
        $prefs = $this->normalizedPrefs($user);

        return $prefs[$type->preferenceKey()] ?? false;
    }

    public function wantsAnyDelivery(User $user): bool
    {
        foreach (AlertType::cases() as $type) {
            if ($this->wants($user, $type)) {
                return true;
            }
        }

        return false;
    }

    public function unreadCount(User $user): int
    {
        return $this->alerts->unreadCount($user);
    }

    /**
     * @return list<AlertCard>
     */
    public function latest(User $user): array
    {
        $cards = [];

        foreach ($this->alerts->latest($user) as $row) {
            $cards[] = $this->cardFrom($row);
        }

        return $cards;
    }

    public function markRead(User $user, string $id): ?string
    {
        $row = $this->alerts->markRead($user, $id);

        return $row !== null ? $this->hrefFrom($row) : null;
    }

    public function markAllRead(User $user): void
    {
        $this->alerts->markAllRead($user);
    }

    public function subscribe(User $user, string $endpoint, string $publicKey, string $authToken, string $encoding = 'aes128gcm'): void
    {
        $this->push->upsert($user, $endpoint, $publicKey, $authToken, $encoding);
    }

    public function unsubscribe(User $user, string $endpoint): void
    {
        $this->push->deleteByEndpoint($user, $endpoint);
    }

    public function badgeUnlocked(User $user, Badge $badge): void
    {
        $name = (string) __('badges.items.'.$badge->slug.'.name');

        $this->dispatch(
            $user,
            AlertType::BadgeUnlocked,
            ['name' => $name],
            dedupe: 'badge:'.$badge->slug,
        );
    }

    public function leagueMoved(User $user, League $league, bool $promoted): void
    {
        $this->dispatch(
            $user,
            $promoted ? AlertType::LeaguePromoted : AlertType::LeagueRelegated,
            ['league' => $league->label()],
            dedupe: 'league:'.$league->value.':'.now()->toDateString(),
        );
    }

    public function friendRequested(User $target, User $from): void
    {
        $this->dispatch(
            $target,
            AlertType::FriendRequest,
            ['name' => $from->name],
            dedupe: 'friend-request:'.$from->id,
        );
    }

    public function friendAccepted(User $target, User $from): void
    {
        $this->dispatch(
            $target,
            AlertType::FriendAccepted,
            ['name' => $from->name],
            dedupe: 'friend:'.$from->id,
        );
    }

    public function newWeekUnlocked(User $user, int $weekNumber): void
    {
        $this->dispatch(
            $user,
            AlertType::NewWeek,
            ['n' => $weekNumber],
            dedupe: 'new_week:'.$weekNumber,
        );
    }

    /**
     * Streak-at-risk and daily-mission reminders at each account's reminder time.
     */
    public function sendDueReminders(?CarbonInterface $now = null): int
    {
        $nowUtc = CarbonImmutable::instance($now ?? now());
        $sent = 0;

        foreach ($this->users->verifiedLearners() as $user) {
            if (! $this->isInReminderWindow($user, $nowUtc)) {
                continue;
            }

            if ($this->isQuietNow($user, $nowUtc)) {
                continue;
            }

            $local = $nowUtc->setTimezone($this->screenTime->timezone($user));
            $today = $local->toDateString();

            if ($this->wants($user, AlertType::StreakAtRisk) && $this->streakIsAtRisk($user, $today)) {
                $stat = $this->stats->firstOrCreateFor($user);
                if ($this->dispatch(
                    $user,
                    AlertType::StreakAtRisk,
                    ['n' => $stat->current_streak],
                    dedupe: 'streak_at_risk:'.$today,
                    at: $nowUtc,
                )) {
                    $sent++;
                }
            }

            if ($this->wants($user, AlertType::MissionReady) && $this->missionIsOpen($user)) {
                if ($this->dispatch(
                    $user,
                    AlertType::MissionReady,
                    [],
                    dedupe: 'mission_ready:'.$today,
                    at: $nowUtc,
                )) {
                    $sent++;
                }
            }
        }

        return $sent;
    }

    /**
     * @param  array<string, mixed>  $replace
     * @param  array<string, mixed>  $routeParams
     */
    public function dispatch(
        User $user,
        AlertType $type,
        array $replace = [],
        ?string $dedupe = null,
        array $routeParams = [],
        bool $allowPush = true,
        ?CarbonInterface $at = null,
    ): bool {
        if (! $this->wants($user, $type)) {
            return false;
        }

        if ($dedupe !== null && $this->alerts->existsDedupe($user, $dedupe)) {
            return false;
        }

        $title = (string) __('alerts.'.$type->value.'.title', $replace);
        $body = (string) __('alerts.'.$type->value.'.body', $replace);

        $this->alerts->send($user, new KidzioAlert(
            type: $type,
            title: $title,
            body: $body,
            routeName: $type->routeName(),
            routeParams: $routeParams,
            dedupe: $dedupe,
            url: $this->urlFor($type, $routeParams),
            withPush: $allowPush && ! $this->isQuietNow($user, $at),
        ));

        return true;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function urlFor(AlertType $type, array $params = []): string
    {
        $name = $type->routeName();

        if (! Route::has($name)) {
            return url('/');
        }

        try {
            return route($name, $params);
        } catch (Throwable) {
            return url('/');
        }
    }

    private function cardFrom(DatabaseNotification $row): AlertCard
    {
        $type = AlertType::tryFrom($this->stringFromData($row, 'type'));

        return new AlertCard(
            id: (string) $row->id,
            title: $this->stringFromData($row, 'title'),
            body: $this->stringFromData($row, 'body'),
            href: $this->hrefFrom($row),
            icon: $type?->icon() ?? 'ph-bell',
            tile: $type?->tile() ?? $this->stringFromData($row, 'tile', 'tile-sky'),
            when: $this->relativeWhen($row->created_at ?? now()),
            unread: $row->read_at === null,
        );
    }

    private function hrefFrom(DatabaseNotification $row): string
    {
        $name = $this->stringFromData($row, 'route', 'home');
        $params = $row->data['params'] ?? [];

        if (! is_array($params)) {
            $params = [];
        }

        if (! Route::has($name)) {
            return route('home');
        }

        try {
            return route($name, $params);
        } catch (Throwable) {
            return route('home');
        }
    }

    private function stringFromData(DatabaseNotification $row, string $key, string $default = ''): string
    {
        $value = $row->data[$key] ?? $default;

        return is_string($value) ? $value : $default;
    }

    private function relativeWhen(CarbonInterface $at): string
    {
        $seconds = (int) abs($at->diffInSeconds(now()));

        if ($seconds < 60) {
            return (string) __('alerts.just_now');
        }

        $minutes = (int) floor($seconds / 60);

        if ($minutes < 60) {
            return (string) __('alerts.minutes_ago', ['n' => $minutes]);
        }

        $hours = (int) floor($minutes / 60);

        if ($hours < 24) {
            return (string) __('alerts.hours_ago', ['n' => $hours]);
        }

        $days = (int) floor($hours / 24);

        if ($days === 1) {
            return (string) __('alerts.yesterday');
        }

        return (string) __('alerts.days_ago', ['n' => $days]);
    }

    private function isInReminderWindow(User $user, CarbonImmutable $nowUtc): bool
    {
        $local = $nowUtc->setTimezone($this->screenTime->timezone($user));
        $target = ($user->reminder_time ?? ReminderTime::Evening)->minutesPastMidnight();
        $nowMin = ($local->hour * 60) + $local->minute;
        $delta = abs($nowMin - $target);
        $wrap = min($delta, (24 * 60) - $delta);

        return $wrap <= self::REMINDER_WINDOW_MINUTES;
    }

    private function isQuietNow(User $user, ?CarbonInterface $at = null): bool
    {
        $prefs = $this->normalizedPrefs($user);

        if (! ($prefs['quiet_hours'] ?? true)) {
            return false;
        }

        return $this->screenTime->isBedtime($user, $at);
    }

    private function streakIsAtRisk(User $user, string $today): bool
    {
        $stat = $this->stats->firstOrCreateFor($user);

        if ($stat->current_streak < 1) {
            return false;
        }

        return $stat->last_played_on?->toDateString() !== $today;
    }

    private function missionIsOpen(User $user): bool
    {
        return count($this->plans->subjectsCompletedToday($user)) < WeekPlanService::MISSION_TOTAL;
    }

    /**
     * @return array<string, bool>
     */
    private function normalizedPrefs(User $user): array
    {
        $saved = $user->notification_preferences;

        return $this->setup->normalizeNotificationPreferences(is_array($saved) ? $saved : []);
    }
}
