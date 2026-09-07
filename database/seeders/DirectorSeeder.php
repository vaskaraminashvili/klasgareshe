<?php

namespace Database\Seeders;

use App\Models\Director;
use Illuminate\Database\Seeder;

class DirectorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Director::factory()->create([
            'name' => 'Director',
            'email' => 'director@example.com',
            'password' => 'password',
        ]);
    }
}
