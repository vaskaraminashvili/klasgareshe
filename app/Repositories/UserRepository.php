<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

class UserRepository
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): User
    {
        return User::query()->create($attributes);
    }

    public function findOrFail(int $id): User
    {
        return User::query()->findOrFail($id);
    }

    public function authenticated(): User
    {
        $id = Auth::id();

        if (! is_numeric($id)) {
            throw new AuthenticationException;
        }

        return $this->findOrFail((int) $id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(User $user, array $attributes): User
    {
        $user->update($attributes);

        return $user;
    }

    public function nicknameExists(string $nickname): bool
    {
        return User::query()->where('nickname', $nickname)->exists();
    }

    public function markEmailVerified(User $user): User
    {
        if ($user->email_verified_at === null) {
            $user->forceFill([
                'email_verified_at' => now(),
            ])->save();
        }

        return $user->fresh() ?? $user;
    }
}
