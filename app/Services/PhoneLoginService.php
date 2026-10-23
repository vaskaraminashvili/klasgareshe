<?php

namespace App\Services;

use App\Data\PhoneVerifyResult;
use App\Enums\PhoneCodeChannel;
use App\Models\User;
use App\Notifications\PhoneLoginCodeNotification;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class PhoneLoginService
{
    public const SESSION_PHONE = 'phone_login.phone';

    public const SESSION_VERIFIED = 'phone_login.verified';

    public function __construct(
        private UserRepository $users,
        private UserRegistrationService $registration,
        private VerificationCodeService $codes,
    ) {}

    public function requestCode(string $phone, ?string $ip = null): PhoneCodeChannel
    {
        $phone = $this->normalize($phone);

        if (! preg_match('/^\+[1-9]\d{7,14}$/', $phone)) {
            throw ValidationException::withMessages([
                'phone' => (string) __('login.phone_invalid'),
            ]);
        }

        $driver = config('services.sms.driver');

        if (is_string($driver) && $driver !== '' && $driver !== 'log') {
            throw ValidationException::withMessages([
                'phone' => (string) __('login.sms_driver_unavailable', ['driver' => $driver]),
            ]);
        }

        if ($this->tooManySends($phone, $ip)) {
            throw ValidationException::withMessages([
                'phone' => (string) __('login.phone_throttled'),
            ]);
        }

        $this->hitSendLimiters($phone, $ip);

        $trashed = $this->users->findWithTrashedByPhone($phone);

        if ($trashed instanceof User && $trashed->trashed()) {
            throw ValidationException::withMessages([
                'phone' => (string) __('login.account_pending_deletion'),
            ]);
        }

        $user = $this->users->findByPhone($phone);
        $code = $this->codes->generate();
        $this->codes->store($this->cacheKey($phone), $code, [
            'phone' => $phone,
            'user_id' => $user?->id,
        ]);

        session([
            self::SESSION_PHONE => $phone,
            self::SESSION_VERIFIED => false,
        ]);

        if ($user instanceof User) {
            Notification::send($user, new PhoneLoginCodeNotification($code));

            return PhoneCodeChannel::Mail;
        }

        Notification::route('mail', $this->logMailbox())
            ->notify(new PhoneLoginCodeNotification($code, $phone, true));

        return PhoneCodeChannel::Log;
    }

    public function verify(string $code): PhoneVerifyResult
    {
        $phone = session(self::SESSION_PHONE);

        if (! is_string($phone) || $phone === '') {
            return PhoneVerifyResult::invalid();
        }

        $payload = $this->codes->consume(
            $this->cacheKey($phone),
            $code,
            'phone-login-attempt:'.$phone,
        );

        if ($payload === null) {
            return PhoneVerifyResult::invalid();
        }

        $user = $this->users->findByPhone($phone);

        if ($user instanceof User) {
            $this->forgetChallenge();

            return PhoneVerifyResult::authenticated($user);
        }

        session([self::SESSION_VERIFIED => true]);
        session()->forget(SocialAuthService::SESSION_PENDING);

        return PhoneVerifyResult::needsRegistration();
    }

    public function verifiedPhone(): ?string
    {
        if (session(self::SESSION_VERIFIED) !== true) {
            return null;
        }

        $phone = session(self::SESSION_PHONE);

        return is_string($phone) && $phone !== '' ? $phone : null;
    }

    public function requestedPhone(): ?string
    {
        $phone = session(self::SESSION_PHONE);

        return is_string($phone) && $phone !== '' ? $phone : null;
    }

    public function registerPending(string $name, string $email, ?string $password, int $age, string $gender): User
    {
        $phone = $this->verifiedPhone();

        if ($phone === null) {
            throw ValidationException::withMessages([
                'phone' => (string) __('login.phone_code_invalid'),
            ]);
        }

        if ($this->users->findWithTrashedByPhone($phone) instanceof User) {
            throw ValidationException::withMessages([
                'phone' => (string) __('login.account_pending_deletion'),
            ]);
        }

        $email = mb_strtolower(trim($email));

        if ($this->users->emailTaken($email)) {
            throw ValidationException::withMessages([
                'email' => (string) __('login.email_taken'),
            ]);
        }

        $user = $this->registration->register($name, $email, $password, $age, $gender, $phone);
        $this->forgetChallenge();

        return $user;
    }

    public function forgetChallenge(): void
    {
        session()->forget([self::SESSION_PHONE, self::SESSION_VERIFIED]);
    }

    public function normalize(string $phone): string
    {
        $phone = preg_replace('/[^\d+]/', '', trim($phone)) ?? '';

        if (str_starts_with($phone, '00')) {
            $phone = '+'.substr($phone, 2);
        }

        if (preg_match('/^5\d{8}$/', $phone) === 1) {
            $phone = '+995'.$phone;
        }

        return $phone;
    }

    private function logMailbox(): string
    {
        $from = config('mail.from.address');

        return is_string($from) && $from !== '' ? $from : 'phone-login@localhost';
    }

    private function cacheKey(string $phone): string
    {
        return 'phone-login:'.$phone;
    }

    private function tooManySends(string $phone, ?string $ip): bool
    {
        if (RateLimiter::tooManyAttempts($this->sendKey($phone), 1)) {
            return true;
        }

        if (is_string($ip) && $ip !== '' && RateLimiter::tooManyAttempts($this->ipKey($ip), 5)) {
            return true;
        }

        return false;
    }

    private function hitSendLimiters(string $phone, ?string $ip): void
    {
        RateLimiter::hit($this->sendKey($phone), 30);

        if (is_string($ip) && $ip !== '') {
            RateLimiter::hit($this->ipKey($ip), 3600);
        }
    }

    private function sendKey(string $phone): string
    {
        return 'phone-login-send:'.$phone;
    }

    private function ipKey(string $ip): string
    {
        return 'phone-login-send-ip:'.$ip;
    }
}
