<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\ParentVerificationNotification;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;

class ParentVerificationService
{
    public function __construct(
        private UserRepository $users,
        private VerificationCodeService $codes,
    ) {}

    public function send(User $user, bool $forceNewCode = false): void
    {
        if (! $forceNewCode && $this->hasPendingCode($user)) {
            return;
        }

        $code = $this->codes->generate();
        $this->codes->store($this->cacheKey($user), $code);

        $url = URL::temporarySignedRoute(
            'parent-verify.confirm',
            now()->addMinutes(VerificationCodeService::TTL_MINUTES),
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
        $payload = $this->codes->consume(
            $this->cacheKey($user),
            $code,
            'parent-verify-attempt:'.$user->id,
        );

        if ($payload === null) {
            return false;
        }

        $this->markVerified($user);

        return true;
    }

    public function markVerified(User $user): User
    {
        $verified = $this->users->markEmailVerified($user);
        $this->codes->forget($this->cacheKey($user));

        return $verified;
    }

    public function hasPendingCode(User $user): bool
    {
        return $this->codes->has($this->cacheKey($user));
    }

    private function cacheKey(User $user): string
    {
        return 'parent-verify:'.$user->id;
    }
}
