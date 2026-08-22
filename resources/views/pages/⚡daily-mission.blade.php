<?php

use App\Repositories\UserRepository;
use App\Services\UserStatService;
use App\Services\WeekPlanService;
use Livewire\Component;

new class extends Component
{
    public int $missionDone = 0;

    public int $missionTotal = 3;

    public int $hoursLeft = 0;

    public int $weekCompleted = 0;

    public int $weekTotal = 0;

    public int $streak = 0;

    /** @var list<array{letter: string, on: bool, today: bool}> */
    public array $weekDays = [];

    /**
     * @var list<array{id: int, weekday: int, subject: string, title: string, completed: bool, playable: bool, emoji: string, completedAt: string|null}>
     */
    public array $items = [];

    public function title(): string
    {
        return __('daily-mission.page_title');
    }

    public function rendering(\Illuminate\View\View $view): void
    {
        $view->title($this->title());
    }

    public function mount(WeekPlanService $week, UserStatService $stats, UserRepository $users): void
    {
        $user = $users->authenticated();
        $home = $stats->homeSnapshot($user);
        $mission = $week->dailyMission($user);

        $this->missionDone = $mission->missionDone;
        $this->missionTotal = $mission->missionTotal;
        $this->hoursLeft = $mission->hoursLeft;
        $this->weekCompleted = $mission->weekCompleted;
        $this->weekTotal = $mission->weekTotal;
        $this->streak = $home->streak;
        $this->weekDays = $home->weekDays;
        $this->items = [];

        foreach ($mission->items as $item) {
            $this->items[] = [
                'id' => $item->id,
                'weekday' => $item->weekday,
                'subject' => $item->subject->label(),
                'title' => $item->title,
                'completed' => $item->completed,
                'playable' => $item->playable,
                'emoji' => $item->emoji,
                'completedAt' => $item->completedAt,
            ];
        }
    }

    public function quizUrl(?int $itemId): string
    {
        if ($itemId === null) {
            return route('daily-mission');
        }

        return route('game-multiple-choice', ['item' => $itemId]);
    }

    public function missionProgressPercent(): int
    {
        if ($this->missionTotal === 0) {
            return 0;
        }

        return (int) round(($this->missionDone / $this->missionTotal) * 100);
    }
};
?>

<main class="device-frame min-h-screen flex flex-col">

  <!-- =============== APPBAR =============== -->
  <header class="appbar safe-top">
    <a href="{{ route('home') }}" class="icon-btn" data-back aria-label="{{ __('onboarding.back') }}"><i class="ph ph-caret-left"></i></a>
    <div class="grow">
      <p class="text-xs text-muted">{{ __('daily-mission.today') }}</p>
      <h1 class="h-display text-lg leading-tight">{{ __('daily-mission.heading') }}</h1>
    </div>
    <button type="button" class="icon-btn" aria-label="{{ __('daily-mission.share') }}"><i class="ph ph-share-fat text-xl"></i></button>
    <button type="button" class="icon-btn" aria-label="{{ __('daily-mission.settings') }}"><i class="ph ph-gear"></i></button>
  </header>

  <!-- =============== MISSION HERO =============== -->
  <section class="px-5">
    <div class="k-card-lg hero-mission">
      <span class="hero-mission-watermark" aria-hidden="true">🎯</span>

      <div class="relative flex items-center gap-2">
        <span class="chip bg-white/20 border-0 text-white">
          <i class="ph-fill ph-sparkle"></i> {{ __('daily-mission.chip') }}
        </span>
        <span class="chip bg-white/20 border-0 text-white ml-auto">
          <span class="live-dot"></span> {{ __('daily-mission.active') }}
        </span>
      </div>

      <p class="h-display text-2xl mt-2 relative">{{ __('daily-mission.mission_explorer') }} 🚀</p>
      <p class="text-sm text-white/90 relative">{{ __('daily-mission.finish_tasks_gift') }}</p>

      <div class="mt-4 flex items-center gap-3 relative">
        <div class="progress on-gradient grow"><span style="width: {{ $this->missionProgressPercent() }}%"></span></div>
        <span class="text-sm font-extrabold">{{ $missionDone }} / {{ $missionTotal }}</span>
      </div>

      <div class="relative mt-4 grid grid-cols-3 gap-2" aria-live="polite">
        <div class="count-pill">
          <span class="count-value">{{ $hoursLeft }}</span>
          <span class="count-label">{{ __('daily-mission.hours') }}</span>
        </div>
        <div class="count-pill">
          <span class="count-value">00</span>
          <span class="count-label">{{ __('daily-mission.minutes') }}</span>
        </div>
        <div class="count-pill">
          <span class="count-value">00</span>
          <span class="count-label">{{ __('daily-mission.seconds') }}</span>
        </div>
      </div>

      <div class="relative mt-4 flex flex-wrap items-center gap-2">
        <span class="chip bg-white/20 border-0 text-white">+120 XP</span>
        <span class="chip bg-white/20 border-0 text-white">🎁 {{ __('daily-mission.gift') }}</span>
        <span class="chip bg-white/20 border-0 text-white">🏅 {{ __('daily-mission.badge') }}</span>
        <span class="chip bg-white/20 border-0 text-white ml-auto">
          <i class="ph-fill ph-users-three"></i> {{ __('daily-mission.playing') }}
        </span>
      </div>
    </div>
  </section>

  <!-- =============== TASK LIST =============== -->
  <section class="px-5 mt-5">
    <p class="section-label">{{ __('daily-mission.your_tasks') }}</p>

    <div class="mt-3 space-y-3">
      @foreach ($items as $item)
        @if ($item['completed'])
          <div class="task done w-full text-left">
            <div class="task-ico"><i class="ph-fill ph-check-circle text-2xl"></i></div>
            <div class="task-body grow">
              <p class="task-title">{{ $item['title'] }} <i class="ph-fill ph-seal-check text-[#29C598]"></i></p>
              <p class="task-sub">{{ __('daily-mission.completed') }}@if ($item['completedAt']) · {{ $item['completedAt'] }}@endif · {{ $item['subject'] }}</p>
            </div>
            <span class="task-xp">{{ __('daily-mission.plus_xp', ['xp' => 40]) }}</span>
          </div>
        @elseif ($item['playable'])
          <a href="{{ $this->quizUrl($item['id']) }}" wire:navigate class="task current">
            <span class="ribbon">{{ __('daily-mission.now') }}</span>
            <div class="task-ico">{{ $item['emoji'] }}</div>
            <div class="task-body grow">
              <p class="task-title">{{ $item['title'] }}</p>
              <p class="task-sub">{{ $item['subject'] }} · {{ __('home.weekdays.'.$item['weekday']) }}</p>
            </div>
            <span class="btn btn-primary h-9 min-h-0 px-4 text-sm shrink-0">{{ __('daily-mission.play') }}</span>
          </a>
        @else
          <div class="task locked w-full text-left">
            <div class="task-ico"><i class="ph-fill ph-lock text-xl"></i></div>
            <div class="task-body grow">
              <p class="task-title">{{ $item['title'] }}</p>
              <p class="task-sub">{{ __('daily-mission.locked_until') }}</p>
            </div>
            <span class="task-xp bg-[color:var(--color-k-border)] text-muted">{{ __('daily-mission.plus_xp', ['xp' => 40]) }}</span>
          </div>
        @endif
      @endforeach
    </div>

    <div class="mt-3">
      <button type="button" class="task locked w-full text-left">
        <div class="task-ico"><i class="ph-fill ph-lock text-xl"></i></div>
        <div class="task-body grow">
          <p class="task-title">{{ __('daily-mission.speed_bonus') }}</p>
          <p class="task-sub">{{ __('daily-mission.unlocks_after') }}</p>
        </div>
        <span class="task-xp bg-[color:var(--color-k-border)] text-muted">{{ __('daily-mission.plus_xp', ['xp' => 20]) }}</span>
      </button>
    </div>
  </section>

  <!-- =============== REWARD SPOTLIGHT =============== -->
  <section class="px-5 mt-5">
    <p class="section-label">{{ __('daily-mission.todays_reward') }}</p>
    <div class="k-card-lg reward-spotlight mt-3">
      <span class="spot-glow" aria-hidden="true"></span>
      <div class="relative flex items-center gap-3">
        <div class="text-5xl">🎁</div>
        <div class="grow">
          <p class="text-xs uppercase font-extrabold tracking-wider">{{ __('daily-mission.surprise_box') }}</p>
          <p class="h-display text-xl leading-tight">{{ __('daily-mission.xp_badge_bonus') }}</p>
          <p class="text-xs opacity-90">{{ __('daily-mission.complete_to_unlock') }}</p>
        </div>
        <span class="chip chip-on-tile">{{ $missionDone }} / {{ $missionTotal }}</span>
      </div>
      <div class="relative mt-3 grid grid-cols-3 gap-2">
        <div class="bg-white/50 rounded-xl p-2 text-center">
          <p class="text-lg">⭐</p>
          <p class="text-[10px] font-extrabold">+120 XP</p>
        </div>
        <div class="bg-white/50 rounded-xl p-2 text-center">
          <p class="text-lg">🏅</p>
          <p class="text-[10px] font-extrabold">{{ __('daily-mission.explorer_badge') }}</p>
        </div>
        <div class="bg-white/50 rounded-xl p-2 text-center">
          <p class="text-lg">🛡️</p>
          <p class="text-[10px] font-extrabold">{{ __('daily-mission.streak_freeze') }}</p>
        </div>
      </div>
    </div>
  </section>

  <!-- =============== BONUS MISSIONS =============== -->
  <section class="px-5 mt-5">
    <div class="section-head">
      <h2 class="h-display text-lg">{{ __('daily-mission.bonus_missions') }}</h2>
      <span class="link cursor-default">{{ __('daily-mission.bonus_xp') }}</span>
    </div>
    <div class="grid grid-cols-2 gap-3">
      <div class="k-card p-4 relative">
        <div class="size-10 rounded-xl tile-coral grid place-items-center text-xl mb-2">📚</div>
        <p class="font-extrabold text-sm">{{ __('daily-mission.read_story') }}</p>
        <p class="text-xs text-muted">{{ __('daily-mission.read_story_meta') }}</p>
        <span class="chip chip-coral mt-2">{{ __('daily-mission.plus_xp', ['xp' => 20]) }}</span>
      </div>
      <div class="k-card p-4 relative">
        <div class="size-10 rounded-xl tile-sky grid place-items-center text-xl mb-2">🎧</div>
        <p class="font-extrabold text-sm">{{ __('daily-mission.listen_learn') }}</p>
        <p class="text-xs text-muted">{{ __('daily-mission.listen_meta') }}</p>
        <span class="chip chip-sky mt-2">{{ __('daily-mission.plus_xp', ['xp' => 15]) }}</span>
      </div>
    </div>
  </section>

  <!-- =============== STREAK KEEPER =============== -->
  <section class="px-5 mt-5">
    <div class="k-card-lg streak-strip block">
      <div class="flex items-center gap-3">
        <div class="text-4xl">🔥</div>
        <div class="grow">
          <p class="text-xs uppercase font-extrabold tracking-wider opacity-90">{{ __('daily-mission.streak_keeper') }}</p>
          <p class="h-display text-xl leading-tight">{{ __('daily-mission.streak_day', ['n' => $streak]) }}</p>
        </div>
        <span class="chip chip-on-tile text-sun-ink">🔥 {{ $streak }}</span>
      </div>
    </div>
  </section>

  <!-- =============== MISSION HISTORY =============== -->
  <section class="px-5 mt-5">
    <div class="section-head">
      <h2 class="h-display text-lg">{{ __('daily-mission.this_week') }}</h2>
      <span class="link cursor-default">{{ $weekCompleted }} / {{ max(1, $weekTotal) }}</span>
    </div>
    <div class="k-card p-4">
      <div class="flex items-center justify-between">
        <div class="grid grid-cols-7 gap-2" aria-label="{{ __('daily-mission.this_week') }}">
          @foreach ($weekDays as $day)
            <span class="history-dot{{ $day['on'] ? ' hit' : '' }}{{ $day['today'] ? ' today' : '' }}">{{ $day['letter'] }}</span>
          @endforeach
        </div>
      </div>
      <div class="mt-3 flex items-center gap-2 text-xs">
        <span class="inline-flex items-center gap-1 text-muted"><span class="size-2 rounded-full bg-[#29C598]"></span> {{ __('daily-mission.hit') }}</span>
        <span class="inline-flex items-center gap-1 text-muted"><span class="size-2 rounded-full bg-[#FF5B73]"></span> {{ __('daily-mission.miss') }}</span>
      </div>
    </div>
  </section>

  <!-- =============== TIP =============== -->
  <section class="px-5 mt-5 mb-5">
    <div class="tip-card rounded-2xl p-4 flex items-start gap-3">
      <div class="mascot shrink-0 size-11 text-xl">🦉</div>
      <div class="grow">
        <p class="font-extrabold text-sm text-ink">{{ __('daily-mission.tip_title') }}</p>
        <p class="text-xs text-muted">{{ __('daily-mission.tip_body') }}</p>
      </div>
      <button type="button" class="chip chip-primary">{{ __('daily-mission.remind_me') }}</button>
    </div>
  </section>

  <livewire:bottom-nav-bar />
</main>
