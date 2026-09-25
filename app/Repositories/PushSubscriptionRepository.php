<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Collection;
use NotificationChannels\WebPush\PushSubscription;

class PushSubscriptionRepository
{
    /**
     * @return Collection<int, PushSubscription>
     */
    public function forUser(User $user): Collection
    {
        return $user->pushSubscriptions()->orderBy('id')->get();
    }

    public function upsert(
        User $user,
        string $endpoint,
        string $publicKey,
        string $authToken,
        string $contentEncoding = 'aes128gcm',
    ): PushSubscription {
        return $user->updatePushSubscription(
            $endpoint,
            $publicKey,
            $authToken,
            $contentEncoding !== '' ? $contentEncoding : 'aes128gcm',
        );
    }

    public function deleteByEndpoint(User $user, string $endpoint): void
    {
        $user->deletePushSubscription($endpoint);
    }
}
