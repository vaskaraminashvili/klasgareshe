<?php

namespace App\Services;

use App\Data\ParentControlsSnapshot;
use App\Enums\DailyGoal;
use App\Enums\ParentPinAttempt;
use App\Enums\SchoolGrade;
use App\Enums\SchoolSubject;
use App\Models\User;
use App\Notifications\ParentPinResetNotification;
use App\Repositories\UserRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class ParentZoneService
{
    public const IDLE_MINUTES = 15;

    public const MAX_ATTEMPTS = 5;

    public const LOCKOUT_SECONDS = 60;

    public const RESET_TTL_MINUTES = 10;

    public const SESSION_UNLOCKED_AT = 'parent_zone.unlocked_at';

    public const SESSION_RESET_PENDING = 'parent_zone.reset_pending';

    public const SESSION_RESET_VERIFIED_AT = 'parent_zone.reset_verified_at';

    /** @var list<string> */
    public const PARENT_ROUTES = [
        'parent-controls',
        'change-pin',
        'preferred-subjects',
        'parent-pin-otp',
    ];

    public function __construct(
        private UserRepository $users,
        private UserStatService $stats,
        private WeekPlanService $week,
        private VerificationCodeService $codes,
    ) {}

    public function hasPin(User $user): bool
    {
        $pin = $user->parent_pin;

        return is_string($pin) && $pin !== '';
    }

    public function isUnlocked(): bool
    {
        $at = $this->unlockedAt();

        if ($at === null) {
            return false;
        }

        return $at->greaterThan(now()->subMinutes(self::IDLE_MINUTES));
    }

    public function unlock(): void
    {
        $this->touch();
    }

    public function touch(): void
    {
        session([self::SESSION_UNLOCKED_AT => now()->toIso8601String()]);
    }

    public function lock(): void
    {
        session()->forget(self::SESSION_UNLOCKED_AT);
    }

    public function lockIfOutsideParentZone(string $routeName): void
    {
        if (in_array($routeName, self::PARENT_ROUTES, true)) {
            return;
        }

        $this->lock();
    }

    public function canSetPinWithoutCurrent(): bool
    {
        $raw = session(self::SESSION_RESET_VERIFIED_AT);

        if (! is_string($raw) || $raw === '') {
            return false;
        }

        $at = $this->parseTime($raw);

        return $at !== null && $at->greaterThan(now()->subMinutes(self::RESET_TTL_MINUTES));
    }

    public function hasResetChallenge(): bool
    {
        return session(self::SESSION_RESET_PENDING) === true || $this->canSetPinWithoutCurrent();
    }

    public function lockoutSecondsRemaining(User $user): int
    {
        if (! RateLimiter::tooManyAttempts($this->attemptKey($user), self::MAX_ATTEMPTS)) {
            return 0;
        }

        return RateLimiter::availableIn($this->attemptKey($user));
    }

    public function attemptUnlock(User $user, string $pin): ParentPinAttempt
    {
        $result = $this->checkPin($user, $pin);

        if ($result === ParentPinAttempt::Unlocked) {
            $this->unlock();
        }

        return $result;
    }

    public function createPin(User $user, string $pin, string $confirm): ParentPinAttempt
    {
        if (! $this->isFourDigits($pin) || $pin !== $confirm) {
            return ParentPinAttempt::Mismatch;
        }

        $this->storePin($user, $pin);
        $this->unlock();

        return ParentPinAttempt::Created;
    }

    public function changePin(User $user, string $current, string $pin, string $confirm): ParentPinAttempt
    {
        if ($this->canSetPinWithoutCurrent()) {
            return $this->createPin($user, $pin, $confirm);
        }

        $check = $this->checkPin($user, $current);

        if ($check !== ParentPinAttempt::Unlocked) {
            return $check;
        }

        return $this->createPin($user, $pin, $confirm);
    }

    public function requestResetCode(User $user, ?string $ip = null): bool
    {
        if ($this->tooManySends($user, $ip)) {
            return false;
        }

        $this->hitSendLimiters($user, $ip);

        $code = $this->codes->generate();
        $this->codes->store($this->cacheKey($user), $code, [
            'user_id' => $user->id,
        ]);

        Notification::send($user, new ParentPinResetNotification($code));
        session([self::SESSION_RESET_PENDING => true]);

        return true;
    }

    public function verifyResetCode(User $user, string $code): bool
    {
        $payload = $this->codes->consume(
            $this->cacheKey($user),
            $code,
            'parent-pin-reset-attempt:'.$user->id,
        );

        if ($payload === null) {
            return false;
        }

        session()->forget(self::SESSION_RESET_PENDING);
        session([self::SESSION_RESET_VERIFIED_AT => now()->toIso8601String()]);

        return true;
    }

    public function maskedEmail(User $user): string
    {
        $email = $user->email;

        if (! str_contains($email, '@')) {
            return '';
        }

        [$local, $domain] = explode('@', $email, 2);
        $visible = mb_substr($local, 0, 1);

        return $visible.str_repeat('*', max(1, mb_strlen($local) - 1)).'@'.$domain;
    }

    public function dashboard(User $user): ParentControlsSnapshot
    {
        $snap = $this->stats->profileSnapshot($user, $this->week->lessonsCompletedThisWeek($user));
        $goal = $user->daily_goal ?? DailyGoal::Regular;
        $subjects = is_array($user->favourite_subjects) ? $user->favourite_subjects : [];
        $labels = [];

        foreach ($subjects as $value) {
            $subject = SchoolSubject::tryFrom($value);

            if ($subject instanceof SchoolSubject) {
                $labels[] = $subject->label();
            }
        }

        $pinSetAt = $user->parent_pin_set_at;
        $pinChangedLabel = $pinSetAt === null
            ? (string) __('parent-zone.pin_never')
            : (string) __('parent-zone.pin_changed', ['date' => $pinSetAt->format('d.m.Y')]);

        return new ParentControlsSnapshot(
            kidName: $user->name,
            age: $user->age ?? 0,
            gradeLabel: ($user->grade ?? SchoolGrade::First)->label(),
            weekXp: $snap->weekXp,
            weekActiveDays: $snap->weekActiveDays,
            weekLessons: $snap->weekLessons,
            weekRangeLabel: $snap->weekRangeLabel,
            email: $user->email,
            emailVerified: $user->email_verified_at !== null,
            allowFriendRequests: (bool) $user->allow_friend_requests,
            showOnLeaderboard: (bool) $user->show_on_leaderboard,
            subjectLabels: $labels,
            pinChangedLabel: $pinChangedLabel,
            dailyGoalMinutes: $goal->minutes(),
            hasPin: $this->hasPin($user),
        );
    }

    public function pinStrength(string $pin): string
    {
        if (! $this->isFourDigits($pin)) {
            return 'weak';
        }

        $unique = $pin !== '1234';
        $noSeq = ! $this->isSequence($pin);
        $noRepeat = ! preg_match('/^(\d)\1{3}$/', $pin);

        if ($unique && $noSeq && $noRepeat) {
            return 'strong';
        }

        if ((int) $unique + (int) $noSeq + (int) $noRepeat >= 2) {
            return 'ok';
        }

        return 'weak';
    }

    /**
     * @return array{unique: bool, noseq: bool, norepeat: bool}
     */
    public function pinRules(string $pin): array
    {
        return [
            'unique' => $this->isFourDigits($pin) && $pin !== '1234',
            'noseq' => $this->isFourDigits($pin) && ! $this->isSequence($pin),
            'norepeat' => $this->isFourDigits($pin) && preg_match('/^(\d)\1{3}$/', $pin) !== 1,
        ];
    }

    private function checkPin(User $user, string $pin): ParentPinAttempt
    {
        $key = $this->attemptKey($user);

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return ParentPinAttempt::LockedOut;
        }

        if (! $this->isFourDigits($pin) || ! $this->hasPin($user) || ! Hash::check($pin, (string) $user->parent_pin)) {
            RateLimiter::hit($key, self::LOCKOUT_SECONDS);

            return ParentPinAttempt::Invalid;
        }

        RateLimiter::clear($key);

        return ParentPinAttempt::Unlocked;
    }

    private function storePin(User $user, string $pin): void
    {
        if (! $this->isFourDigits($pin)) {
            throw ValidationException::withMessages([
                'pin' => (string) __('parent-zone.pin_invalid'),
            ]);
        }

        $this->users->update($user, [
            'parent_pin' => $pin,
            'parent_pin_set_at' => now(),
        ]);
        RateLimiter::clear($this->attemptKey($user));
        session()->forget([self::SESSION_RESET_PENDING, self::SESSION_RESET_VERIFIED_AT]);
    }

    private function isFourDigits(string $pin): bool
    {
        return preg_match('/^\d{4}$/', $pin) === 1;
    }

    private function isSequence(string $pin): bool
    {
        $asc = '0123456789';
        $desc = '9876543210';

        return str_contains($asc, $pin) || str_contains($desc, $pin);
    }

    private function unlockedAt(): ?CarbonImmutable
    {
        $raw = session(self::SESSION_UNLOCKED_AT);

        return is_string($raw) ? $this->parseTime($raw) : null;
    }

    private function parseTime(string $raw): ?CarbonImmutable
    {
        if (strtotime($raw) === false) {
            return null;
        }

        return CarbonImmutable::parse($raw);
    }

    private function attemptKey(User $user): string
    {
        return 'parent-pin:'.$user->id;
    }

    private function cacheKey(User $user): string
    {
        return 'parent-pin-reset:'.$user->id;
    }

    private function sendKey(User $user): string
    {
        return 'parent-pin-reset-send:'.$user->id;
    }

    private function ipKey(string $ip): string
    {
        return 'parent-pin-reset-send-ip:'.$ip;
    }

    private function tooManySends(User $user, ?string $ip): bool
    {
        if (RateLimiter::tooManyAttempts($this->sendKey($user), 1)) {
            return true;
        }

        if (is_string($ip) && $ip !== '' && RateLimiter::tooManyAttempts($this->ipKey($ip), 5)) {
            return true;
        }

        return false;
    }

    private function hitSendLimiters(User $user, ?string $ip): void
    {
        RateLimiter::hit($this->sendKey($user), 30);

        if (is_string($ip) && $ip !== '') {
            RateLimiter::hit($this->ipKey($ip), 3600);
        }
    }
}
