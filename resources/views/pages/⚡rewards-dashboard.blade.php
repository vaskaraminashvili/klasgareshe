<?php

use App\Data\RewardClaimCard;
use App\Data\RewardsDashboardSnapshot;
use App\Data\ShopBundleCard;
use App\Enums\RewardClaimType;
use App\Repositories\UserRepository;
use App\Services\RewardService;
use App\Services\ShopService;
use Illuminate\View\View;
use Livewire\Component;

new class extends Component
{
    public int $xp = 0;

    public int $coins = 0;

    public int $todayXp = 0;

    public int $weekXp = 0;

    public int $claimCount = 0;

    public int $badgeCount = 0;

    public int $badgeTotal = 1;

    public string $leagueLabel = '';

    public int $level = 1;

    public int $xpToNext = 0;

    public int $nextLevel = 2;

    /** @var list<array{type: string, reference: string, emoji: string, title: string, subtitle: string, action: string, buttonClass: string}> */
    public array $claims = [];

    /** @var list<array{letter: string, xp: int, state: string, emoji: string}> */
    public array $calendar = [];

    public int $calendarDay = 0;

    public bool $loginClaimed = false;

    public int $todayLoginXp = 0;

    public string $today = '';

    /** @var list<array{title: string, subtitle: string, emoji: string, tile: string, amount: int, spent: bool, when: string}> */
    public array $activity = [];

    public string $shopFilter = 'all';

    public string $shopNotice = '';

    public int $shopCount = 0;

    public string $boostUntil = '';

    /** @var list<array{slug: string, category: string, emoji: string, tile: string, name: string, description: string, price: int, listPrice: int|null, tag: string, tagClass: string, tagLabel: string, owned: bool, equipped: bool, action: string, enabled: bool, click: string}> */
    public array $shopItems = [];

    public ?string $bundleSlug = null;

    public string $bundleEmoji = '';

    public string $bundleName = '';

    public string $bundleKicker = '';

    public string $bundleBody = '';

    public int $bundlePrice = 0;

    public bool $bundleOwned = false;

    public function title(): string
    {
        return __('rewards.page_title');
    }

    public function rendering(View $view): void
    {
        $view->title($this->title());
    }

    public function mount(RewardService $rewards, ShopService $shop, UserRepository $users): void
    {
        $this->reload($rewards, $shop, $users);
    }

    public function claim(string $type, string $reference, RewardService $rewards, ShopService $shop, UserRepository $users): void
    {
        $claimType = RewardClaimType::tryFrom($type);

        if (! $claimType instanceof RewardClaimType) {
            return;
        }

        $result = $rewards->claim($users->authenticated(), $claimType, $reference);

        if (is_string($result->redirectSlug) && $result->redirectSlug !== '') {
            $this->redirectRoute('badge-unlock', ['slug' => $result->redirectSlug], navigate: true);

            return;
        }

        $this->reload($rewards, $shop, $users);
    }

    public function setFilter(string $filter): void
    {
        $allowed = ['all', 'avatar', 'hat', 'background', 'boost', 'sound', 'shield', 'sale'];

        if (in_array($filter, $allowed, true)) {
            $this->shopFilter = $filter;
        }
    }

    public function buy(string $slug, RewardService $rewards, ShopService $shop, UserRepository $users): void
    {
        if (preg_match('/^[a-z0-9-]{1,64}$/', $slug) !== 1) {
            return;
        }

        $result = $shop->purchase($users->authenticated(), $slug);
        $this->shopNotice = match ($result->status) {
            'insufficient' => (string) __('shop.insufficient'),
            'capped' => (string) __('shop.capped'),
            default => '',
        };
        $this->reload($rewards, $shop, $users);
    }

    public function equip(string $slug, RewardService $rewards, ShopService $shop, UserRepository $users): void
    {
        if (preg_match('/^[a-z0-9-]{1,64}$/', $slug) !== 1) {
            return;
        }

        $shop->equip($users->authenticated(), $slug);
        $this->shopNotice = '';
        $this->reload($rewards, $shop, $users);
    }

    /**
     * @return list<array{slug: string, category: string, emoji: string, tile: string, name: string, description: string, price: int, listPrice: int|null, tag: string, tagClass: string, tagLabel: string, owned: bool, equipped: bool, action: string, enabled: bool, click: string}>
     */
    public function visibleShopItems(): array
    {
        return array_values(array_filter(
            $this->shopItems,
            function (array $item): bool {
                if ($this->shopFilter === 'all') {
                    return true;
                }

                if ($this->shopFilter === 'sale') {
                    return $item['tag'] === 'sale';
                }

                return $item['category'] === $this->shopFilter;
            },
        ));
    }

    private function fillFrom(RewardsDashboardSnapshot $snap): void
    {
        $this->xp = $snap->xp;
        $this->coins = $snap->coins;
        $this->todayXp = $snap->todayXp;
        $this->weekXp = $snap->weekXp;
        $this->claimCount = $snap->claimCount;
        $this->badgeCount = $snap->badgeCount;
        $this->badgeTotal = $snap->badgeTotal;
        $this->leagueLabel = $snap->leagueLabel;
        $this->level = $snap->level;
        $this->xpToNext = $snap->xpToNext;
        $this->nextLevel = $snap->nextLevel;
        $this->claims = array_map(
            fn (RewardClaimCard $card) => $card->toArray(),
            $snap->claims,
        );
        $this->calendar = $snap->calendar;
        $this->calendarDay = $snap->calendarDay;
        $this->loginClaimed = $snap->loginClaimed;
        $this->todayLoginXp = $snap->todayLoginXp;
        $this->today = $snap->today;
        $this->activity = $snap->activity;
    }

    private function reload(RewardService $rewards, ShopService $shop, UserRepository $users): void
    {
        $user = $users->authenticated();
        $this->fillFrom($rewards->dashboard($user));
        $page = $shop->page($user);
        $this->shopItems = $page['items'];
        $this->shopCount = $page['count'];
        $this->boostUntil = $page['boostUntil'];
        $this->fillBundle($page['bundle']);
    }

    private function fillBundle(?ShopBundleCard $bundle): void
    {
        if (! $bundle instanceof ShopBundleCard) {
            $this->bundleSlug = null;
            $this->bundleEmoji = '';
            $this->bundleName = '';
            $this->bundleKicker = '';
            $this->bundleBody = '';
            $this->bundlePrice = 0;
            $this->bundleOwned = false;

            return;
        }

        $this->bundleSlug = $bundle->slug;
        $this->bundleEmoji = $bundle->emoji;
        $this->bundleName = $bundle->name;
        $this->bundleKicker = $bundle->kicker;
        $this->bundleBody = $bundle->body;
        $this->bundlePrice = $bundle->price;
        $this->bundleOwned = $bundle->owned;
    }
};
?>

<main class="device-frame min-h-screen flex flex-col">

  <!-- =============== APPBAR =============== -->
  <header class="appbar safe-top">
    <div class="grow">
      <p class="text-xs text-muted">{{ __('rewards.your_treasures') }}</p>
      <h1 class="h-display text-2xl leading-tight">{{ __('rewards.rewards') }}</h1>
    </div>
    <a href="{{ route('xp-progress') }}" wire:navigate class="icon-btn" aria-label="{{ __('rewards.history') }}"><i class="ph ph-clock-counter-clockwise text-xl"></i></a>
    <button type="button" class="icon-btn" data-theme-toggle aria-label="{{ __('rewards.toggle_theme') }}"><i class="ph ph-moon text-xl"></i></button>
  </header>

  <!-- =============== WALLET HERO =============== -->
  <section class="px-5">
    <div class="k-card-lg hero-rewards">
      <div class="relative flex items-center gap-3">
        <div class="text-5xl">⭐</div>
        <div class="grow">
          <span class="chip bg-white/20 border-0 text-white">
            <i class="ph-fill ph-wallet"></i> {{ __('rewards.your_balance') }}
          </span>
          <p class="h-display text-4xl mt-1 leading-none">{{ number_format($coins) }} <span class="text-xl opacity-80">{{ __('rewards.xp') }}</span></p>
          <p class="text-xs text-white/90 mt-1"><i class="ph-fill ph-trend-up"></i> {{ __('rewards.today_keep', ['xp' => $todayXp]) }}</p>
        </div>
      </div>

      <div class="relative mt-4 grid grid-cols-3 gap-2">
        <div class="hero-metric">
          <p class="hm-v">{{ $claimCount }}</p>
          <p class="hm-l">{{ __('rewards.to_claim') }}</p>
        </div>
        <div class="hero-metric">
          <p class="hm-v">{{ $badgeCount }}</p>
          <p class="hm-l">{{ __('rewards.badges') }}</p>
        </div>
        <div class="hero-metric">
          <p class="hm-v">{{ $leagueLabel }}</p>
          <p class="hm-l">{{ __('rewards.league') }}</p>
        </div>
      </div>

      <div class="relative mt-4 flex items-center gap-2">
        <a href="{{ route('xp-progress') }}" wire:navigate class="cta-soft">{{ __('rewards.view_xp') }} <i class="ph-fill ph-arrow-right"></i></a>
        @if ($boostUntil !== '')
        <span class="chip bg-white/20 border-0 text-white">{{ __('shop.boost_active', ['until' => $boostUntil]) }}</span>
        @endif
        <span class="chip bg-white/20 border-0 text-white ml-auto">
          {{ __('rewards.level_to_next', ['level' => $level, 'xp' => number_format($xpToNext), 'next' => $nextLevel]) }}
        </span>
      </div>
    </div>
  </section>

  <!-- =============== QUICK SHORTCUTS =============== -->
  <section class="px-5 mt-4 grid grid-cols-3 gap-3 text-center">
    <a href="{{ route('badges') }}" wire:navigate class="k-card p-3">
      <div class="size-10 rounded-2xl tile-sun grid place-items-center text-xl mx-auto">🏅</div>
      <p class="h-display text-lg mt-1">{{ $badgeCount }} / {{ $badgeTotal }}</p>
      <p class="text-[11px] text-muted font-extrabold">{{ __('rewards.badges') }}</p>
    </a>
    <a href="{{ route('xp-progress') }}" wire:navigate class="k-card p-3">
      <div class="size-10 rounded-2xl tile-violet grid place-items-center text-xl mx-auto">📈</div>
      <p class="h-display text-lg mt-1">+{{ $weekXp }}</p>
      <p class="text-[11px] text-muted font-extrabold">{{ __('rewards.this_week') }}</p>
    </a>
    <a href="{{ route('league') }}" wire:navigate class="k-card p-3">
      <div class="size-10 rounded-2xl tile-mint grid place-items-center text-xl mx-auto">🏆</div>
      <p class="h-display text-lg mt-1">{{ $leagueLabel }}</p>
      <p class="text-[11px] text-muted font-extrabold">{{ __('rewards.league') }}</p>
    </a>
  </section>

  <!-- =============== READY TO CLAIM =============== -->
  @if (count($claims) > 0)
  <section class="mt-5">
    <div class="section-head px-5">
      <h2 class="h-display text-lg">{{ __('rewards.ready_to_claim') }}</h2>
      <span class="chip chip-coral">{{ __('rewards.new_count', ['count' => count($claims)]) }}</span>
    </div>
    <div data-swiper-rail class="swiper rail-swiper">
      <div class="swiper-wrapper">
      @foreach ($claims as $card)
      <div wire:key="claim-{{ $card['type'] }}-{{ $card['reference'] }}" class="swiper-slide claim-card">
        <span class="claim-gift">{{ $card['emoji'] }}</span>
        <p class="font-extrabold text-sm mt-2">{{ $card['title'] }}</p>
        <p class="text-[11px] text-muted">{{ $card['subtitle'] }}</p>
        <button type="button" class="btn {{ $card['buttonClass'] }} h-9 min-h-0 w-full mt-3 text-xs" wire:click="claim('{{ $card['type'] }}', '{{ $card['reference'] }}')">{{ $card['action'] }}</button>
      </div>
      @endforeach
    </div>
    </div>
  </section>
  @endif

  <!-- =============== DAILY REWARD CALENDAR =============== -->
  <section class="px-5 mt-5">
    <div class="section-head">
      <h2 class="h-display text-lg">{{ __('rewards.daily_login') }}</h2>
      <span class="chip chip-sun">{{ __('rewards.calendar_day', ['day' => $calendarDay]) }}</span>
    </div>
    <div class="grid grid-cols-7 gap-2">
      @foreach ($calendar as $day)
      <div class="reward-day {{ $day['state'] }}">
        <span class="rd-day">{{ $day['letter'] }}</span>
        <span class="rd-emoji">{{ $day['emoji'] }}</span>
        <span class="rd-xp">@if ($day['xp'] > 0)+{{ $day['xp'] }}@endif</span>
      </div>
      @endforeach
    </div>
    @if ($loginClaimed)
    <button type="button" class="btn btn-primary w-full mt-3 h-11 min-h-0 text-sm" disabled>{{ __('rewards.collected_today') }}</button>
    @else
    <button type="button" class="btn btn-primary w-full mt-3 h-11 min-h-0 text-sm" wire:click="claim('daily_login', '{{ $today }}')">{{ __('rewards.collect_today') }}</button>
    @endif
  </section>

  <!-- =============== SHOP FILTERS =============== -->
  <section class="px-5 mt-5">
    <div class="section-head">
      <h2 class="h-display text-lg">{{ __('shop.heading') }}</h2>
      <button type="button" wire:click="setFilter('all')" class="link">{{ __('shop.all_count', ['count' => $shopCount]) }}</button>
    </div>
    <div class="swiper rail-swiper" data-swiper-rail-tabs data-tabs>
      <div class="swiper-wrapper">
        @foreach (['all', 'avatar', 'hat', 'background', 'boost', 'sound', 'shield', 'sale'] as $filter)
        <button type="button" wire:click="setFilter('{{ $filter }}')" wire:key="shop-filter-{{ $filter }}" class="swiper-slide chip{{ $shopFilter === $filter ? ' chip-primary' : '' }}">{{ __('shop.filters.'.$filter) }}</button>
        @endforeach
      </div>
    </div>
  </section>

  <!-- =============== SHOP GRID =============== -->
  <section class="px-5 mt-3">
    @if ($shopNotice !== '')
    <p class="text-sm mb-3" style="color:var(--color-k-coral)">{{ $shopNotice }}</p>
    @endif
    <div class="grid grid-cols-2 gap-3">
      @forelse ($this->visibleShopItems() as $item)
      <div wire:key="shop-{{ $item['slug'] }}" class="shop-card">
        @if ($item['tagClass'] !== '')
        <span class="shop-tag {{ $item['tagClass'] }}">{{ $item['tagLabel'] }}</span>
        @endif
        <div class="shop-emoji {{ $item['tile'] }}">{{ $item['emoji'] }}</div>
        <p class="shop-name">{{ $item['name'] }}</p>
        <p class="shop-desc">{{ $item['description'] }}</p>
        <p class="shop-price">⭐ {{ $item['price'] }} {{ __('rewards.xp') }}@if ($item['listPrice'] !== null) <span class="text-muted line-through font-normal">{{ $item['listPrice'] }}</span>@endif</p>
        <button type="button" class="btn btn-soft h-9 min-h-0 w-full mt-3 text-xs" @disabled(! $item['enabled']) @if ($item['click'] !== '') wire:click="{{ $item['click'] }}" wire:loading.attr="disabled" @endif>{{ $item['action'] }}</button>
      </div>
      @empty
      <p class="text-xs text-muted col-span-2">{{ __('shop.empty') }}</p>
      @endforelse
    </div>
  </section>

  <!-- =============== LIMITED-TIME BUNDLE =============== -->
  @if ($bundleSlug !== null)
  <section class="px-5 mt-5">
    <button type="button" class="k-card-lg card-hero-flame relative overflow-hidden block w-full text-left" @if (! $bundleOwned) wire:click="buy('{{ $bundleSlug }}')" wire:loading.attr="disabled" @else disabled @endif>
      <span class="watermark-emoji" aria-hidden="true">{{ $bundleEmoji }}</span>
      <div class="relative flex items-center gap-3">
        <div class="text-4xl">{{ $bundleEmoji }}</div>
        <div class="grow">
          <p class="text-xs uppercase font-extrabold tracking-wider opacity-90">{{ $bundleKicker }}</p>
          <p class="h-display text-lg leading-tight">{{ $bundleName }}</p>
          <p class="text-xs text-white/90">{{ $bundleBody }}</p>
        </div>
        <span class="cta-soft shrink-0">{{ $bundleOwned ? __('shop.equipped') : $bundlePrice.' '.__('rewards.xp') }}</span>
      </div>
    </button>
  </section>
  @endif

  <!-- =============== RECENT ACTIVITY =============== -->
  <section class="px-5 mt-5">
    <div class="section-head">
      <h2 class="h-display text-lg">{{ __('rewards.recent_activity') }}</h2>
      <a href="{{ route('xp-progress') }}" wire:navigate class="link">{{ __('rewards.see_all') }}</a>
    </div>
    <div class="space-y-2">
      @forelse ($activity as $row)
      <div class="activity-row">
        <div class="act-ico {{ $row['tile'] }}">{{ $row['emoji'] }}</div>
        <div class="grow min-w-0">
          <p class="font-extrabold text-sm text-ink">{{ $row['title'] }}</p>
          <p class="text-[11px] text-muted">{{ $row['subtitle'] }}</p>
        </div>
        <div class="text-right shrink-0">
          <span class="xp-pill{{ $row['spent'] ? ' spend' : '' }}">{{ $row['spent'] ? '-' : '+' }}{{ $row['amount'] }}</span>
          <p class="text-[10px] text-muted mt-1">{{ $row['when'] }}</p>
        </div>
      </div>
      @empty
      <p class="text-xs text-muted">{{ __('rewards.no_activity') }}</p>
      @endforelse
    </div>
  </section>

  <!-- =============== EARN MORE CTA =============== -->
  <section class="px-5 mt-5">
    <a href="{{ route('daily-mission') }}" wire:navigate class="k-card-lg card-hero-success relative overflow-hidden block">
      <span class="watermark-emoji" aria-hidden="true">🚀</span>
      <div class="relative flex items-center gap-3">
        <div class="text-4xl">🚀</div>
        <div class="grow">
          <p class="text-xs uppercase font-extrabold tracking-wider opacity-90">{{ __('rewards.earn_more') }}</p>
          <p class="h-display text-lg leading-tight">{{ __('rewards.finish_mission') }}</p>
          <p class="text-xs text-white/90">{{ __('rewards.mission_reward') }}</p>
        </div>
        <span class="cta-soft shrink-0">{{ __('rewards.start') }}</span>
      </div>
    </a>
  </section>

  <!-- =============== PARENT TIP =============== -->
  <section class="px-5 mt-5 mb-5">
    <div class="tip-card rounded-2xl p-4 flex items-start gap-3">
      <div class="mascot shrink-0 size-11 text-xl">🦉</div>
      <div class="grow">
        <p class="font-extrabold text-sm text-ink">{{ __('rewards.no_real_money') }}</p>
        <p class="text-xs text-muted">{{ __('rewards.no_real_money_body') }}</p>
      </div>
      <a href="{{ route('settings') }}" wire:navigate class="chip chip-primary">{{ __('rewards.learn_more') }}</a>
    </div>
  </section>

  <livewire:bottom-nav-bar />
</main>
