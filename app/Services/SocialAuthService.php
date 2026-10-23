<?php

namespace App\Services;

use App\Data\PendingSocialSignup;
use App\Enums\SocialProvider;
use App\Exceptions\AuthProviderException;
use App\Models\User;
use App\Repositories\SocialIdentityRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\AbstractUser;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Testing\FakeProvider;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\InvalidStateException;

class SocialAuthService
{
    public const SESSION_PENDING = 'social_auth.pending';

    public function __construct(
        private UserRepository $users,
        private SocialIdentityRepository $identities,
        private UserRegistrationService $registration,
    ) {}

    public function redirectToProvider(string $provider): RedirectResponse
    {
        $enum = $this->provider($provider);
        $driver = $this->resolveDriver($enum);

        if ($driver instanceof AbstractProvider && $enum === SocialProvider::Google) {
            $driver->scopes(['openid', 'email', 'profile']);
        }

        $response = $driver->redirect();

        if (! $response instanceof RedirectResponse) {
            throw new AuthProviderException(__('login.provider_failed'));
        }

        return $response;
    }

    /**
     * Sign in an existing parent account, or stash a pending signup when the
     * email is new. Returns null when the parent still has to finish consent
     * and the kid's own name on the register screen.
     */
    public function authenticate(string $provider): ?User
    {
        $enum = $this->provider($provider);
        $driver = $this->resolveDriver($enum);

        try {
            $socialUser = $driver->user();
        } catch (InvalidStateException) {
            throw new AuthProviderException(__('login.provider_failed'));
        }

        $providerId = trim((string) $socialUser->getId());

        if ($providerId === '') {
            throw new AuthProviderException(__('login.provider_failed'));
        }

        $email = $this->emailFrom($socialUser);
        $verified = $this->emailVerified($enum, $socialUser, $email);

        $identity = $this->identities->findActive($enum, $providerId);

        if ($identity !== null) {
            $user = $this->users->find($identity->user_id);

            if (! $user instanceof User) {
                throw new AuthProviderException(__('login.account_pending_deletion'));
            }

            if ($verified) {
                $user = $this->users->markEmailVerified($user);
            }

            return $user;
        }

        if ($email === '') {
            throw new AuthProviderException(__('login.provider_email_missing'));
        }

        $trashed = $this->users->findWithTrashedByEmail($email);

        if ($trashed instanceof User && $trashed->trashed()) {
            throw new AuthProviderException(__('login.account_pending_deletion'));
        }

        $existing = $this->users->findByEmail($email);

        if ($existing instanceof User) {
            $this->identities->create($existing->id, $enum, $providerId, $email);

            if ($verified) {
                $existing = $this->users->markEmailVerified($existing);
            }

            return $existing;
        }

        $this->stashPending($enum, $providerId, $email, $verified);

        return null;
    }

    public function pending(): ?PendingSocialSignup
    {
        $payload = session(self::SESSION_PENDING);

        if (! is_array($payload)) {
            return null;
        }

        $provider = SocialProvider::tryFrom((string) ($payload['provider'] ?? ''));
        $providerId = $payload['provider_id'] ?? null;
        $email = $payload['email'] ?? null;

        if (! $provider instanceof SocialProvider || ! is_string($providerId) || $providerId === '' || ! is_string($email) || $email === '') {
            return null;
        }

        return new PendingSocialSignup(
            $provider,
            $providerId,
            $email,
            ($payload['email_verified'] ?? false) === true,
        );
    }

    public function registerPending(string $name, ?string $password, int $age, string $gender): User
    {
        $pending = $this->pending();

        if (! $pending instanceof PendingSocialSignup) {
            throw new AuthProviderException(__('login.provider_failed'));
        }

        $existing = $this->users->findWithTrashedByEmail($pending->email);

        if ($existing instanceof User) {
            if ($existing->trashed()) {
                throw new AuthProviderException(__('login.account_pending_deletion'));
            }

            $this->identities->create($existing->id, $pending->provider, $pending->providerId, $pending->email);

            if ($pending->emailVerified) {
                $existing = $this->users->markEmailVerified($existing);
            }

            $this->forgetPending();

            return $existing;
        }

        $user = $this->registration->register(
            $name,
            $pending->email,
            $password,
            $age,
            $gender,
        );

        $this->identities->create($user->id, $pending->provider, $pending->providerId, $pending->email);

        if ($pending->emailVerified) {
            $user = $this->users->markEmailVerified($user);
        }

        $this->forgetPending();

        return $user;
    }

    public function forgetPending(): void
    {
        session()->forget(self::SESSION_PENDING);
    }

    private function stashPending(SocialProvider $provider, string $providerId, string $email, bool $verified): void
    {
        session()->forget([
            PhoneLoginService::SESSION_PHONE,
            PhoneLoginService::SESSION_VERIFIED,
        ]);

        session([
            self::SESSION_PENDING => [
                'provider' => $provider->value,
                'provider_id' => $providerId,
                'email' => $email,
                'email_verified' => $verified,
            ],
        ]);
    }

    private function provider(string $provider): SocialProvider
    {
        $enum = SocialProvider::tryFrom($provider);

        if (! $enum instanceof SocialProvider) {
            throw new AuthProviderException(__('login.provider_not_configured', [
                'provider' => $provider,
            ]));
        }

        return $enum;
    }

    private function resolveDriver(SocialProvider $provider): Provider
    {
        $driver = Socialite::driver($provider->value);

        if ($driver instanceof FakeProvider) {
            return $driver;
        }

        $this->ensureConfigured($provider);

        return $driver;
    }

    private function ensureConfigured(SocialProvider $provider): void
    {
        $config = config('services.'.$provider->value);
        $config = is_array($config) ? $config : [];

        $clientId = $config['client_id'] ?? null;
        $secret = $config['client_secret'] ?? null;
        $privateKey = $config['private_key'] ?? null;
        $teamId = $config['team_id'] ?? null;
        $keyId = $config['key_id'] ?? null;

        $hasId = is_string($clientId) && $clientId !== '';
        $hasSecret = is_string($secret) && $secret !== '';
        $hasKey = is_string($privateKey) && $privateKey !== ''
            && is_string($teamId) && $teamId !== ''
            && is_string($keyId) && $keyId !== '';

        $ready = $provider === SocialProvider::Apple
            ? ($hasId && ($hasSecret || $hasKey))
            : ($hasId && $hasSecret);

        if (! $ready) {
            throw new AuthProviderException(__('login.provider_not_configured', [
                'provider' => $provider->name,
            ]));
        }
    }

    private function emailFrom(SocialiteUser $socialUser): string
    {
        $email = $socialUser->getEmail();

        if (! is_string($email)) {
            return '';
        }

        return mb_strtolower(trim($email));
    }

    private function emailVerified(SocialProvider $provider, SocialiteUser $socialUser, string $email): bool
    {
        if ($email === '') {
            return false;
        }

        if ($provider === SocialProvider::Apple) {
            return true;
        }

        if (! $socialUser instanceof AbstractUser) {
            return false;
        }

        $raw = $socialUser->getRaw();

        if (! array_key_exists('email_verified', $raw)) {
            return false;
        }

        return $raw['email_verified'] === true
            || $raw['email_verified'] === 'true'
            || $raw['email_verified'] === 1;
    }
}
