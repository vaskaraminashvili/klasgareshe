<?php

use App\Enums\DailyGoal;
use App\Repositories\UserRepository;
use App\Services\KidSetupService;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public string $dailyGoal = 'regular';

    public function title(): string
    {
        return __('onboarding.goals.page_title');
    }

    public function rendering(\Illuminate\View\View $view): void
    {
        $view->title($this->title());
    }

    public function mount(KidSetupService $setup, UserRepository $users): void
    {
        $this->dailyGoal = $setup->defaultDailyGoal($users->authenticated())->value;
    }

    public function select(string $dailyGoal): void
    {
        $this->dailyGoal = $dailyGoal;
    }

    public function save(KidSetupService $setup, UserRepository $users): void
    {
        $this->validate([
            'dailyGoal' => ['required', Rule::enum(DailyGoal::class)],
        ]);

        $setup->saveDailyGoal($users->authenticated(), DailyGoal::from($this->dailyGoal));

        $this->redirectRoute('onboarding-notifications', navigate: true);
    }

    public function selected(): DailyGoal
    {
        return DailyGoal::from($this->dailyGoal);
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top">

  <!-- =============== APPBAR =============== -->
  <header class="appbar">
    <a href="{{ route('onboarding-categories') }}" class="icon-btn" data-back aria-label="{{ __('onboarding.back') }}"><i class="ph ph-caret-left"></i></a>
    <div class="grow">
      <div class="progress"><span class="w-75"></span></div>
    </div>
    <span class="text-xs font-extrabold text-muted">{{ __('onboarding.progress', ['current' => 3, 'total' => 4]) }}</span>
  </header>

  <!-- =============== HEADING =============== -->
  <section class="px-6 pt-4">
    <div class="flex items-center gap-2 mb-3">
      <span class="chip chip-primary">
        <i class="ph-fill ph-target"></i> {{ __('onboarding.step', ['n' => 3]) }}
      </span>
      <span id="goalBadge" class="chip chip-mint ml-auto">
        {{ $this->selected()->label() }} · {{ $this->selected()->timeLabel() }}
      </span>
    </div>
    <h1 class="h-display text-2xl leading-tight">{{ __('onboarding.goals.heading') }}</h1>
    <p class="text-sm mt-1 text-muted">{{ __('onboarding.goals.subtitle') }}</p>
  </section>

  <!-- =============== GOAL CARDS =============== -->
  <section class="px-6 mt-5">
    <p class="section-label">{{ __('onboarding.goals.choose_pace') }}</p>
    <div class="mt-3 space-y-2.5" id="goalGroup">

      <button type="button" class="goal-card{{ $dailyGoal === 'casual' ? ' is-selected' : '' }}" wire:click="select('casual')" data-goal data-name="Casual" data-time="5 min">
        <div class="goal-ico tile-mint">🌱</div>
        <div class="goal-body">
          <p class="goal-name">{{ __('onboarding.goals.casual') }} <span class="pill-easy rounded-full px-2 py-0.5 text-[10px] font-extrabold">{{ __('onboarding.goals.easy') }}</span></p>
          <p class="goal-sub">{{ __('onboarding.goals.casual_sub') }}</p>
          <div class="goal-stats">
            <span>{{ __('onboarding.goals.casual_month') }}</span>
            <span>{{ __('onboarding.goals.casual_lessons') }}</span>
          </div>
        </div>
        <span class="goal-radio"></span>
      </button>

      <button type="button" class="goal-card{{ $dailyGoal === 'regular' ? ' is-selected' : '' }}" wire:click="select('regular')" data-goal data-name="Regular" data-time="10 min">
        <span class="goal-popular">{{ __('onboarding.goals.most_picked') }}</span>
        <div class="goal-ico tile-sun">⚡</div>
        <div class="goal-body">
          <p class="goal-name">{{ __('onboarding.goals.regular') }}</p>
          <p class="goal-sub">{{ __('onboarding.goals.regular_sub') }}</p>
          <div class="goal-stats">
            <span>{{ __('onboarding.goals.regular_month') }}</span>
            <span>{{ __('onboarding.goals.regular_lessons') }}</span>
          </div>
        </div>
        <span class="goal-radio"></span>
      </button>

      <button type="button" class="goal-card{{ $dailyGoal === 'serious' ? ' is-selected' : '' }}" wire:click="select('serious')" data-goal data-name="Serious" data-time="15 min">
        <div class="goal-ico tile-coral">🔥</div>
        <div class="goal-body">
          <p class="goal-name">{{ __('onboarding.goals.serious') }} <span class="pill-medium rounded-full px-2 py-0.5 text-[10px] font-extrabold">{{ __('onboarding.goals.medium') }}</span></p>
          <p class="goal-sub">{{ __('onboarding.goals.serious_sub') }}</p>
          <div class="goal-stats">
            <span>{{ __('onboarding.goals.serious_month') }}</span>
            <span>{{ __('onboarding.goals.serious_lessons') }}</span>
          </div>
        </div>
        <span class="goal-radio"></span>
      </button>

      <button type="button" class="goal-card{{ $dailyGoal === 'intense' ? ' is-selected' : '' }}" wire:click="select('intense')" data-goal data-name="Intense" data-time="20 min">
        <div class="goal-ico tile-violet">🚀</div>
        <div class="goal-body">
          <p class="goal-name">{{ __('onboarding.goals.intense') }} <span class="pill-hard rounded-full px-2 py-0.5 text-[10px] font-extrabold">{{ __('onboarding.goals.hard') }}</span></p>
          <p class="goal-sub">{{ __('onboarding.goals.intense_sub') }}</p>
          <div class="goal-stats">
            <span>{{ __('onboarding.goals.intense_month') }}</span>
            <span>{{ __('onboarding.goals.intense_lessons') }}</span>
          </div>
        </div>
        <span class="goal-radio"></span>
      </button>

    </div>
  </section>

  <!-- =============== MOTIVATION STRIP =============== -->
  <section class="px-6 mt-5">
    <p class="section-label">{{ __('onboarding.goals.unlock_label') }}</p>
    <div class="mt-3 grid grid-cols-3 gap-2 text-center">
      <div class="k-card p-3">
        <div class="size-9 rounded-xl tile-sun grid place-items-center text-lg mx-auto">🔥</div>
        <p class="h-display text-base mt-1">{{ __('onboarding.goals.seven_day') }}</p>
        <p class="text-[10px] text-muted font-extrabold">{{ __('onboarding.goals.streak_badge') }}</p>
      </div>
      <div class="k-card p-3">
        <div class="size-9 rounded-xl tile-violet grid place-items-center text-lg mx-auto">⭐</div>
        <p class="h-display text-base mt-1">{{ __('onboarding.goals.level_5') }}</p>
        <p class="text-[10px] text-muted font-extrabold">{{ __('onboarding.goals.in_14_days') }}</p>
      </div>
      <div class="k-card p-3">
        <div class="size-9 rounded-xl tile-mint grid place-items-center text-lg mx-auto">🏅</div>
        <p class="h-display text-base mt-1">{{ __('onboarding.goals.three_badges') }}</p>
        <p class="text-[10px] text-muted font-extrabold">{{ __('onboarding.goals.in_week_1') }}</p>
      </div>
    </div>
  </section>

  <!-- =============== PARENT TIP =============== -->
  <section class="px-6 mt-5">
    <div class="tip-card rounded-2xl p-3 flex items-start gap-3">
      <div class="mascot shrink-0 size-10 text-base">🦉</div>
      <div class="grow">
        <p class="font-extrabold text-sm text-ink">{{ __('onboarding.goals.tip_title') }}</p>
        <p class="text-[11px] text-muted">{{ __('onboarding.goals.tip_body') }}</p>
      </div>
    </div>
  </section>

  <!-- =============== CTA =============== -->
  <div class="mt-auto px-6 pb-8 pt-5 safe-bottom">
    <button type="button" id="continueBtn" class="btn btn-primary w-full" wire:click="save">
      {{ __('onboarding.goals.set_goal', ['label' => $this->selected()->label(), 'time' => $this->selected()->timeLabel()]) }}
    </button>
    <p class="text-[11px] text-center text-muted mt-2">{{ __('onboarding.goals.change_later') }}</p>
  </div>
</main>
