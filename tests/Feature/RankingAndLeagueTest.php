<?php

namespace Tests\Feature;

use App\Enums\League;
use App\Enums\LeagueOutcome;
use App\Enums\LeagueWeekStatus;
use App\Models\User;
use App\Repositories\LeagueRepository;
use App\Repositories\UserStatRepository;
use App\Services\LeagueSeasonService;
use App\Services\UserStatService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RankingAndLeagueTest extends TestCase
{
    use RefreshDatabase;

    public function test_xp_progress_page_shows_live_level_and_xp(): void
    {
        $this->withoutVite();
        $this->travelTo(CarbonImmutable::parse('2026-08-21 12:00:00'));

        $user = User::factory()->fullySetUp()->withStats(['xp' => 1240])->create();
        app(UserStatService::class)->recordPlay($user, 40, CarbonImmutable::parse('2026-08-21'));

        Livewire::actingAs($user)
            ->test('pages::xp-progress')
            ->assertSet('xp', 1280)
            ->assertSet('level', 5)
            ->assertSee('1,280');
    }

    public function test_leaderboard_orders_by_xp_and_shows_your_rank(): void
    {
        $this->withoutVite();

        $you = User::factory()->fullySetUp()->withStats(['xp' => 500])->create(['name' => 'Luna']);
        User::factory()->fullySetUp()->withStats(['xp' => 900])->create(['name' => 'Leo']);
        User::factory()->fullySetUp()->withStats(['xp' => 700])->create(['name' => 'Mia']);

        Livewire::actingAs($you)
            ->test('pages::leaderboard')
            ->assertSet('yourRank', 3)
            ->assertSet('yourXp', 500)
            ->assertSee('Leo')
            ->assertSee('Mia')
            ->assertSee('Luna');
    }

    public function test_record_play_adds_week_xp_to_league_membership(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-08-17 10:00:00'));

        $user = User::factory()->fullySetUp()->create();
        app(UserStatService::class)->recordPlay($user, 40);

        $snap = app(LeagueSeasonService::class)->weeklySnapshot($user);

        $this->assertSame(40, $snap->yourWeekXp);
        $this->assertSame(1, $snap->memberCount);
        $this->assertSame(League::Bronze, $snap->tier);
    }

    public function test_close_week_promotes_and_relegates_when_group_is_large_enough(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-08-17 10:00:00'));
        $leagues = app(LeagueSeasonService::class);
        $stats = app(UserStatService::class);

        $players = User::factory()->fullySetUp()->count(4)->create();
        $xpAmounts = [100, 80, 50, 10];

        foreach ($players as $i => $player) {
            $stats->recordPlay($player, $xpAmounts[$i]);
        }

        $week = $leagues->ensureCurrentWeek();
        $this->travelTo(CarbonImmutable::parse('2026-08-24 01:00:00'));
        $leagues->closeWeek($week);

        $week->refresh();
        $this->assertSame(LeagueWeekStatus::Closed, $week->status);

        $top = $players[0]->fresh();
        $bottom = $players[3]->fresh();

        $this->assertSame(League::Silver, app(UserStatRepository::class)->firstOrCreateFor($top)->league);
        $this->assertSame(League::Bronze, app(UserStatRepository::class)->firstOrCreateFor($bottom)->league);

        $topMember = app(LeagueRepository::class)->membershipForUserInWeek($top, $week);
        $bottomMember = app(LeagueRepository::class)->membershipForUserInWeek($bottom, $week);

        $this->assertSame(LeagueOutcome::Promote, $topMember?->outcome);
        $this->assertSame(LeagueOutcome::Relegate, $bottomMember?->outcome);
        $this->assertSame(1, $topMember?->finish_rank);
        $this->assertSame(4, $bottomMember?->finish_rank);
    }

    public function test_weekly_and_league_pages_load_for_authenticated_kid(): void
    {
        $this->withoutVite();

        $user = User::factory()->fullySetUp()->create();

        Livewire::actingAs($user)->test('pages::ranking-weekly')->assertSuccessful();
        Livewire::actingAs($user)->test('pages::league')->assertSuccessful();
    }

    public function test_close_week_holds_everyone_in_tiny_groups(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-08-17 10:00:00'));
        $leagues = app(LeagueSeasonService::class);
        $stats = app(UserStatService::class);

        $a = User::factory()->fullySetUp()->create();
        $b = User::factory()->fullySetUp()->create();
        $stats->recordPlay($a, 100);
        $stats->recordPlay($b, 10);

        $week = $leagues->ensureCurrentWeek();
        $leagues->closeWeek($week);

        $repo = app(LeagueRepository::class);
        $this->assertSame(LeagueOutcome::Hold, $repo->membershipForUserInWeek($a, $week)?->outcome);
        $this->assertSame(LeagueOutcome::Hold, $repo->membershipForUserInWeek($b, $week)?->outcome);
    }
}
