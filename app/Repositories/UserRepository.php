<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    /**
     * @param  array{name: string, surname: string, nickname: string, email: string, password: string}  $attributes
     */
    public function create(array $attributes): User
    {
        return User::query()->create($attributes);
    }

    public function nicknameExists(string $nickname): bool
    {
        return User::query()->where('nickname', $nickname)->exists();
    }
}
