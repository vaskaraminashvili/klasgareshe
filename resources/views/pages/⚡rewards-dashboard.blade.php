<?php

use App\Data\RewardClaimCard;
use App\Data\RewardsDashboardSnapshot;
use App\Enums\RewardClaimType;
use App\Repositories\UserRepository;
use App\Services\RewardService;
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

    public function title(): string
    {
        return __('rewards.page_title');
    }

    public function rendering(View $view): void
    {
        $view->title($this->title());
    }

    public function mount(RewardService $rewards, UserRepository $users): void
    {
        $this->fillFrom($rewards->dashboard($users->authenticated()));
    }

    public function claim(string $type, string $reference, RewardService $rewards, UserRepository $users): void
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

        $this->fillFrom($rewards->dashboard($users->authenticated()));
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
          <p class="h-display text-4xl mt-1 leading-none">{{ number_format($xp) }} <span class="text-xl opacity-80">{{ __('rewards.xp') }}</span></p>
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

  {{-- Reward shop filters, grid, and limited bundle: dummy catalog.
       Re-port from the template in docs/tasks/T20-reward-shop.md. --}}

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
