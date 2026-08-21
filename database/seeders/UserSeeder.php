<?php

namespace Database\Seeders;

use App\Enums\League;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->veteran([
            'xp' => fake()->numberBetween(1000, 10000),
            'current_streak' => fake()->numberBetween(1, 10),
            'longest_streak' => fake()->numberBetween(10, 100),
            'league' => fake()->randomElement(League::cases()),
            'last_played_on' => now()->toDateString(),
        ])->create([
            'name' => 'John',
            'surname' => 'Doe',
            'nickname' => 'johndoe',
            'email' => 'john.doe@example.com',
            'password' => 'password',
        ]);
    }
}
