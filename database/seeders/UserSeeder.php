<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->fullySetUp()->create([
            'name' => 'John',
            'surname' => 'Doe',
            'nickname' => 'johndoe',
            'email' => 'john.doe@example.com',
            'password' => 'password',
        ]);
    }
}
