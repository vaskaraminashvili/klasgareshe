<?php

namespace Tests\Feature;

use App\Enums\League;
use App\Models\User;
use App\Models\UserActivityDay;
use App\Models\UserStat;
use Database\Seeders\RankingSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingFactorySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_veteran_factory_creates_stats_activity_and_league_membership(): void
    {
        $user = User::factory()->veteran([
            'xp' => 900,
            'league' => League::Silver,
            'current_streak' => 5,
        ])->create();

        $stat = UserStat::query()->where('user_id', $user->id)->first();

        $this->assertNotNull($stat);
        $this->assertSame(900, $stat->xp);
        $this->assertSame(League::Silver, $stat->league);
        $this->assertGreaterThan(0, UserActivityDay::query()->where('user_id', $user->id)->count());
        $this->assertDatabaseHas('league_group_members', [
            'user_id' => $user->id,
        ]);
    }

    public function test_leaderboard_top_factory_has_high_xp(): void
    {
        $user = User::factory()->leaderboardTop(['xp' => 2000])->create();

        $this->assertSame(2000, UserStat::query()->where('user_id', $user->id)->value('xp'));
    }

    public function test_ranking_seeder_fills_the_leaderboard(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(RankingSeeder::class);

        $this->assertGreaterThanOrEqual(14, UserStat::query()->where('xp', '>', 0)->count());
        $this->assertDatabaseHas('users', ['email' => 'john.doe@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'leo@example.com']);
        $this->assertSame(2140, (int) UserStat::query()
            ->where('user_id', User::query()->where('email', 'leo@example.com')->value('id'))
            ->value('xp'));
    }
}
