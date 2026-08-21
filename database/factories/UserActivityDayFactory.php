<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserActivityDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserActivityDay>
 */
class UserActivityDayFactory extends Factory
{
    protected $model = UserActivityDay::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'played_on' => fake()->dateTimeBetween('-14 days', 'now')->format('Y-m-d'),
            'xp_earned' => fake()->numberBetween(8, 120),
        ];
    }

    public function on(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'played_on' => $date,
        ]);
    }

    public function xp(int $xp): static
    {
        return $this->state(fn (array $attributes) => [
            'xp_earned' => $xp,
        ]);
    }
}
