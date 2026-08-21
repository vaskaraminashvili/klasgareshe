<?php

namespace Database\Factories;

use App\Enums\League;
use App\Models\LeagueGroup;
use App\Models\LeagueWeek;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeagueGroup>
 */
class LeagueGroupFactory extends Factory
{
    protected $model = LeagueGroup::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'league_week_id' => LeagueWeek::factory(),
            'tier' => League::Bronze,
            'capacity' => 12,
        ];
    }

    public function tier(League $tier): static
    {
        return $this->state(fn (array $attributes) => [
            'tier' => $tier,
        ]);
    }

    public function capacity(int $capacity): static
    {
        return $this->state(fn (array $attributes) => [
            'capacity' => $capacity,
        ]);
    }
}
