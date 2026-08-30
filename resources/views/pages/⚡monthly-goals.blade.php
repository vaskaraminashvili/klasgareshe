<?php

use App\Repositories\UserRepository;
use App\Services\MonthlyGoalService;
use Illuminate\View\View;
use Livewire\Component;

new class extends Component
{
    public string $monthLabel = '';

    public int $monthPct = 0;

    public int $xpEarned = 0;

    public int $goalsHit = 0;

    public int $goalsTotal = 4;

    public int $xpPerDay = 0;

    public int $daysLeft = 0;

    public int $vsLastMonthPercent = 0;

    public string $insightTitle = '';

    public string $insightBody = '';

    public string $insightChip = '';

    public int $weeklyXpTotal = 0;

    /** @var list<array{label: string, value: int, height: int}> */
    public array $weeklyBars = [];

    /**
     * @var list<array{
     *     key: string,
     *     name: string,
     *     emoji: string,
     *     tile: string,
     *     progressClass: string,
     *     chipClass: string,
     *     current: int,
     *     target: int,
     *     unit: string,
     *     percent: int,
     *     statusLabel: string,
     *     done: bool
     * }>
     */
    public array $goals = [];

    public int $rewardPercent = 0;

    public int $rewardDone = 0;

    public int $rewardTotal = 4;

    public function title(): string
    {
        return __('monthly-goals.page_title');
    }

    public function rendering(View $view): void
    {
        $view->title($this->title());
    }

    public function mount(MonthlyGoalService $goals, UserRepository $users): void
    {
        $snap = $goals->snapshot($users->authenticated());

        $this->monthLabel = $snap->monthLabel;
        $this->monthPct = $snap->monthPct;
        $this->xpEarned = $snap->xpEarned;
        $this->goalsHit = $snap->goalsHit;
        $this->goalsTotal = $snap->goalsTotal;
        $this->xpPerDay = $snap->xpPerDay;
        $this->daysLeft = $snap->daysLeft;
        $this->vsLastMonthPercent = $snap->vsLastMonthPercent;
        $this->insightTitle = $snap->insightTitle;
        $this->insightBody = $snap->insightBody;
        $this->insightChip = $snap->insightChip;
        $this->weeklyXpTotal = $snap->weeklyXpTotal;
        $this->weeklyBars = $snap->weeklyBars;
        $this->goals = array_map(fn ($row) => $row->toArray(), $snap->goals);
        $this->rewardPercent = $snap->rewardPercent;
        $this->rewardDone = $snap->rewardDone;
        $this->rewardTotal = $snap->rewardTotal;
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top">

  <header class="appbar">
    <a href="{{ route('profile') }}" class="icon-btn" data-back aria-label="{{ __('monthly-goals.back') }}"><i class="ph ph-caret-left"></i></a>
    <div class="grow">
      <p class="text-xs text-muted">{{ __('monthly-goals.family_progress') }}</p>
      <h1 class="h-display text-lg leading-tight">{{ __('monthly-goals.monthly_goals') }}</h1>
    </div>
    <span class="chip chip-primary"><i class="ph-fill ph-calendar"></i> <span>{{ $monthLabel }}</span></span>
    <button type="button" class="icon-btn" data-theme-toggle aria-label="{{ __('monthly-goals.toggle_theme') }}"><i class="ph ph-moon text-xl"></i></button>
  </header>

  <!-- =============== HERO =============== -->
  <section class="px-5">
    <div class="k-card-lg hero-rewards text-center">
      <div class="relative inline-grid place-items-center">
        <div class="size-24 rounded-3xl bg-white/20 backdrop-blur-sm grid place-items-center text-5xl">🎯</div>
      </div>
      <p class="relative chip bg-white/20 border-0 text-white mt-4">
        <i class="ph-fill ph-sparkle"></i> {{ __('monthly-goals.so_far', ['month' => mb_strtoupper($monthLabel)]) }}
      </p>
      <p class="relative h-display text-4xl mt-2 leading-none text-white">{{ $monthPct }}%</p>
      <p class="relative text-xs text-white/90 mt-1">{{ __('monthly-goals.of_month_goals', ['month' => $monthLabel]) }}</p>

      <div class="relative mt-4 grid grid-cols-3 gap-2">
        <div class="rounded-2xl bg-white/15 backdrop-blur-sm p-3">
          <p class="h-display text-xl leading-none text-white">{{ number_format($xpEarned) }}</p>
          <p class="text-[10px] text-white/85 mt-1">{{ __('monthly-goals.xp_earned') }}</p>
        </div>
        <div class="rounded-2xl bg-white/15 backdrop-blur-sm p-3">
          <p class="h-display text-xl leading-none text-white">{{ $goalsHit }}/{{ $goalsTotal }}</p>
          <p class="text-[10px] text-white/85 mt-1">{{ __('monthly-goals.goals_hit') }}</p>
        </div>
        <div class="rounded-2xl bg-white/15 backdrop-blur-sm p-3">
          <p class="h-display text-xl leading-none text-white">{{ number_format($xpPerDay) }}</p>
          <p class="text-[10px] text-white/85 mt-1">{{ __('monthly-goals.xp_per_day') }}</p>
        </div>
      </div>

      <div class="relative mt-4 flex items-center gap-2 flex-wrap">
        <span class="chip bg-white/20 border-0 text-white"><i class="ph-fill ph-clock"></i> {{ __('monthly-goals.days_left', ['days' => $daysLeft]) }}</span>
        <span class="chip bg-white/20 border-0 text-white ml-auto"><i class="ph-fill ph-trend-up"></i> {{ __('monthly-goals.vs_last', ['sign' => $vsLastMonthPercent >= 0 ? '+' : '', 'pct' => abs($vsLastMonthPercent)]) }}</span>
      </div>
    </div>
  </section>

  <!-- =============== DONUT + INSIGHT =============== -->
  <section class="px-5 mt-5">
    <div class="section-head">
      <h2 class="h-display text-lg">{{ __('monthly-goals.month_progress', ['month' => $monthLabel]) }}</h2>
      <span class="link cursor-default">{{ $insightChip }}</span>
    </div>
    <div class="k-card p-4 mt-3 flex items-center gap-4">
      <div class="xp-ring shrink-0 size-28" style="--pct: {{ $monthPct }}">
        <div class="xp-ring-inner">
          <div>
            <p class="xp-level">{{ $monthPct }}%</p>
            <p class="xp-label">{{ $monthLabel }}</p>
          </div>
        </div>
      </div>
      <div class="grow min-w-0">
        <p class="font-extrabold text-sm text-ink">{{ $insightTitle }}</p>
        <p class="text-xs text-muted mt-1">{{ $insightBody }}</p>
        <div class="mt-2 flex items-center gap-2 flex-wrap">
          <span class="chip chip-mint">{{ ($vsLastMonthPercent >= 0 ? '+' : '') . $vsLastMonthPercent }}% {{ $vsLastMonthPercent >= 0 ? '↑' : '↓' }}</span>
          <span class="chip">{{ __('monthly-goals.vs_prev_month') }}</span>
        </div>
      </div>
    </div>
  </section>

  <!-- =============== WEEKLY XP =============== -->
  <section class="px-5 mt-5">
    <div class="section-head">
      <h2 class="h-display text-lg">{{ __('monthly-goals.weekly_xp') }}</h2>
      <span class="link cursor-default">{{ __('monthly-goals.weekly_total', ['xp' => number_format($weeklyXpTotal)]) }}</span>
    </div>
    <div class="k-card p-4">
      <div class="flex items-end gap-2 h-40">
        @foreach ($weeklyBars as $bar)
          <div class="grow flex flex-col items-center justify-end h-full gap-1">
            <span class="text-[10px] font-extrabold text-muted">{{ $bar['value'] > 0 ? number_format($bar['value']) : '' }}</span>
            <div class="w-full rounded-xl bg-[linear-gradient(180deg,#8E72FF,#49B8FF)]" style="height: {{ max(8, $bar['height']) }}%"></div>
            <span class="text-[10px] font-extrabold text-ink">{{ $bar['label'] }}</span>
          </div>
        @endforeach
      </div>
      <div class="mt-3 grid grid-cols-4 gap-2">
        @foreach ($weeklyBars as $bar)
          <span class="chip {{ $bar['value'] === max(array_column($weeklyBars, 'value')) && $bar['value'] > 0 ? 'chip-primary' : '' }}">{{ $bar['label'] }}</span>
        @endforeach
      </div>
    </div>
  </section>

  <!-- =============== GOALS =============== -->
  <section class="px-5 mt-5">
    <div class="section-head">
      <h2 class="h-display text-lg">{{ __('monthly-goals.goals_this_month') }}</h2>
      <span class="link cursor-default opacity-40">{{ __('monthly-goals.add') }}</span>
    </div>
    <div class="space-y-3 mt-3">
      @foreach ($goals as $goal)
        <button type="button" class="k-card flex items-center gap-3 w-full text-left">
          <div class="size-11 rounded-2xl {{ $goal['tile'] }} grid place-items-center text-xl shrink-0">{{ $goal['emoji'] }}</div>
          <div class="grow min-w-0">
            <div class="flex items-center gap-2">
              <p class="font-extrabold text-sm text-ink">{{ $goal['name'] }}</p>
              <span class="chip {{ $goal['chipClass'] }}">{{ $goal['percent'] }}%</span>
            </div>
            <p class="text-[11px] text-muted">{{ __('monthly-goals.goal_progress', [
                'current' => number_format($goal['current']),
                'target' => number_format($goal['target']),
                'status' => $goal['statusLabel'],
            ]) }}</p>
            <div class="progress {{ $goal['progressClass'] }} mt-1"><span style="width: {{ $goal['percent'] }}%"></span></div>
          </div>
          <i class="ph ph-caret-right text-muted shrink-0 opacity-30"></i>
        </button>
      @endforeach
    </div>
  </section>

  <!-- =============== REWARD CTA =============== -->
  <section class="px-5 mt-5 mb-5">
    <div class="k-card-lg hero-friends text-center">
      <div class="relative inline-grid place-items-center">
        <div class="size-20 rounded-3xl bg-white/20 backdrop-blur-sm grid place-items-center text-4xl">🎁</div>
      </div>
      <p class="relative h-display text-xl mt-3 leading-tight">{{ __('monthly-goals.finish_all_unlock', ['total' => $rewardTotal]) }}</p>
      <p class="relative text-xs text-white/90 mt-1">{{ __('monthly-goals.reward_subtitle', ['done' => $rewardDone, 'total' => $rewardTotal]) }}</p>
      <div class="relative mt-3 flex items-center gap-3">
        <div class="progress on-gradient grow"><span class="transition-all duration-700" style="width: {{ $rewardPercent }}%"></span></div>
        <span class="text-sm font-extrabold shrink-0">{{ $rewardPercent }}%</span>
      </div>
    </div>
  </section>

  <livewire:bottom-nav-bar />
</main>
