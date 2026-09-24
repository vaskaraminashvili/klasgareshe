<?php

use App\Repositories\UserRepository;
use App\Services\FriendshipService;
use App\Services\UserStatService;
use Illuminate\View\View;
use Livewire\Component;

new class extends Component
{
    public int $current = 0;

    public int $best = 0;

    public int $weekActiveDays = 0;

    /** @var list<array{letter: string, on: bool, today: bool, dayNum: int, ico: string}> */
    public array $weekDays = [];

    public string $leagueLabel = '';

    public bool $playedToday = false;

    public bool $grewFromYesterday = false;

    public string $heroLine = '';

    public string $checkInTitle = '';

    public string $checkInMeta = '';

    public string $monthLabel = '';

    public int $monthHits = 0;

    /** @var list<array{empty: bool, day: int, on: bool, miss: bool, today: bool}> */
    public array $calendarCells = [];

    /** @var list<array{days: int, name: string, hint: string, status: string, chip: string, ico: string, percent: int}> */
    public array $milestones = [];

    public int $milestonesDone = 0;

    public int $milestonesTotal = 5;

    public int $freezes = 0;

    public int $freezeCap = 3;

    public bool $canUseFreeze = false;

    /** @var list<array{name: string, avatar: string, streak: int, longest: int, isYou: bool, subtitle: string}> */
    public array $friendFlames = [];

    public string $chartJson = '[]';

    public function title(): string
    {
        return __('streak.page_title');
    }

    public function rendering(View $view): void
    {
        $view->title($this->title());
    }

    public function mount(UserStatService $stats, FriendshipService $friends, UserRepository $users): void
    {
        $this->syncStreak($stats, $friends, $users);
    }

    public function useFreeze(UserStatService $stats, FriendshipService $friends, UserRepository $users): void
    {
        $stats->useFreeze($users->authenticated());
        $this->syncStreak($stats, $friends, $users);
    }

    private function syncStreak(UserStatService $stats, FriendshipService $friends, UserRepository $users): void
    {
        $user = $users->authenticated();
        $snap = $stats->streakSnapshot($user, $friends->friendFlames($user));

        $this->current = $snap->current;
        $this->best = $snap->best;
        $this->weekActiveDays = $snap->weekActiveDays;
        $this->weekDays = $snap->weekDays;
        $this->leagueLabel = $snap->leagueLabel;
        $this->playedToday = $snap->playedToday;
        $this->grewFromYesterday = $snap->grewFromYesterday;
        $this->heroLine = $snap->heroLine;
        $this->checkInTitle = $snap->checkInTitle;
        $this->checkInMeta = $snap->checkInMeta;
        $this->monthLabel = $snap->monthLabel;
        $this->monthHits = $snap->monthHits;
        $this->calendarCells = $snap->calendarCells;
        $this->milestones = $snap->milestones;
        $this->milestonesDone = $snap->milestonesDone;
        $this->milestonesTotal = $snap->milestonesTotal;
        $this->freezes = $snap->freezes;
        $this->freezeCap = $snap->freezeCap;
        $this->canUseFreeze = $snap->canUseFreeze;
        $this->friendFlames = $snap->friendFlames;
        $this->chartJson = $snap->chartJson;
    }
};
?>

<main class="device-frame min-h-screen flex flex-col">

  <header class="appbar safe-top">
    <a href="{{ route('home') }}" class="icon-btn" data-back aria-label="{{ __('streak.daily_streak') }}"><i class="ph ph-caret-left"></i></a>
    <div class="grow">
      <p class="text-xs text-muted">{{ __('streak.learning_flame') }}</p>
      <h1 class="h-display text-lg leading-tight">{{ __('streak.daily_streak') }}</h1>
    </div>
    <button type="button" class="icon-btn" aria-label="{{ __('streak.share') }}"><i class="ph ph-share-fat text-xl"></i></button>
    <button type="button" class="icon-btn" data-theme-toggle aria-label="{{ __('streak.toggle_theme') }}"><i class="ph ph-moon text-xl"></i></button>
  </header>

  <section class="px-5">
    <div class="hero-streak">
      <span class="flame-emoji" aria-hidden="true">🔥</span>
      <p class="streak-number relative">{{ $current }}</p>
      <p class="text-sm font-extrabold text-white/95 mt-1 relative">{{ $heroLine }}</p>

      @if ($grewFromYesterday)
        <div class="relative mt-4 inline-flex items-center gap-2 bg-white/20 backdrop-blur-sm rounded-full px-4 py-1.5">
          <i class="ph-fill ph-trend-up"></i>
          <span class="text-xs font-extrabold">{{ __('streak.plus_one_yesterday') }}</span>
        </div>
      @endif

      <div class="relative mt-5 grid grid-cols-3 gap-2 text-white">
        <div class="bg-white/20 rounded-2xl py-2">
          <p class="h-display text-lg leading-none">{{ $best }}</p>
          <p class="text-[10px] font-extrabold uppercase tracking-wide opacity-90">{{ __('streak.best') }}</p>
        </div>
        <div class="bg-white/20 rounded-2xl py-2">
          <p class="h-display text-lg leading-none">{{ __('streak.week_frac', ['done' => $weekActiveDays]) }}</p>
          <p class="text-[10px] font-extrabold uppercase tracking-wide opacity-90">{{ __('streak.this_week') }}</p>
        </div>
        <div class="bg-white/20 rounded-2xl py-2">
          <p class="h-display text-lg leading-none">{{ $leagueLabel }}</p>
          <p class="text-[10px] font-extrabold uppercase tracking-wide opacity-90">{{ __('streak.league') }}</p>
        </div>
      </div>
    </div>
  </section>

  <section class="px-5 mt-4">
    <a href="{{ route('daily-mission') }}" wire:navigate class="k-card-lg card-hero-primary relative overflow-hidden block">
      <span class="watermark-emoji" aria-hidden="true">🔥</span>
      <div class="relative flex items-center gap-3">
        <div class="size-12 rounded-2xl bg-white/25 grid place-items-center text-2xl shrink-0">{{ $playedToday ? '✅' : '🎯' }}</div>
        <div class="grow">
          <p class="text-xs uppercase font-extrabold tracking-wider opacity-90">{{ __('streak.todays_checkin') }}</p>
          <p class="h-display text-lg leading-tight">{{ $checkInTitle }}</p>
          <p class="text-xs text-white/90">{{ $checkInMeta }}</p>
        </div>
        <span class="cta-soft shrink-0">{{ __('streak.start') }}</span>
      </div>
    </a>
  </section>

  <section class="px-5 mt-5">
    <div class="section-head">
      <h2 class="h-display text-lg">{{ __('streak.this_week') }}</h2>
      <span class="chip chip-primary">{{ __('streak.week_frac', ['done' => $weekActiveDays]) }}</span>
    </div>
    <div class="grid grid-cols-7 gap-2">
      @foreach ($weekDays as $day)
        <div @class(['day-cap', 'hit' => $day['on'], 'today' => $day['today']])>
          <span class="day-letter">{{ $day['letter'] }}</span>
          <span class="day-ico">{{ $day['ico'] }}</span>
          <span class="day-num">{{ $day['dayNum'] }}</span>
        </div>
      @endforeach
    </div>
  </section>

  <section class="px-5 mt-5">
    <div class="section-head">
      <h2 class="h-display text-lg">{{ __('streak.activity_trend') }}</h2>
      <span class="link cursor-default">{{ __('streak.last_7_days') }}</span>
    </div>
    <div class="k-card text-ink">
      <div id="streakChart" data-days='@json(json_decode($chartJson, true))'></div>
    </div>
  </section>

  <section class="px-5 mt-5">
    <div class="section-head">
      <h2 class="h-display text-lg">{{ __('streak.streak_milestones') }}</h2>
      <span class="link cursor-default">{{ $milestonesDone }} / {{ $milestonesTotal }}</span>
    </div>
    <div class="space-y-3">
      @foreach ($milestones as $row)
        <div class="milestone {{ $row['status'] }}">
          <div class="m-ico {{ $row['ico'] }}">{{ $row['days'] }}</div>
          <div class="grow">
            <p class="font-extrabold text-sm text-ink">{{ $row['name'] }}</p>
            <p class="text-xs text-muted">{{ $row['hint'] }}</p>
            @if ($row['status'] !== 'done')
              <div class="progress mt-1"><span style="width: {{ $row['percent'] }}%"></span></div>
            @endif
          </div>
          <span @class([
            'chip',
            'chip-mint' => $row['status'] === 'done',
            'chip-primary' => $row['status'] === 'current',
            'chip-sun' => $row['status'] === 'upcoming' && $row['days'] === 14,
          ])>{{ $row['chip'] }}</span>
        </div>
      @endforeach
    </div>
  </section>

  <section class="px-5 mt-5">
    <div class="section-head">
      <h2 class="h-display text-lg">{{ $monthLabel }}</h2>
      <span class="chip chip-sun">{{ __('streak.days_count', ['n' => $monthHits]) }}</span>
    </div>
    <div class="k-card p-4">
      <div class="flex items-center justify-between text-[10px] font-extrabold text-muted uppercase tracking-wide mb-2">
        <span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span><span>S</span>
      </div>
      <div class="calendar-mini">
        @foreach ($calendarCells as $cell)
          <span @class(['cal-cell', 'empty' => $cell['empty'], 'on' => $cell['on'], 'miss' => $cell['miss'], 'today' => $cell['today']])>
            {{ $cell['empty'] ? '' : $cell['day'] }}
          </span>
        @endforeach
      </div>
      <div class="mt-3 flex items-center gap-3 text-xs text-muted">
        <span class="inline-flex items-center gap-1.5"><span class="size-3 rounded-md bg-gradient-to-b from-[#FFD66B] to-[#FF7A2E]"></span> {{ __('streak.hit') }}</span>
        <span class="inline-flex items-center gap-1.5"><span class="size-3 rounded-md bg-[rgba(255,91,115,.2)]"></span> {{ __('streak.miss') }}</span>
        <span class="inline-flex items-center gap-1.5"><span class="size-3 rounded-md border-2 border-[color:var(--color-k-primary)]"></span> {{ __('streak.today') }}</span>
      </div>
    </div>
  </section>

  <section class="px-5 mt-5">
    <div class="k-card-lg freeze-card">
      <span class="freeze-glow" aria-hidden="true"></span>
      <div class="relative flex items-center gap-3">
        <div class="text-5xl">🛡️</div>
        <div class="grow">
          <p class="text-xs uppercase font-extrabold tracking-wider">{{ __('streak.streak_freeze') }}</p>
          <p class="h-display text-xl leading-tight">{{ __('streak.freeze_title') }}</p>
          <p class="text-xs opacity-90">
            {{ $freezes > 0
              ? __('streak.freeze_meta', ['n' => $freezes])
              : __('streak.freeze_none') }}
          </p>
        </div>
      </div>
      <div class="relative mt-3 flex items-center gap-2">
        <div class="flex-1 flex items-center gap-2">
          @for ($i = 0; $i < $freezeCap; $i++)
            <span @class(['size-9 rounded-full grid place-items-center text-lg', 'bg-white/70' => $i < $freezes, 'bg-white/30 opacity-60' => $i >= $freezes])>🛡️</span>
          @endfor
        </div>
        <button type="button" wire:click="useFreeze" @disabled(! $canUseFreeze) class="btn btn-primary h-10 min-h-0 px-4 text-sm">{{ __('streak.use_freeze') }}</button>
      </div>
    </div>
  </section>

  <section class="px-5 mt-5">
    <div class="section-head">
      <h2 class="h-display text-lg">{{ __('streak.friend_flames') }}</h2>
      <a href="{{ route('ranking-friends') }}" wire:navigate class="link">{{ __('streak.see_all') }}</a>
    </div>
    <div class="k-card p-0 overflow-hidden">
      @foreach ($friendFlames as $row)
        <div @class([
          'flex items-center gap-3 p-3',
          'border-t border-token' => ! $loop->first,
          'bg-[rgba(124,92,255,.06)] dark:bg-[rgba(124,92,255,.14)]' => $row['isYou'],
        ])>
          <div class="size-10 rounded-full tile-sun grid place-items-center text-lg shrink-0">{{ $row['avatar'] }}</div>
          <div class="grow">
            <p class="font-extrabold text-sm">{{ $row['name'] }}</p>
            <p class="text-[11px] text-muted">{{ $row['subtitle'] }}</p>
          </div>
          <span @class(['chip', 'chip-primary' => $row['isYou'], 'chip-sun' => ! $row['isYou']])>🔥 {{ $row['streak'] }}</span>
        </div>
      @endforeach
    </div>
  </section>

  <section class="px-5 mt-5 mb-5">
    <div class="tip-card rounded-2xl p-4 flex items-start gap-3">
      <div class="mascot shrink-0 size-11 text-xl">🦉</div>
      <div class="grow">
        <p class="font-extrabold text-sm text-ink">{{ __('streak.streak_secret') }}</p>
        <p class="text-xs text-muted">{{ __('streak.streak_secret_body') }}</p>
      </div>
      <a href="{{ route('settings') }}" wire:navigate class="chip chip-primary">{{ __('streak.remind') }}</a>
    </div>
  </section>

  <livewire:bottom-nav-bar />
</main>

@push('scripts')
    <script src="{{ asset('assets/js/charts.js') }}"></script>
    <script src="{{ asset('assets/js/streak.js') }}"></script>
@endpush
