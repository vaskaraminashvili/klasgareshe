<?php

namespace Database\Seeders;

use App\Enums\League;
use App\Models\User;
use App\Services\LeagueSeasonService;
use Illuminate\Database\Seeder;

class RankingSeeder extends Seeder
{
    /**
     * Seed veteran kids so Global / Weekly / League screens have live competition.
     */
    public function run(): void
    {
        $topKids = [
            ['name' => 'Leo', 'surname' => 'Beridze', 'nickname' => 'leo_top', 'email' => 'leo@example.com', 'xp' => 2140, 'league' => League::Sapphire, 'week' => 380],
            ['name' => 'Mia', 'surname' => 'Kapanadze', 'nickname' => 'mia_star', 'email' => 'mia@example.com', 'xp' => 1820, 'league' => League::Emerald, 'week' => 340],
            ['name' => 'Sam', 'surname' => 'Gelashvili', 'nickname' => 'sam_quiz', 'email' => 'sam@example.com', 'xp' => 1630, 'league' => League::Gold, 'week' => 300],
        ];

        foreach ($topKids as $kid) {
            $this->seedNamedKid($kid);
        }

        $midXp = [1510, 1190, 1080, 980, 910, 870, 760, 640, 520, 410];

        foreach ($midXp as $index => $xp) {
            User::factory()->veteran([
                'xp' => $xp,
                'current_streak' => fake()->numberBetween(2, 10),
                'longest_streak' => fake()->numberBetween(10, 25),
                'league' => fake()->randomElement([League::Bronze, League::Silver, League::Gold]),
                'last_played_on' => now()->toDateString(),
            ])->create([
                'email' => 'kid'.($index + 1).'@example.com',
                'nickname' => 'kid'.($index + 1),
            ]);
        }
    }

    /**
     * @param  array{name: string, surname: string, nickname: string, email: string, xp: int, league: League, week: int}  $kid
     */
    private function seedNamedKid(array $kid): void
    {
        $user = User::factory()->leaderboardTop([
            'xp' => $kid['xp'],
            'league' => $kid['league'],
            'current_streak' => fake()->numberBetween(8, 16),
            'longest_streak' => fake()->numberBetween(16, 30),
            'last_played_on' => now()->toDateString(),
        ])->create([
            'name' => $kid['name'],
            'surname' => $kid['surname'],
            'nickname' => $kid['nickname'],
            'email' => $kid['email'],
            'password' => 'password',
        ]);

        $member = app(LeagueSeasonService::class)->ensureMembership($user);
        $member->update(['week_xp' => $kid['week']]);
    }
}
