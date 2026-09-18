<?php

namespace Tests\Feature;

use App\Enums\FriendshipStatus;
use App\Models\Friendship;
use App\Models\User;
use App\Repositories\UserStatRepository;
use App\Services\LeagueSeasonService;
use App\Services\UserStatService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PrivacyTogglesTest extends TestCase
{
    use RefreshDatabase;

    public function test_opted_out_kid_is_absent_from_global_leaderboard_and_ranks_close_the_gap(): void
    {
        $this->withoutVite();

        $leader = User::factory()->fullySetUp()->withStats(['xp' => 900])->create(['name' => 'Leo']);
        $hidden = User::factory()->fullySetUp()->withStats(['xp' => 700])->create([
            'name' => 'Mia',
            'show_on_leaderboard' => false,
        ]);
        $you = User::factory()->fullySetUp()->withStats(['xp' => 500])->create(['name' => 'Luna']);

        $stats = app(UserStatRepository::class);

        $this->assertSame(
            [$leader->id, $you->id],
            $stats->topByXp()->pluck('user_id')->all(),
        );
        $this->assertSame(1, $stats->rankFor($leader));
        $this->assertSame(2, $stats->rankFor($you));
        $this->assertNull($stats->rankFor($hidden));
        $this->assertSame(2, $stats->countLearners());
        $this->assertSame(500, $stats->xpAtRank(2));

        Livewire::actingAs($you)
            ->test('pages::leaderboard')
            ->assertSet('yourRank', 2)
            ->assertSee('Leo')
            ->assertSee('Luna')
            ->assertDontSee('Mia');

        Livewire::actingAs($you)
            ->test('pages::profile')
            ->assertSet('rank', 2)
            ->assertSee('#2', false);
    }

    public function test_opted_out_kid_still_sees_own_xp_without_a_public_rank(): void
    {
        $this->withoutVite();

        User::factory()->fullySetUp()->withStats(['xp' => 900])->create(['name' => 'Leo']);
        $hidden = User::factory()->fullySetUp()->withStats(['xp' => 1240])->create([
            'name' => 'ნინო',
            'show_on_leaderboard' => false,
        ]);

        $page = Livewire::actingAs($hidden)
            ->test('pages::leaderboard')
            ->assertSet('yourRank', null)
            ->assertSet('yourXp', 1240)
            ->assertSee(__('ranking.you', ['name' => 'ნინო']), false)
            ->assertSee('1,240');

        $this->assertFalse(
            collect($page->get('rows'))->contains(
                fn (array $row): bool => (int) $row['userId'] === $hidden->id,
            ),
        );

        Livewire::actingAs($hidden)
            ->test('pages::profile')
            ->assertSet('rank', null)
            ->assertSet('xp', 1240)
            ->assertSee('ნინო', false)
            ->assertSee(__('profile.rank_hidden'), false)
            ->assertDontSee('#1', false);
    }

    public function test_weekly_ranking_queries_exclude_opted_out_kids(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-08-20 12:00:00'));

        $from = '2026-08-17';
        $to = '2026-08-23';
        $days = app(UserStatRepository::class);

        $leader = User::factory()->fullySetUp()->withStats(['xp' => 100])->create();
        $hidden = User::factory()->fullySetUp()->withStats(['xp' => 100])->create([
            'show_on_leaderboard' => false,
        ]);
        $you = User::factory()->fullySetUp()->withStats(['xp' => 100])->create();

        $days->addDayXp($leader, '2026-08-18', 80);
        $days->addDayXp($hidden, '2026-08-18', 60);
        $days->addDayXp($you, '2026-08-18', 40);

        $this->assertSame(
            [$leader->id, $you->id],
            $days->topByWeekXp($from, $to)->pluck('user_id')->all(),
        );
        $this->assertSame(1, $days->rankForWeek($leader, $from, $to));
        $this->assertSame(2, $days->rankForWeek($you, $from, $to));
        $this->assertNull($days->rankForWeek($hidden, $from, $to));
        $this->assertSame(40, $days->weekXpAtRank(2, $from, $to));
    }

    public function test_opted_out_kid_stays_on_weekly_league_and_friends_ranking(): void
    {
        $this->withoutVite();
        $this->travelTo(CarbonImmutable::parse('2026-08-17 10:00:00'));

        $you = User::factory()->fullySetUp()->withStats(['xp' => 200])->create(['name' => 'Luna']);
        $hidden = User::factory()->fullySetUp()->withStats(['xp' => 800])->create([
            'name' => 'Mia',
            'show_on_leaderboard' => false,
        ]);

        $stats = app(UserStatService::class);
        $stats->recordPlay($you, 20);
        $stats->recordPlay($hidden, 90);

        $snap = app(LeagueSeasonService::class)->weeklySnapshot($you);
        $names = array_map(fn ($row) => $row->name, $snap->members);
        $this->assertContains('Mia', $names);

        Livewire::actingAs($you)
            ->test('pages::ranking-weekly')
            ->assertSee('Mia');

        Friendship::factory()->create([
            'user_id' => $you->id,
            'friend_id' => $hidden->id,
            'status' => FriendshipStatus::Accepted,
            'accepted_at' => now(),
        ]);

        Livewire::actingAs($you)
            ->test('pages::ranking-friends')
            ->assertSee('Mia')
            ->assertSet('yourRank', 2);
    }

    public function test_toggling_edit_profile_hides_the_kid_from_the_leaderboard(): void
    {
        $this->withoutVite();

        $hidden = User::factory()->fullySetUp()->withStats(['xp' => 700])->create([
            'name' => 'Mia',
            'nickname' => 'mia-star',
        ]);
        $viewer = User::factory()->fullySetUp()->withStats(['xp' => 100])->create(['name' => 'Luna']);

        Livewire::actingAs($viewer)
            ->test('pages::leaderboard')
            ->assertSee('Mia');

        Livewire::actingAs($hidden)
            ->test('pages::edit-profile')
            ->set('showOnLeaderboard', false)
            ->call('save')
            ->assertRedirect(route('profile'));

        $this->assertFalse($hidden->fresh()->show_on_leaderboard);

        Livewire::actingAs($viewer)
            ->test('pages::leaderboard')
            ->assertDontSee('Mia');
    }
}
