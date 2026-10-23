<?php

namespace Tests\Feature;

use App\Enums\XpSource;
use App\Models\ShopItem;
use App\Models\User;
use App\Models\UserActivityDay;
use App\Models\UserBoost;
use App\Models\UserInventory;
use App\Models\UserStat;
use App\Services\ShopService;
use App\Services\UserStatService;
use Carbon\CarbonImmutable;
use Database\Seeders\ShopItemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RewardShopTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ShopItemSeeder::class);
    }

    public function test_shop_lists_categories_and_data_driven_tags(): void
    {
        $this->withoutVite();
        $user = $this->kid(900, 900);

        $page = Livewire::actingAs($user)->test('pages::rewards-dashboard');

        $page->assertSee(__('shop.heading'), false)
            ->assertSee(__('shop.filters.avatar'), false)
            ->assertSee(__('shop.filters.hat'), false)
            ->assertSee(__('shop.filters.background'), false)
            ->assertSee(__('shop.filters.boost'), false)
            ->assertSee(__('shop.filters.sound'), false)
            ->assertSee(__('shop.filters.shield'), false)
            ->assertSee(__('shop.items.avatar-dragon.name'), false)
            ->assertSee(__('shop.items.sound-fun.name'), false)
            ->assertSee(__('shop.items.shield.name'), false)
            ->assertSeeHtml('shop-tag hot')
            ->assertSeeHtml('shop-tag new')
            ->assertSeeHtml('shop-tag sale')
            ->assertSeeHtml('line-through')
            ->assertSee(__('shop.items.bundle-explorer.name'), false);

        ShopItem::query()->where('slug', 'avatar-dragon')->update(['tag' => null]);

        Livewire::actingAs($user)->test('pages::rewards-dashboard')
            ->assertDontSeeHtml('shop-tag hot');
    }

    public function test_filters_hide_other_categories(): void
    {
        $this->withoutVite();
        $user = $this->kid(100, 100);

        Livewire::actingAs($user)->test('pages::rewards-dashboard')
            ->call('setFilter', 'hat')
            ->assertSee(__('shop.items.hat-crown.name'), false)
            ->assertDontSee(__('shop.items.avatar-dragon.name'), false)
            ->call('setFilter', 'sale')
            ->assertSee(__('shop.items.boost-star.name'), false)
            ->assertSee(__('shop.items.bg-sunset.name'), false)
            ->assertDontSee(__('shop.items.shield.name'), false);
    }

    public function test_purchase_spends_coins_and_leaves_ranking_xp(): void
    {
        $this->withoutVite();
        $user = $this->kid(800, 800);

        Livewire::actingAs($user)->test('pages::rewards-dashboard')
            ->call('buy', 'shield')
            ->assertSet('xp', 800)
            ->assertSet('coins', 650)
            ->assertSet('shopNotice', '')
            ->assertSee(__('shop.bought', ['name' => __('shop.items.shield.name')]), false);

        $stat = $this->stat($user);
        $this->assertSame(800, $stat->xp);
        $this->assertSame(650, $stat->coins);
        $this->assertSame(1, $stat->streak_freezes);
        $this->assertGreaterThanOrEqual(0, $stat->coins);
    }

    public function test_purchase_cannot_go_negative(): void
    {
        $this->withoutVite();
        $user = $this->kid(400, 10);

        Livewire::actingAs($user)->test('pages::rewards-dashboard')
            ->call('buy', 'shield')
            ->assertSet('xp', 400)
            ->assertSet('coins', 10)
            ->assertSet('shopNotice', __('shop.insufficient'));

        $stat = $this->stat($user);
        $this->assertSame(400, $stat->xp);
        $this->assertSame(10, $stat->coins);
        $this->assertSame(0, $stat->streak_freezes);
    }

    public function test_overlapping_double_submit_charges_once(): void
    {
        $user = $this->kid(900, 500);
        $shop = app(ShopService::class);
        $second = null;

        $first = $shop->purchase($user, 'avatar-dragon', function () use ($shop, $user, &$second): void {
            $second = $shop->purchase($user, 'avatar-dragon');
        });

        $stat = $this->stat($user);

        $this->assertSame(0, $stat->coins);
        $this->assertSame(900, $stat->xp);
        $this->assertGreaterThanOrEqual(0, $stat->coins);
        $this->assertSame(1, UserInventory::query()->where('user_id', $user->id)->count());
        $this->assertNotNull($second);
        $this->assertSame(
            ['owned', 'purchased'],
            collect([$first->status, $second->status])->sort()->values()->all(),
        );
    }

    public function test_same_second_shield_purchase_is_idempotent_then_stacks_under_the_cap(): void
    {
        $user = $this->kid(1000, 1000);
        $shop = app(ShopService::class);

        $this->assertSame('purchased', $shop->purchase($user, 'shield')->status);
        $this->assertSame('owned', $shop->purchase($user, 'shield')->status);
        $this->assertSame(1, $this->stat($user)->streak_freezes);
        $this->assertSame(850, $this->stat($user)->coins);
        $this->assertSame(1000, $this->stat($user)->xp);

        $this->travel(2)->seconds();

        $this->assertSame('purchased', $shop->purchase($user, 'shield')->status);
        $this->assertSame(2, $this->stat($user)->streak_freezes);
        $this->assertSame(700, $this->stat($user)->coins);

        $this->stat($user)->update(['streak_freezes' => 3]);
        $coins = $this->stat($user)->coins;

        $this->travel(2)->seconds();

        $this->assertSame('capped', $shop->purchase($user, 'shield')->status);
        $this->assertSame(3, $this->stat($user)->streak_freezes);
        $this->assertSame($coins, $this->stat($user)->coins);
    }

    public function test_bought_shield_is_consumed_by_the_existing_freeze(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-08-17 10:00:00'));
        $user = $this->kid(0, 200);
        $stats = app(UserStatService::class);

        $stats->recordPlay($user, 40);
        $this->assertSame('purchased', app(ShopService::class)->purchase($user, 'shield')->status);
        $this->assertSame(1, $this->stat($user)->streak_freezes);
        $this->assertSame(40, $this->stat($user)->xp);

        $this->travelTo(CarbonImmutable::parse('2026-08-19 10:00:00'));
        $stat = $stats->recordPlay($user, 40);

        $this->assertSame(3, $stat->current_streak);
        $this->assertSame(0, $stat->streak_freezes);
        $this->assertGreaterThan(40, $stat->xp);
        $this->assertTrue(
            UserActivityDay::query()
                ->where('user_id', $user->id)
                ->whereDate('played_on', '2026-08-18')
                ->where('frozen', true)
                ->exists(),
        );
    }

    public function test_equipping_avatar_hat_and_background_changes_profile_and_edit_profile(): void
    {
        $this->withoutVite();
        $user = $this->kid(2000, 2000);
        $shop = app(ShopService::class);

        $shop->purchase($user, 'avatar-dragon');
        $shop->purchase($user, 'hat-crown');
        $shop->purchase($user, 'bg-rainbow');

        $user->refresh();
        $this->assertSame('🐲', $user->avatar);
        $this->assertSame(2000, $this->stat($user)->xp);
        $this->assertSame(2000 - 500 - 400 - 250, $this->stat($user)->coins);

        $gradient = 'linear-gradient(135deg, #7c5cff 0%, #4ed6a8 45%, #ff8fc5 100%)';

        Livewire::actingAs($user)->test('pages::profile')
            ->assertSee('🐲', false)
            ->assertSee('🎩', false)
            ->assertSee($gradient, false);

        Livewire::actingAs($user)->test('pages::edit-profile')
            ->assertSet('avatar', '🐲')
            ->assertSet('hat', '🎩')
            ->assertSee('🐲', false)
            ->assertSee('🎩', false)
            ->assertSee($gradient, false)
            ->set('avatar', '🦊')
            ->call('save');

        $user->refresh();
        $this->assertSame('🦊', $user->avatar);
        $this->assertFalse(
            UserInventory::query()
                ->where('user_id', $user->id)
                ->where('equipped', true)
                ->whereHas('item', fn ($query) => $query->where('slug', 'avatar-dragon'))
                ->exists(),
        );

        Livewire::actingAs($user)->test('pages::profile')
            ->assertSee('🦊', false)
            ->assertSee('🎩', false);

        $shop->purchase($user, 'avatar-peacock');

        Livewire::actingAs($user->fresh())->test('pages::edit-profile')
            ->set('avatar', '🦚')
            ->call('save')
            ->assertRedirect(route('profile'));

        $this->assertSame('🦚', $user->fresh()->avatar);

        Livewire::actingAs($user->fresh())->test('pages::profile')
            ->assertSee('🦚', false);
    }

    public function test_unowned_shop_avatar_cannot_be_saved(): void
    {
        $this->withoutVite();
        $user = $this->kid(100, 100);

        Livewire::actingAs($user)->test('pages::edit-profile')
            ->set('avatar', '🐲')
            ->call('save')
            ->assertHasErrors(['avatar']);

        $this->assertNotSame('🐲', $user->fresh()->avatar);
    }

    public function test_sound_pack_can_be_equipped(): void
    {
        $this->withoutVite();
        $user = $this->kid(400, 400);

        Livewire::actingAs($user)->test('pages::rewards-dashboard')
            ->call('buy', 'sound-fun')
            ->assertSee(__('shop.equipped'), false);

        $this->assertTrue(
            UserInventory::query()
                ->where('user_id', $user->id)
                ->where('equipped', true)
                ->whereHas('item', fn ($query) => $query->where('slug', 'sound-fun'))
                ->exists(),
        );
        $this->assertSame(400, $this->stat($user)->xp);
        $this->assertSame(100, $this->stat($user)->coins);
    }

    public function test_boost_doubles_xp_while_active_and_shows_on_the_dashboard(): void
    {
        $this->withoutVite();
        $this->travelTo(CarbonImmutable::parse('2026-09-28 10:00:00'));
        $user = $this->kid(0, 200);
        $stats = app(UserStatService::class);

        $this->assertSame('purchased', app(ShopService::class)->purchase($user, 'boost-star')->status);
        $this->assertSame(0, $this->stat($user)->xp);
        $this->assertSame(100, $this->stat($user)->coins);
        $this->assertSame(1, UserBoost::query()->where('user_id', $user->id)->count());

        $stats->awardXp($user, XpSource::Pack, 40, context: 'boost-on', countsAsPlay: false);

        $this->assertSame(80, $this->stat($user)->xp);
        $this->assertSame(180, $this->stat($user)->coins);

        $until = UserBoost::query()->where('user_id', $user->id)->firstOrFail()->ends_at->format('d.m H:i');

        Livewire::actingAs($user)->test('pages::rewards-dashboard')
            ->assertSee(__('shop.boost_active', ['until' => $until]), false)
            ->assertSee(__('shop.active'), false);

        $this->travelTo(CarbonImmutable::parse('2026-09-29 12:00:00'));

        $stats->awardXp($user, XpSource::Pack, 40, context: 'boost-off', countsAsPlay: false);

        $this->assertSame(120, $this->stat($user)->xp);
        $this->assertSame(220, $this->stat($user)->coins);

        Livewire::actingAs($user)->test('pages::rewards-dashboard')
            ->assertDontSee(__('shop.boost_active', ['until' => $until]), false);
    }

    public function test_bundle_charges_once_and_grants_its_items(): void
    {
        $this->withoutVite();
        $user = $this->kid(900, 2000);
        $shop = app(ShopService::class);

        $this->assertSame('purchased', $shop->purchase($user, 'bundle-explorer')->status);
        $this->assertSame('owned', $shop->purchase($user, 'bundle-explorer')->status);

        $this->assertSame(900, $this->stat($user)->xp);
        $this->assertSame(1300, $this->stat($user)->coins);
        $this->assertSame('🐲', $user->fresh()->avatar);
        $this->assertNotNull(UserBoost::query()->where('user_id', $user->id)->first());
        $this->assertTrue(
            UserInventory::query()
                ->where('user_id', $user->id)
                ->where('equipped', true)
                ->whereHas('item', fn ($query) => $query->where('slug', 'bg-rainbow'))
                ->exists(),
        );

        Livewire::actingAs($user->fresh())->test('pages::profile')
            ->assertSee('🐲', false)
            ->assertSee('linear-gradient(135deg, #7c5cff 0%, #4ed6a8 45%, #ff8fc5 100%)', false)
            ->assertSee('2× XP', false);
    }

    private function kid(int $xp, int $coins): User
    {
        return User::factory()->fullySetUp()->withStats([
            'xp' => $xp,
            'coins' => $coins,
        ])->create();
    }

    private function stat(User $user): UserStat
    {
        return UserStat::query()->where('user_id', $user->id)->firstOrFail();
    }
}
