<?php

namespace App\Services;

use App\Enums\ParentPinAttempt;
use App\Models\User;
use App\Notifications\AccountDataExportNotification;
use App\Notifications\AccountDeletionCodeNotification;
use App\Notifications\AccountDeletionRequestedNotification;
use App\Notifications\ParentEmailChangeAlertNotification;
use App\Notifications\ParentEmailVerifyNotification;
use App\Repositories\BadgeRepository;
use App\Repositories\FriendshipRepository;
use App\Repositories\LeagueRepository;
use App\Repositories\UserRepository;
use App\Repositories\UserStatRepository;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AccountService
{
    public const GRACE_DAYS = 14;

    public const LINK_TTL_MINUTES = 60;

    public function __construct(
        private UserRepository $users,
        private FriendshipRepository $friends,
        private LeagueRepository $leagues,
        private UserStatRepository $stats,
        private BadgeRepository $badges,
        private ParentZoneService $zone,
        private VerificationCodeService $codes,
        private ProgressReportService $reports,
    ) {}

    public function requestEmailChange(User $user, string $newEmail, string $confirmEmail, string $pin, ?string $ip = null): void
    {
        $newEmail = mb_strtolower(trim($newEmail));
        $confirmEmail = mb_strtolower(trim($confirmEmail));

        if ($newEmail === '' || ! filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'newEmail' => (string) __('account.email_invalid'),
            ]);
        }

        if ($newEmail !== $confirmEmail) {
            throw ValidationException::withMessages([
                'confirmEmail' => (string) __('account.email_mismatch'),
            ]);
        }

        if ($newEmail === mb_strtolower($user->email)) {
            throw ValidationException::withMessages([
                'newEmail' => (string) __('account.email_same'),
            ]);
        }

        if ($this->users->emailTaken($newEmail, $user->id)) {
            throw ValidationException::withMessages([
                'newEmail' => (string) __('account.email_taken'),
            ]);
        }

        $pinResult = $this->zone->confirmPin($user, $pin);

        if ($pinResult === ParentPinAttempt::LockedOut) {
            throw ValidationException::withMessages([
                'currentPin' => (string) __('parent-zone.pin_locked', [
                    'seconds' => $this->zone->lockoutSecondsRemaining($user),
                ]),
            ]);
        }

        if ($pinResult !== ParentPinAttempt::Unlocked) {
            throw ValidationException::withMessages([
                'currentPin' => (string) __('parent-zone.pin_wrong'),
            ]);
        }

        if ($this->tooManyEmailSends($user, $ip)) {
            throw ValidationException::withMessages([
                'newEmail' => (string) __('account.wait_resend'),
            ]);
        }

        $this->issuePendingEmail($user, $newEmail, $ip);
    }

    public function resendPendingEmail(User $user, ?string $ip = null): bool
    {
        $pending = $user->pending_parent_email;

        if (! is_string($pending) || $pending === '') {
            return false;
        }

        if ($this->tooManyEmailSends($user, $ip)) {
            return false;
        }

        $this->issuePendingEmail($user, $pending, $ip);

        return true;
    }

    public function verifyPendingEmailCode(User $user, string $code): bool
    {
        $pending = $user->pending_parent_email;

        if (! is_string($pending) || $pending === '') {
            return false;
        }

        $payload = $this->codes->consume(
            $this->emailCodeKey($user),
            $code,
            'parent-email-attempt:'.$user->id,
        );

        if ($payload === null) {
            return false;
        }

        $this->applyPendingEmail($user);

        return true;
    }

    public function confirmPendingEmail(User $user, string $token): bool
    {
        $stored = $user->pending_parent_email_token;
        $pending = $user->pending_parent_email;

        if (! is_string($stored) || $stored === '' || ! is_string($pending) || $pending === '') {
            return false;
        }

        if (! hash_equals($stored, hash('sha256', $token))) {
            return false;
        }

        $this->applyPendingEmail($user);

        return true;
    }

    public function setWeeklyEmail(User $user, bool $enabled): User
    {
        return $this->reports->setWeeklyEmail($user, $enabled);
    }

    public function sendDeletionCode(User $user, ?string $ip = null): bool
    {
        if ($this->tooManyDeletionSends($user, $ip)) {
            return false;
        }

        $this->hitDeletionSendLimiters($user, $ip);

        $code = $this->codes->generate();
        $this->codes->store($this->deletionCodeKey($user), $code, [
            'user_id' => $user->id,
        ]);

        $user->notify(new AccountDeletionCodeNotification($code, $user->name));

        return true;
    }

    public function verifyDeletionCode(User $user, string $code): bool
    {
        $payload = $this->codes->consume(
            $this->deletionCodeKey($user),
            $code,
            'account-delete-attempt:'.$user->id,
        );

        return $payload !== null;
    }

    public function requestDeletion(User $user): User
    {
        $this->friends->deleteAllFor($user);
        $this->leagues->removeUser($user);

        $updated = $this->users->update($user, [
            'show_on_leaderboard' => false,
            'allow_friend_requests' => false,
            'deletion_requested_at' => now(),
        ]);

        $this->users->deleteSessions($updated);
        $deleted = $this->users->softDelete($updated);

        $purgeOn = now()->addDays(self::GRACE_DAYS)->timezone($deleted->timezone ?: 'Asia/Tbilisi');

        Notification::route('mail', $deleted->email)
            ->notify(new AccountDeletionRequestedNotification(
                $deleted->name,
                $purgeOn->translatedFormat('j F Y'),
            ));

        return $deleted;
    }

    public function purgeDue(?CarbonInterface $now = null): int
    {
        $cutoff = ($now ?? now())->subDays(self::GRACE_DAYS);
        $purged = 0;

        foreach ($this->users->dueForPurge($cutoff) as $user) {
            $this->friends->deleteAllFor($user);
            $this->leagues->removeUser($user);
            $this->users->forceDelete($user);
            $purged++;
        }

        return $purged;
    }

    public function emailDataExport(User $user): void
    {
        $user->notify(new AccountDataExportNotification($this->exportPayload($user)));
    }

    /**
     * @return array<string, mixed>
     */
    public function exportPayload(User $user): array
    {
        $stat = $this->stats->firstOrCreateFor($user);
        $from = $user->created_at?->toDateString() ?? now()->toDateString();
        $xpByDay = $this->stats->xpByDateBetween($user, $from, now()->toDateString());

        $badges = [];

        foreach ($this->badges->forUser($user) as $row) {
            $badge = $row->badge;
            $badges[] = [
                'slug' => $badge?->slug,
                'unlocked_at' => $row->unlocked_at?->toIso8601String(),
            ];
        }

        $friends = [];

        foreach ($this->friends->acceptedFriends($user) as $friend) {
            $friends[] = $friend->nickname;
        }

        return [
            'exported_at' => now()->toIso8601String(),
            'kid_name' => $user->name,
            'nickname' => $user->nickname,
            'age' => $user->age,
            'grade' => $user->grade?->value,
            'parent_email' => $user->email,
            'stats' => [
                'xp' => $stat->xp,
                'current_streak' => $stat->current_streak,
                'longest_streak' => $stat->longest_streak,
                'league' => $stat->league->value,
            ],
            'activity_xp_by_day' => $xpByDay,
            'badges' => $badges,
            'friends' => $friends,
        ];
    }

    private function issuePendingEmail(User $user, string $newEmail, ?string $ip): void
    {
        $plain = Str::random(64);

        $this->users->update($user, [
            'pending_parent_email' => $newEmail,
            'pending_parent_email_token' => hash('sha256', $plain),
            'pending_parent_email_sent_at' => now(),
        ]);

        $code = $this->codes->generate();
        $this->codes->store($this->emailCodeKey($user), $code, [
            'user_id' => $user->id,
        ]);

        $url = URL::temporarySignedRoute(
            'parent-email.confirm',
            now()->addMinutes(self::LINK_TTL_MINUTES),
            ['user' => $user->id, 'token' => $plain],
        );

        $this->hitEmailSendLimiters($user, $ip);

        Notification::route('mail', $newEmail)
            ->notify(new ParentEmailVerifyNotification($code, $url, $user->name));

        $user->notify(new ParentEmailChangeAlertNotification($user->name, $newEmail));
    }

    private function applyPendingEmail(User $user): void
    {
        $pending = $user->pending_parent_email;

        if (! is_string($pending) || $pending === '') {
            return;
        }

        $this->users->update($user, [
            'email' => $pending,
            'email_verified_at' => now(),
            'pending_parent_email' => null,
            'pending_parent_email_token' => null,
            'pending_parent_email_sent_at' => null,
        ]);

        $this->codes->forget($this->emailCodeKey($user));
    }

    private function emailCodeKey(User $user): string
    {
        return 'parent-email:'.$user->id;
    }

    private function deletionCodeKey(User $user): string
    {
        return 'account-delete:'.$user->id;
    }

    private function tooManyEmailSends(User $user, ?string $ip): bool
    {
        if (RateLimiter::tooManyAttempts($this->emailSendKey($user), 1)) {
            return true;
        }

        return is_string($ip) && $ip !== '' && RateLimiter::tooManyAttempts($this->emailIpKey($ip), 5);
    }

    private function hitEmailSendLimiters(User $user, ?string $ip): void
    {
        RateLimiter::hit($this->emailSendKey($user), 30);

        if (is_string($ip) && $ip !== '') {
            RateLimiter::hit($this->emailIpKey($ip), 3600);
        }
    }

    private function emailSendKey(User $user): string
    {
        return 'parent-email-send:'.$user->id;
    }

    private function emailIpKey(string $ip): string
    {
        return 'parent-email-send-ip:'.$ip;
    }

    private function tooManyDeletionSends(User $user, ?string $ip): bool
    {
        if (RateLimiter::tooManyAttempts($this->deletionSendKey($user), 1)) {
            return true;
        }

        return is_string($ip) && $ip !== '' && RateLimiter::tooManyAttempts($this->deletionIpKey($ip), 5);
    }

    private function hitDeletionSendLimiters(User $user, ?string $ip): void
    {
        RateLimiter::hit($this->deletionSendKey($user), 30);

        if (is_string($ip) && $ip !== '') {
            RateLimiter::hit($this->deletionIpKey($ip), 3600);
        }
    }

    private function deletionSendKey(User $user): string
    {
        return 'account-delete-send:'.$user->id;
    }

    private function deletionIpKey(string $ip): string
    {
        return 'account-delete-send-ip:'.$ip;
    }
}
