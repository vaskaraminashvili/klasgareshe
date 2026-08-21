<?php

namespace Database\Factories;

use App\Enums\League;
use App\Models\User;
use App\Models\UserStat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserStat>
 */
class UserStatFactory extends Factory
{
    protected $model = UserStat::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $xp = fake()->numberBetween(0, 800);
        $streak = fake()->numberBetween(0, 7);

        return [
            'user_id' => User::factory(),
            'xp' => $xp,
            'current_streak' => $streak,
            'longest_streak' => max($streak, fake()->numberBetween($streak, 21)),
            'last_played_on' => $streak > 0 ? now()->toDateString() : null,
            'league' => League::Bronze,
        ];
    }

    public function xp(int $xp): static
    {
        return $this->state(fn (array $attributes) => [
            'xp' => $xp,
        ]);
    }

    public function streak(int $current, ?int $longest = null): static
    {
        return $this->state(fn (array $attributes) => [
            'current_streak' => $current,
            'longest_streak' => $longest ?? max($current, $attributes['longest_streak'] ?? $current),
            'last_played_on' => $current > 0 ? now()->toDateString() : null,
        ]);
    }

    public function league(League $league): static
    {
        return $this->state(fn (array $attributes) => [
            'league' => $league,
        ]);
    }

    /**
     * Seasoned kid with enough XP to sit mid/high on the leaderboard.
     */
    public function veteran(): static
    {
        $xp = fake()->numberBetween(400, 2400);
        $streak = fake()->numberBetween(3, 14);

        return $this->state(fn (array $attributes) => [
            'xp' => $xp,
            'current_streak' => $streak,
            'longest_streak' => fake()->numberBetween($streak, max($streak, 30)),
            'last_played_on' => now()->toDateString(),
            'league' => fake()->randomElement([
                League::Bronze,
                League::Silver,
                League::Gold,
                League::Emerald,
            ]),
        ]);
    }

    public function leaderboardTop(): static
    {
        $xp = fake()->numberBetween(1600, 3200);
        $streak = fake()->numberBetween(7, 21);

        return $this->state(fn (array $attributes) => [
            'xp' => $xp,
            'current_streak' => $streak,
            'longest_streak' => fake()->numberBetween($streak, 40),
            'last_played_on' => now()->toDateString(),
            'league' => fake()->randomElement([League::Gold, League::Emerald, League::Sapphire]),
        ]);
    }
}
