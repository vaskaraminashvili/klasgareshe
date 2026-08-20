<?php

namespace Tests\Feature;

use App\Enums\League;
use App\Models\User;
use App\Repositories\UserStatRepository;
use App\Services\UserStatService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class UserStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_the_kids_stats_and_weekly_streak(): void
    {
        $this->withoutVite();
        $this->travelTo(CarbonImmutable::parse('2026-08-20 12:00:00'));

        $user = User::factory()->fullySetUp()->withStats([
            'xp' => 1240,
            'current_streak' => 7,
            'longest_streak' => 12,
            'league' => League::Gold,
        ])->create();

        $days = app(UserStatRepository::class);

        foreach (['2026-08-17', '2026-08-18', '2026-08-19', '2026-08-20'] as $date) {
            $days->addDayXp($user, $date, 0);
        }

        Livewire::actingAs($user)
            ->test('pages::home')
            ->assertSet('streak', 7)
            ->assertSet('xp', 1240)
            ->assertSet('leagueLabel', League::Gold->label())
            ->assertSet('weekActiveDays', 4)
            ->assertSet('weekDays.3.today', true)
            ->assertSet('weekDays.3.on', true)
            ->assertSet('weekDays.4.on', false)
            ->assertSeeHtml('data-target="1240"');
    }

    public function test_new_kids_start_with_empty_home_stats(): void
    {
        $this->withoutVite();

        $user = User::factory()->fullySetUp()->create();

        Livewire::actingAs($user)
            ->test('pages::home')
            ->assertSet('streak', 0)
            ->assertSet('xp', 0)
            ->assertSet('leagueLabel', League::Bronze->label())
            ->assertSet('weekActiveDays', 0);
    }

    public function test_play_increments_streak_across_consecutive_days_and_resets_after_a_gap(): void
    {
        $user = User::factory()->fullySetUp()->create();
        $stats = app(UserStatService::class);

        $this->travelTo(CarbonImmutable::parse('2026-08-17 10:00:00'));
        $stat = $stats->recordPlay($user, 40);
        $this->assertSame(1, $stat->current_streak);
        $this->assertSame(40, $stat->xp);

        $this->travelTo(CarbonImmutable::parse('2026-08-17 22:00:00'));
        $stat = $stats->recordPlay($user, 10);
        $this->assertSame(1, $stat->current_streak);
        $this->assertSame(50, $stat->xp);

        $this->travelTo(CarbonImmutable::parse('2026-08-18 09:00:00'));
        $stat = $stats->recordPlay($user, 40);
        $this->assertSame(2, $stat->current_streak);
        $this->assertSame(90, $stat->xp);

        $this->travelTo(CarbonImmutable::parse('2026-08-20 09:00:00'));
        $stat = $stats->recordPlay($user, 40);
        $this->assertSame(1, $stat->current_streak);
        $this->assertSame(2, $stat->longest_streak);
        $this->assertSame(130, $stat->xp);
    }

    public function test_negative_xp_is_rejected(): void
    {
        $user = User::factory()->fullySetUp()->create();

        $this->expectException(InvalidArgumentException::class);

        app(UserStatService::class)->recordPlay($user, -10);
    }
}
