<?php

namespace App\Repositories;

use App\Enums\SocialProvider;
use App\Models\SocialIdentity;

class SocialIdentityRepository
{
    public function findActive(SocialProvider $provider, string $providerId): ?SocialIdentity
    {
        return SocialIdentity::query()
            ->where('provider', $provider->value)
            ->where('provider_id', $providerId)
            ->first();
    }

    public function create(int $userId, SocialProvider $provider, string $providerId, string $email): SocialIdentity
    {
        return SocialIdentity::query()->create([
            'user_id' => $userId,
            'provider' => $provider,
            'provider_id' => $providerId,
            'email' => $email,
        ]);
    }

    public function softDeleteForUser(int $userId): void
    {
        SocialIdentity::query()->where('user_id', $userId)->delete();
    }

    public function forceDeleteForUser(int $userId): void
    {
        SocialIdentity::withTrashed()->where('user_id', $userId)->forceDelete();
    }
}
