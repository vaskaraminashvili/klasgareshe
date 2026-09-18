<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserPlaySession;
use App\Services\ParentZoneService;
use App\Services\ScreenTimeService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ScreenTimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_open_screen_time_routes(): void
    {
        $this->get(route('screen-time'))->assertRedirect(route('user-login'));
        $this->get(route('bedtime-lock'))->assertRedirect(route('user-login'));
        $this->get(route('play-paused'))->assertRedirect(route('user-login'));
    }

    public function test_locked_parent_urls_bounce_to_the_pin_gate(): void
    {
        $user = User::factory()->fullySetUp()->withStats()->withParentPin()->create();

        $this->actingAs($user);

        $this->get(route('screen-time'))->assertRedirect(route('parent-controls'));
        $this->get(route('bedtime-lock'))->assertRedirect(route('parent-controls'));
    }

    public function test_limit_hit_blocks_play_and_survives_a_reload(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-18 12:00:00', 'Asia/Tbilisi'));

        $user = User::factory()->fullySetUp()->withStats()->withDailyLimit(15)->create();
        $this->recordPlay($user, 15 * 60);

        $this->actingAs($user)
            ->get(route('game-multiple-choice', ['item' => 1]))
            ->assertRedirect(route('play-paused'));

        $this->get(route('game-multiple-choice', ['item' => 1]))
            ->assertRedirect(route('play-paused'));

        $this->get(route('play-paused'))->assertOk();
        $this->get(route('profile'))->assertOk();
    }

    public function test_parent_can_grant_a_one_off_extension(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-18 12:00:00', 'Asia/Tbilisi'));

        $user = User::factory()->fullySetUp()->withStats()->withParentPin()->withDailyLimit(15)->create();
        $this->recordPlay($user, 15 * 60);

        $this->actingAs($user);
        app(ParentZoneService::class)->unlock();

        Livewire::actingAs($user)
            ->test('pages::screen-time')
            ->call('grantExtra')
            ->assertSet('extraGrantedToday', true);

        $user->refresh();
        $this->actingAs($user);

        $this->assertSame(15, $user->screen_time_extra_minutes);
        $this->assertNull(app(ScreenTimeService::class)->blockReason($user));

        $this->get(route('game-multiple-choice', ['item' => 1]))
            ->assertRedirect(route('home'));
    }

    public function test_bedtime_window_blocks_play_across_midnight(): void
    {
        $user = User::factory()->fullySetUp()->withStats()->withBedtime('21:00', '07:00')->create();
        $time = app(ScreenTimeService::class);

        $this->travelTo(CarbonImmutable::parse('2026-09-18 20:30:00', 'Asia/Tbilisi'));
        $this->assertNull($time->blockReason($user->fresh()));

        $this->travelTo(CarbonImmutable::parse('2026-09-18 21:30:00', 'Asia/Tbilisi'));
        $this->actingAs($user)
            ->get(route('game-multiple-choice', ['item' => 1]))
            ->assertRedirect(route('play-paused'));

        $this->travelTo(CarbonImmutable::parse('2026-09-19 06:30:00', 'Asia/Tbilisi'));
        $this->get(route('game-multiple-choice', ['item' => 1]))
            ->assertRedirect(route('play-paused'));

        $this->travelTo(CarbonImmutable::parse('2026-09-19 08:00:00', 'Asia/Tbilisi'));
        $this->assertNull($time->blockReason($user->fresh()));
    }

    public function test_stale_open_session_is_capped_at_last_heartbeat(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-18 12:00:00', 'Asia/Tbilisi'));

        $user = User::factory()->fullySetUp()->withStats()->create();
        $start = now()->subHours(3);
        $last = now()->subHours(2);

        UserPlaySession::factory()->create([
            'user_id' => $user->id,
            'started_at' => $start,
            'last_heartbeat_at' => $last,
            'ended_at' => null,
            'seconds' => 0,
        ]);

        $used = app(ScreenTimeService::class)->usedTodaySeconds($user);

        $this->assertEquals(3600, $used);
        $this->assertNotNull(UserPlaySession::query()->where('user_id', $user->id)->first()?->ended_at);
    }

    public function test_profile_chip_and_parent_dashboard_show_real_minutes(): void
    {
        $this->withoutVite();
        $this->travelTo(CarbonImmutable::parse('2026-09-18 12:00:00', 'Asia/Tbilisi'));

        $user = User::factory()->fullySetUp()->withStats()->withParentPin()->withDailyLimit(30)->create();
        $this->recordPlay($user, 10 * 60);

        $this->actingAs($user);

        Livewire::test('pages::profile')
            ->assertSee(__('profile.screen_time'), false)
            ->assertSee(__('profile.screen_time_left', ['minutes' => 20]), false);

        app(ParentZoneService::class)->unlock();

        Livewire::test('pages::parent-controls')
            ->assertSet('weekMinutes', 10)
            ->assertSet('todayUsedMinutes', 10)
            ->assertSee(__('parent-zone.daily_screen_time'), false)
            ->assertSee(__('parent-zone.bedtime_lock'), false);
    }

    public function test_unlimited_play_is_allowed_when_no_limit_is_set(): void
    {
        $user = User::factory()->fullySetUp()->withStats()->create();
        $this->recordPlay($user, 120 * 60);

        $this->assertNull(app(ScreenTimeService::class)->blockReason($user));
    }

    private function recordPlay(User $user, int $seconds): void
    {
        UserPlaySession::factory()->lasting($seconds)->create([
            'user_id' => $user->id,
        ]);
    }
}
