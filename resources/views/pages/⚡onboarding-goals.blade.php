<?php

use App\Enums\DailyGoal;
use App\Repositories\UserRepository;
use App\Services\KidSetupService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Goals · Kidzio')] class extends Component
{
    public string $dailyGoal = 'regular';

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
    <a href="{{ route('onboarding-categories') }}" class="icon-btn" data-back aria-label="Back"><i class="ph ph-caret-left"></i></a>
    <div class="grow">
      <div class="progress"><span class="w-75"></span></div>
    </div>
    <span class="text-xs font-extrabold text-muted">3/4</span>
  </header>

  <!-- =============== HEADING =============== -->
  <section class="px-6 pt-4">
    <div class="flex items-center gap-2 mb-3">
      <span class="chip chip-primary">
        <i class="ph-fill ph-target"></i> STEP 3
      </span>
      <span id="goalBadge" class="chip chip-mint ml-auto">
        {{ $this->selected()->label() }} · {{ $this->selected()->timeLabel() }}
      </span>
    </div>
    <h1 class="h-display text-2xl leading-tight">Daily learning goal</h1>
    <p class="text-sm mt-1 text-muted">Pick how much time your kid plays each day. Consistency matters more than length.</p>
  </section>

  <!-- =============== GOAL CARDS =============== -->
  <section class="px-6 mt-5">
    <p class="section-label">Choose a pace</p>
    <div class="mt-3 space-y-2.5" id="goalGroup">

      <button type="button" class="goal-card{{ $dailyGoal === 'casual' ? ' is-selected' : '' }}" wire:click="select('casual')" data-goal data-name="Casual" data-time="5 min">
        <div class="goal-ico tile-mint">🌱</div>
        <div class="goal-body">
          <p class="goal-name">Casual <span class="pill-easy rounded-full px-2 py-0.5 text-[10px] font-extrabold">Easy</span></p>
          <p class="goal-sub">5 min / day · 10 XP · great first step</p>
          <div class="goal-stats">
            <span>📆 ~2.5 hrs/month</span>
            <span>🎯 +2 lessons</span>
          </div>
        </div>
        <span class="goal-radio"></span>
      </button>

      <button type="button" class="goal-card{{ $dailyGoal === 'regular' ? ' is-selected' : '' }}" wire:click="select('regular')" data-goal data-name="Regular" data-time="10 min">
        <span class="goal-popular">MOST PICKED</span>
        <div class="goal-ico tile-sun">⚡</div>
        <div class="goal-body">
          <p class="goal-name">Regular</p>
          <p class="goal-sub">10 min / day · 25 XP · balanced &amp; sustainable</p>
          <div class="goal-stats">
            <span>📆 ~5 hrs/month</span>
            <span>🎯 +5 lessons</span>
          </div>
        </div>
        <span class="goal-radio"></span>
      </button>

      <button type="button" class="goal-card{{ $dailyGoal === 'serious' ? ' is-selected' : '' }}" wire:click="select('serious')" data-goal data-name="Serious" data-time="15 min">
        <div class="goal-ico tile-coral">🔥</div>
        <div class="goal-body">
          <p class="goal-name">Serious <span class="pill-medium rounded-full px-2 py-0.5 text-[10px] font-extrabold">Medium</span></p>
          <p class="goal-sub">15 min / day · 40 XP · fast progress</p>
          <div class="goal-stats">
            <span>📆 ~7.5 hrs/month</span>
            <span>🎯 +8 lessons</span>
          </div>
        </div>
        <span class="goal-radio"></span>
      </button>

      <button type="button" class="goal-card{{ $dailyGoal === 'intense' ? ' is-selected' : '' }}" wire:click="select('intense')" data-goal data-name="Intense" data-time="20 min">
        <div class="goal-ico tile-violet">🚀</div>
        <div class="goal-body">
          <p class="goal-name">Intense <span class="pill-hard rounded-full px-2 py-0.5 text-[10px] font-extrabold">Hard</span></p>
          <p class="goal-sub">20 min / day · 60 XP · for super learners</p>
          <div class="goal-stats">
            <span>📆 ~10 hrs/month</span>
            <span>🎯 +12 lessons</span>
          </div>
        </div>
        <span class="goal-radio"></span>
      </button>

    </div>
  </section>

  <!-- =============== MOTIVATION STRIP =============== -->
  <section class="px-6 mt-5">
    <p class="section-label">What you'll unlock</p>
    <div class="mt-3 grid grid-cols-3 gap-2 text-center">
      <div class="k-card p-3">
        <div class="size-9 rounded-xl tile-sun grid place-items-center text-lg mx-auto">🔥</div>
        <p class="h-display text-base mt-1">7-day</p>
        <p class="text-[10px] text-muted font-extrabold">Streak badge</p>
      </div>
      <div class="k-card p-3">
        <div class="size-9 rounded-xl tile-violet grid place-items-center text-lg mx-auto">⭐</div>
        <p class="h-display text-base mt-1">Lv 5</p>
        <p class="text-[10px] text-muted font-extrabold">In 14 days</p>
      </div>
      <div class="k-card p-3">
        <div class="size-9 rounded-xl tile-mint grid place-items-center text-lg mx-auto">🏅</div>
        <p class="h-display text-base mt-1">3 badges</p>
        <p class="text-[10px] text-muted font-extrabold">In week 1</p>
      </div>
    </div>
  </section>

  <!-- =============== PARENT TIP =============== -->
  <section class="px-6 mt-5">
    <div class="tip-card rounded-2xl p-3 flex items-start gap-3">
      <div class="mascot shrink-0 size-10 text-base">🦉</div>
      <div class="grow">
        <p class="font-extrabold text-sm text-ink">Start small</p>
        <p class="text-[11px] text-muted">Kids build habits best with short, playful sessions. You can upgrade the goal later.</p>
      </div>
    </div>
  </section>

  <!-- =============== CTA =============== -->
  <div class="mt-auto px-6 pb-8 pt-5 safe-bottom">
    <button type="button" id="continueBtn" class="btn btn-primary w-full" wire:click="save">
      Set goal · <span id="continueGoal">{{ $this->selected()->label() }} {{ $this->selected()->timeLabel() }}</span>
    </button>
    <p class="text-[11px] text-center text-muted mt-2">You can change this anytime in Settings</p>
  </div>
</main>
