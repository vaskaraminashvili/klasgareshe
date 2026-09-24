<?php

namespace Tests\Feature;

use App\Enums\RewardClaimType;
use App\Enums\XpSource;
use App\Models\RewardClaim;
use App\Models\User;
use App\Models\UserStat;
use App\Models\XpEvent;
use App\Repositories\BadgeRepository;
use App\Services\RewardService;
use App\Services\UserStatService;
use Carbon\CarbonImmutable;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RewardsDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_rewards_tab_and_profile_open_the_dashboard(): void
    {
        $this->withoutVite();
        $user = User::factory()->fullySetUp()->withStats()->create();

        Livewire::actingAs($user)
            ->test('pages::home')
            ->assertSee(route('rewards-dashboard'), false);

        Livewire::actingAs($user)
            ->test('pages::profile')
            ->assertSee(route('rewards-dashboard'), false)
            ->assertSee(__('profile.rewards_dashboard'), false)
            ->assertDontSee('3 new', false);

        Livewire::actingAs($user)
            ->test('bottom-nav-bar')
            ->assertSet('claimCount', 2)
            ->assertSee(route('rewards-dashboard'), false);
    }

    public function test_dashboard_shows_wallet_and_calendar(): void
    {
        $this->withoutVite();
        $user = User::factory()->fullySetUp()->withStats(['xp' => 240, 'coins' => 240])->create();

        Livewire::actingAs($user)
            ->test('pages::rewards-dashboard')
            ->assertSet('xp', 240)
            ->assertSet('coins', 240)
            ->assertSee(__('rewards.daily_box'), false)
            ->assertSee(__('rewards.collect_today'), false)
            ->assertSeeHtml('reward-day');
    }

    public function test_claiming_the_daily_box_twice_pays_once(): void
    {
        $this->withoutVite();
        $this->travelTo(CarbonImmutable::parse('2026-09-21 10:00:00'));
        $user = User::factory()->fullySetUp()->withStats()->create();
        $today = now()->toDateString();

        $page = Livewire::actingAs($user)->test('pages::rewards-dashboard');
        $page->call('claim', RewardClaimType::DailyBox->value, $today);
        $page->call('claim', RewardClaimType::DailyBox->value, $today);

        $stat = UserStat::query()->where('user_id', $user->id)->first();

        $this->assertSame(RewardService::DAILY_BOX_XP, $stat?->xp);
        $this->assertSame(RewardService::DAILY_BOX_XP, $stat?->coins);
        $this->assertSame(1, $this->eventCount($user, XpSource::DailyBox, $today));
        $this->assertSame(1, RewardClaim::query()->where('user_id', $user->id)->where('type', RewardClaimType::DailyBox)->count());
    }

    public function test_collecting_login_twice_pays_once(): void
    {
        $this->withoutVite();
        $this->travelTo(CarbonImmutable::parse('2026-09-21 09:00:00'));
        $user = User::factory()->fullySetUp()->withStats()->create();
        $today = now()->toDateString();

        $page = Livewire::actingAs($user)->test('pages::rewards-dashboard');
        $page->call('claim', RewardClaimType::DailyLogin->value, $today)
            ->assertSet('loginClaimed', true)
            ->assertSet('xp', 10);
        $page->call('claim', RewardClaimType::DailyLogin->value, $today)
            ->assertSet('xp', 10);

        $this->assertSame(1, $this->eventCount($user, XpSource::DailyLogin, $today));
        $this->assertSame(10, UserStat::query()->where('user_id', $user->id)->first()?->coins);
    }

    public function test_seven_day_freeze_is_claimed_once_from_the_queue(): void
    {
        $user = User::factory()->fullySetUp()->create();
        $stats = app(UserStatService::class);
        $rewards = app(RewardService::class);

        for ($day = 17; $day <= 23; $day++) {
            $this->travelTo(CarbonImmutable::parse('2026-08-'.$day.' 10:00:00'));
            $stats->recordPlay($user, 40);
        }

        $stat = UserStat::query()->where('user_id', $user->id)->first();
        $this->assertSame(0, $stat?->streak_freezes);

        $first = $rewards->claim($user, RewardClaimType::Freeze, '7');
        $second = $rewards->claim($user, RewardClaimType::Freeze, '7');

        $this->assertTrue($first->paid);
        $this->assertFalse($second->paid);
        $this->assertSame(1, UserStat::query()->where('user_id', $user->id)->first()?->streak_freezes);
    }

    public function test_unseen_badge_claim_opens_unlock_and_does_not_mark_seen(): void
    {
        $this->withoutVite();
        $this->seed(BadgeSeeder::class);
        $user = User::factory()->fullySetUp()->withStats()->create();
        $badge = app(BadgeRepository::class)->findBySlug('first-win');
        $this->assertNotNull($badge);
        app(BadgeRepository::class)->award($user, $badge);

        Livewire::actingAs($user)
            ->test('pages::rewards-dashboard')
            ->call('claim', RewardClaimType::Badge->value, 'first-win')
            ->assertRedirect(route('badge-unlock', ['slug' => 'first-win']));

        $this->assertNull(
            app(BadgeRepository::class)->findUserBadge($user, $badge)?->seen_at,
        );
    }

    public function test_search_includes_the_rewards_dashboard(): void
    {
        $this->withoutVite();
        $user = User::factory()->fullySetUp()->create();

        Livewire::actingAs($user)
            ->test('pages::home')
            ->assertSee(__('home.search_to.rewards.name'), false);
    }

    public function test_award_xp_keeps_spendable_coins_in_lockstep(): void
    {
        $user = User::factory()->fullySetUp()->create();
        $stat = app(UserStatService::class)->awardXp($user, XpSource::Pack, 40, countsAsPlay: false);

        $this->assertSame(40, $stat->xp);
        $this->assertSame(40, $stat->coins);
    }

    private function eventCount(User $user, XpSource $source, ?string $context = null): int
    {
        $query = XpEvent::query()
            ->where('user_id', $user->id)
            ->where('source', $source);

        if ($context !== null) {
            $query->where('context', $context);
        }

        return $query->count();
    }
}
