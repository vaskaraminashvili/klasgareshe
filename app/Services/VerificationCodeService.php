<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class VerificationCodeService
{
    public const LENGTH = 6;

    public const TTL_MINUTES = 10;

    public function generate(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public function store(string $key, string $code, array $extra = []): void
    {
        Cache::put($key, array_merge($extra, [
            'hash' => Hash::make($code),
        ]), now()->addMinutes(self::TTL_MINUTES));
    }

    public function has(string $key): bool
    {
        return Cache::has($key);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function payload(string $key): ?array
    {
        $payload = Cache::get($key);

        return is_array($payload) ? $payload : null;
    }

    public function forget(string $key): void
    {
        Cache::forget($key);
    }

    /**
     * Check the code, consume it on success, and throttle failed attempts.
     *
     * @return array<string, mixed>|null
     */
    public function consume(string $key, string $code, string $attemptKey, int $maxAttempts = 5): ?array
    {
        if (RateLimiter::tooManyAttempts($attemptKey, $maxAttempts)) {
            return null;
        }

        $payload = $this->payload($key);
        $hash = is_array($payload) ? ($payload['hash'] ?? null) : null;

        if (! is_string($hash) || ! Hash::check($code, $hash)) {
            RateLimiter::hit($attemptKey, 60);

            return null;
        }

        RateLimiter::clear($attemptKey);
        $this->forget($key);

        return $payload;
    }
}
