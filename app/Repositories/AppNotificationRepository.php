<?php

namespace App\Repositories;

use App\Models\User;
use App\Notifications\KidzioAlert;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

class AppNotificationRepository
{
    /**
     * @return Collection<int, DatabaseNotification>
     */
    public function latest(User $user, int $limit = 30): Collection
    {
        return $user->notifications()->latest()->limit($limit)->get();
    }

    public function unreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function existsDedupe(User $user, string $key): bool
    {
        return $user->notifications()->where('data->dedupe', $key)->exists();
    }

    public function send(User $user, KidzioAlert $alert): void
    {
        $user->notify($alert);
    }

    public function findForUser(User $user, string $id): ?DatabaseNotification
    {
        $row = $user->notifications()->whereKey($id)->first();

        return $row instanceof DatabaseNotification ? $row : null;
    }

    public function markRead(User $user, string $id): ?DatabaseNotification
    {
        $row = $this->findForUser($user, $id);

        $row?->markAsRead();

        return $row;
    }

    public function markAllRead(User $user): void
    {
        $user->unreadNotifications()->update(['read_at' => now()]);
    }
}
