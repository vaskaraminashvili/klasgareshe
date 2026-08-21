<?php

namespace Database\Factories;

use App\Enums\League;
use App\Models\LeagueGroup;
use App\Models\LeagueGroupMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeagueGroupMember>
 */
class LeagueGroupMemberFactory extends Factory
{
    protected $model = LeagueGroupMember::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'league_group_id' => LeagueGroup::factory(),
            'user_id' => User::factory()->fullySetUp(),
            'week_xp' => fake()->numberBetween(0, 300),
            'finish_rank' => null,
            'outcome' => null,
        ];
    }

    public function weekXp(int $xp): static
    {
        return $this->state(fn (array $attributes) => [
            'week_xp' => $xp,
        ]);
    }

    public function forTier(League $tier): static
    {
        return $this->state(fn (array $attributes) => [
            'league_group_id' => LeagueGroup::factory()->state([
                'tier' => $tier,
            ]),
        ]);
    }
}
