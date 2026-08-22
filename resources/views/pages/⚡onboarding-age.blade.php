<?php

use App\Enums\SchoolGrade;
use App\Repositories\UserRepository;
use App\Services\KidSetupService;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public int $grade = 1;

    public function title(): string
    {
        return __('onboarding.age.page_title');
    }

    public function rendering(\Illuminate\View\View $view): void
    {
        $view->title($this->title());
    }

    public function mount(KidSetupService $setup, UserRepository $users): void
    {
        $this->grade = $setup->defaultGrade($users->authenticated())->value;
    }

    public function select(int $grade): void
    {
        $this->grade = $grade;
    }

    public function save(KidSetupService $setup, UserRepository $users): void
    {
        $this->validate([
            'grade' => ['required', Rule::enum(SchoolGrade::class)],
        ]);

        $setup->saveGrade($users->authenticated(), SchoolGrade::from($this->grade));

        $this->redirectRoute('onboarding-categories', navigate: true);
    }

    public function selected(): SchoolGrade
    {
        return SchoolGrade::from($this->grade);
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top">

  <!-- =============== APPBAR =============== -->
  <header class="appbar">
    <a href="#" class="icon-btn" data-back aria-label="{{ __('onboarding.back') }}"><i class="ph ph-caret-left"></i></a>
    <div class="grow">
      <div class="progress"><span class="w-25"></span></div>
    </div>
    <span class="text-xs font-extrabold text-muted">{{ __('onboarding.progress', ['current' => 1, 'total' => 4]) }}</span>
  </header>

  <!-- =============== HEADING =============== -->
  <section class="px-6 pt-4">
    <div class="flex items-center gap-2 mb-3">
      <span class="chip chip-primary">
        <i class="ph-fill ph-sparkle"></i> {{ __('onboarding.step', ['n' => 1]) }}
      </span>
      <span id="pickBadge" class="chip chip-mint ml-auto">
        {{ $this->selected()->label() }} · {{ $this->selected()->range() }}
      </span>
    </div>
    <h1 class="h-display text-2xl leading-tight">{{ __('onboarding.age.heading') }}</h1>
    <p class="text-sm mt-1 text-muted">{{ __('onboarding.age.subtitle') }}</p>
  </section>

  <!-- =============== AGE GRID =============== -->
  <section class="px-6 mt-5">
    <p class="section-label">{{ __('onboarding.age.pick_range') }}</p>
    <div class="mt-3 grid grid-cols-2 gap-3" id="ageGroup">

      <button type="button" class="age-card{{ $grade === 1 ? ' is-selected' : '' }}" wire:click="select(1)" data-age data-name="Grade 1" data-range="6–7">
        <span class="age-tag">{{ __('onboarding.age.popular') }}</span>
        <div class="age-emoji tile-sun">1️⃣</div>
        <p class="age-range">{{ __('onboarding.age.grade_ranges.1') }}</p>
        <p class="age-label">{{ __('onboarding.age.grades.1') }}</p>
        <span class="age-check"></span>
      </button>

      <button type="button" class="age-card{{ $grade === 2 ? ' is-selected' : '' }}" wire:click="select(2)" data-age data-name="Grade 2" data-range="7–8">
        <div class="age-emoji tile-mint">2️⃣</div>
        <p class="age-range">{{ __('onboarding.age.grade_ranges.2') }}</p>
        <p class="age-label">{{ __('onboarding.age.grades.2') }}</p>
        <span class="age-check"></span>
      </button>

      <button type="button" class="age-card{{ $grade === 3 ? ' is-selected' : '' }}" wire:click="select(3)" data-age data-name="Grade 3" data-range="8–9">
        <div class="age-emoji tile-coral">3️⃣</div>
        <p class="age-range">{{ __('onboarding.age.grade_ranges.3') }}</p>
        <p class="age-label">{{ __('onboarding.age.grades.3') }}</p>
        <span class="age-check"></span>
      </button>
    </div>
  </section>

  <!-- =============== AGE PREVIEW =============== -->
  <section class="px-6 mt-5">
    <p class="section-label">{{ __('onboarding.age.focus_label') }}</p>
    <div class="mt-3 grid grid-cols-3 gap-2 text-center">
      <div class="k-card p-3">
        <div class="size-9 rounded-xl tile-sun grid place-items-center text-lg mx-auto">🔤</div>
        <p class="text-[11px] font-extrabold mt-1 text-ink">{{ __('onboarding.age.letters') }}</p>
        <p class="text-[10px] text-muted">{{ __('onboarding.age.letters_sub') }}</p>
      </div>
      <div class="k-card p-3">
        <div class="size-9 rounded-xl tile-violet grid place-items-center text-lg mx-auto">➗</div>
        <p class="text-[11px] font-extrabold mt-1 text-ink">{{ __('onboarding.age.counting') }}</p>
        <p class="text-[10px] text-muted">{{ __('onboarding.age.counting_sub') }}</p>
      </div>
      <div class="k-card p-3">
        <div class="size-9 rounded-xl tile-sky grid place-items-center text-lg mx-auto">🏛️</div>
        <p class="text-[11px] font-extrabold mt-1 text-ink">{{ __('onboarding.age.animals') }}</p>
        <p class="text-[10px] text-muted">{{ __('onboarding.age.animals_sub') }}</p>
      </div>
      <div class="k-card p-3">
        <div class="size-9 rounded-xl tile-coral grid place-items-center text-lg mx-auto">📚</div>
        <p class="text-[11px] font-extrabold mt-1 text-ink">{{ __('onboarding.age.words') }}</p>
        <p class="text-[10px] text-muted">{{ __('onboarding.age.words_sub') }}</p>
      </div>
      <div class="k-card p-3">
        <div class="size-9 rounded-xl tile-mint grid place-items-center text-lg mx-auto">🔢</div>
        <p class="text-[11px] font-extrabold mt-1 text-ink">{{ __('onboarding.age.colors') }}</p>
        <p class="text-[10px] text-muted">{{ __('onboarding.age.colors_sub') }}</p>
      </div>
      <div class="k-card p-3">
        <div class="size-9 rounded-xl tile-pink grid place-items-center text-lg mx-auto">🚩</div>
        <p class="text-[11px] font-extrabold mt-1 text-ink">{{ __('onboarding.age.opposites') }}</p>
        <p class="text-[10px] text-muted">{{ __('onboarding.age.opposites_sub') }}</p>
      </div>
    </div>
  </section>

  <!-- =============== PARENT TIP =============== -->
  <section class="px-6 mt-5">
    <div class="tip-card rounded-2xl p-3 flex items-start gap-3">
      <div class="mascot shrink-0 size-10 text-base">🦉</div>
      <div class="grow">
        <p class="font-extrabold text-sm text-ink">{{ __('onboarding.age.tip_title') }}</p>
        <p class="text-[11px] text-muted">{{ __('onboarding.age.tip_body') }}</p>
      </div>
    </div>
  </section>

  <!-- =============== CTA =============== -->
  <div class="mt-auto px-6 pb-8 pt-5 safe-bottom">
    <button type="button" id="continueBtn" class="btn btn-primary w-full" wire:click="save">
      {{ __('onboarding.age.continue', ['label' => $this->selected()->label(), 'range' => $this->selected()->range()]) }}
    </button>
    <p class="text-[11px] text-center text-muted mt-2">{{ __('onboarding.age.footer') }}</p>
  </div>
</main>
