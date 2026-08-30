<?php

namespace Database\Factories;

use App\Enums\FriendshipStatus;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Friendship>
 */
class FriendshipFactory extends Factory
{
    protected $model = Friendship::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'friend_id' => User::factory(),
            'status' => FriendshipStatus::Accepted,
            'accepted_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => FriendshipStatus::Pending,
            'accepted_at' => null,
        ]);
    }
}
