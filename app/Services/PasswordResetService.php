<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\PasswordResetNotification;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;

class PasswordResetService
{
    public const SESSION_EMAIL = 'password_reset.email';

    public const SESSION_USER_ID = 'password_reset.user_id';

    public function __construct(
        private UserRepository $users,
        private VerificationCodeService $codes,
    ) {}

    /**
     * Always records the email so the OTP screen can open. A code is mailed only
     * when that address belongs to an account and the sender is not throttled.
     */
    public function request(string $email, ?string $ip = null): bool
    {
        $email = mb_strtolower(trim($email));
        session([self::SESSION_EMAIL => $email]);
        session()->forget(self::SESSION_USER_ID);

        if ($this->tooManySends($email, $ip)) {
            return false;
        }

        $this->hitSendLimiters($email, $ip);

        $user = $this->users->findByEmail($email);

        if (! $user instanceof User) {
            return true;
        }

        $this->issueCode($user, $email);

        return true;
    }

    public function resend(?string $ip = null): bool
    {
        $email = $this->requestedEmail();

        if ($email === null) {
            return false;
        }

        return $this->request($email, $ip);
    }

    public function verifyCode(string $code): bool
    {
        $email = $this->requestedEmail();

        if ($email === null) {
            return false;
        }

        $payload = $this->codes->consume(
            $this->cacheKey($email),
            $code,
            'password-reset-attempt:'.$email,
        );

        if ($payload === null) {
            return false;
        }

        $userId = $payload['user_id'] ?? null;

        if (! is_numeric($userId)) {
            return false;
        }

        session([self::SESSION_USER_ID => (int) $userId]);

        return true;
    }

    public function applyNewPassword(string $password): ?User
    {
        $userId = session(self::SESSION_USER_ID);

        if (! is_numeric($userId)) {
            return null;
        }

        $user = $this->users->findOrFail((int) $userId);
        $updated = $this->users->update($user, ['password' => $password]);
        $rotated = $this->users->rotateRememberToken($updated);
        $this->clearChallenge();

        return $rotated;
    }

    public function forgetOtherSessions(User $user, string $keepSessionId): void
    {
        $this->users->deleteOtherSessions($user, $keepSessionId);
    }

    public function requestedEmail(): ?string
    {
        $email = session(self::SESSION_EMAIL);

        return is_string($email) && $email !== '' ? $email : null;
    }

    public function maskedEmail(): string
    {
        $email = $this->requestedEmail() ?? '';

        if (! str_contains($email, '@')) {
            return '';
        }

        [$local, $domain] = explode('@', $email, 2);
        $visible = mb_substr($local, 0, 1);

        return $visible.str_repeat('*', max(1, mb_strlen($local) - 1)).'@'.$domain;
    }

    public function hasVerifiedChallenge(): bool
    {
        return is_numeric(session(self::SESSION_USER_ID));
    }

    public function clearChallenge(): void
    {
        $email = $this->requestedEmail();

        if ($email !== null) {
            $this->codes->forget($this->cacheKey($email));
        }

        session()->forget([self::SESSION_EMAIL, self::SESSION_USER_ID]);
    }

    private function issueCode(User $user, string $email): void
    {
        $code = $this->codes->generate();
        $this->codes->store($this->cacheKey($email), $code, [
            'user_id' => $user->id,
        ]);

        Notification::send($user, new PasswordResetNotification($code));
    }

    private function cacheKey(string $email): string
    {
        return 'password-reset:'.$email;
    }

    private function tooManySends(string $email, ?string $ip): bool
    {
        if (RateLimiter::tooManyAttempts($this->sendKey($email), 1)) {
            return true;
        }

        if (is_string($ip) && $ip !== '' && RateLimiter::tooManyAttempts($this->ipKey($ip), 5)) {
            return true;
        }

        return false;
    }

    private function hitSendLimiters(string $email, ?string $ip): void
    {
        RateLimiter::hit($this->sendKey($email), 30);

        if (is_string($ip) && $ip !== '') {
            RateLimiter::hit($this->ipKey($ip), 3600);
        }
    }

    private function sendKey(string $email): string
    {
        return 'password-reset-send:'.$email;
    }

    private function ipKey(string $ip): string
    {
        return 'password-reset-send-ip:'.$ip;
    }
}
