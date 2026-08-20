<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\ParentVerificationNotification;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;

class ParentVerificationService
{
    public function __construct(private UserRepository $users) {}

    public function send(User $user, bool $forceNewCode = false): void
    {
        if (! $forceNewCode && $this->hasPendingCode($user)) {
            return;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->storeCode($user, $code);

        $url = URL::temporarySignedRoute(
            'parent-verify.confirm',
            now()->addMinutes(10),
            ['user' => $user->id],
        );

        Notification::send($user, new ParentVerificationNotification($code, $url));
    }

    public function resend(User $user): bool
    {
        $key = 'parent-verify-resend:'.$user->id;

        if (RateLimiter::tooManyAttempts($key, 1)) {
            return false;
        }

        RateLimiter::hit($key, 30);
        $this->send($user, true);

        return true;
    }

    public function verifyCode(User $user, string $code): bool
    {
        $attemptKey = 'parent-verify-attempt:'.$user->id;

        if (RateLimiter::tooManyAttempts($attemptKey, 5)) {
            return false;
        }

        $payload = Cache::get($this->cacheKey($user));

        if (! is_array($payload) || ! isset($payload['hash']) || ! is_string($payload['hash']) || ! Hash::check($code, $payload['hash'])) {
            RateLimiter::hit($attemptKey, 60);

            return false;
        }

        RateLimiter::clear($attemptKey);
        $this->markVerified($user);
        Cache::forget($this->cacheKey($user));

        return true;
    }

    public function markVerified(User $user): User
    {
        $verified = $this->users->markEmailVerified($user);
        Cache::forget($this->cacheKey($user));

        return $verified;
    }

    public function hasPendingCode(User $user): bool
    {
        return Cache::has($this->cacheKey($user));
    }

    private function storeCode(User $user, string $code): void
    {
        Cache::put($this->cacheKey($user), [
            'hash' => Hash::make($code),
        ], now()->addMinutes(10));
    }

    private function cacheKey(User $user): string
    {
        return 'parent-verify:'.$user->id;
    }
}
