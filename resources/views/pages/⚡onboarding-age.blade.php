<?php

use App\Enums\AgeGroup;
use App\Repositories\UserRepository;
use App\Services\KidSetupService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Choose Age · Kidzio')] class extends Component
{
    public string $ageGroup = 'kindergarten';

    public function mount(KidSetupService $setup, UserRepository $users): void
    {
        $this->ageGroup = $setup->defaultAgeGroup($users->authenticated())->value;
    }

    public function select(string $ageGroup): void
    {
        $this->ageGroup = $ageGroup;
    }

    public function save(KidSetupService $setup, UserRepository $users): void
    {
        $this->validate([
            'ageGroup' => ['required', Rule::enum(AgeGroup::class)],
        ]);

        $setup->saveAgeGroup($users->authenticated(), AgeGroup::from($this->ageGroup));

        $this->redirectRoute('onboarding-categories', navigate: true);
    }

    public function selected(): AgeGroup
    {
        return AgeGroup::from($this->ageGroup);
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top">

  <!-- =============== APPBAR =============== -->
  <header class="appbar">
    <a href="#" class="icon-btn" data-back aria-label="Back"><i class="ph ph-caret-left"></i></a>
    <div class="grow">
      <div class="progress"><span class="w-25"></span></div>
    </div>
    <span class="text-xs font-extrabold text-muted">1/4</span>
  </header>

  <!-- =============== HEADING =============== -->
  <section class="px-6 pt-4">
    <div class="flex items-center gap-2 mb-3">
      <span class="chip chip-primary">
        <i class="ph-fill ph-sparkle"></i> STEP 1
      </span>
      <span id="pickBadge" class="chip chip-mint ml-auto">
        {{ $this->selected()->label() }} · {{ $this->selected()->range() }}
      </span>
    </div>
    <h1 class="h-display text-2xl leading-tight">How old is your kid?</h1>
    <p class="text-sm mt-1 text-muted">We tailor lesson difficulty, word length and pace to their age.</p>
  </section>

  <!-- =============== AGE GRID =============== -->
  <section class="px-6 mt-5">
    <p class="section-label">Pick an age range</p>
    <div class="mt-3 grid grid-cols-2 gap-3" id="ageGroup">

      <button type="button" class="age-card{{ $ageGroup === 'preschool' ? ' is-selected' : '' }}" wire:click="select('preschool')" data-age data-name="Preschool" data-range="4–5">
        <div class="age-emoji tile-mint">🧸</div>
        <p class="age-range">4–5</p>
        <p class="age-label">Preschool</p>
        <span class="age-check"></span>
      </button>

      <button type="button" class="age-card{{ $ageGroup === 'kindergarten' ? ' is-selected' : '' }}" wire:click="select('kindergarten')" data-age data-name="Kindergarten" data-range="6–7">
        <span class="age-tag">POPULAR</span>
        <div class="age-emoji tile-sun">🦄</div>
        <p class="age-range">6–7</p>
        <p class="age-label">Kindergarten</p>
        <span class="age-check"></span>
      </button>

      <button type="button" class="age-card{{ $ageGroup === 'elementary' ? ' is-selected' : '' }}" wire:click="select('elementary')" data-age data-name="Elementary" data-range="8–9">
        <div class="age-emoji tile-coral">🚀</div>
        <p class="age-range">8–9</p>
        <p class="age-label">Elementary</p>
        <span class="age-check"></span>
      </button>

      <button type="button" class="age-card{{ $ageGroup === 'explorer' ? ' is-selected' : '' }}" wire:click="select('explorer')" data-age data-name="Explorer" data-range="10+">
        <div class="age-emoji tile-violet">🧪</div>
        <p class="age-range">10+</p>
        <p class="age-label">Explorer</p>
        <span class="age-check"></span>
      </button>
    </div>
  </section>

  <!-- =============== AGE PREVIEW =============== -->
  <section class="px-6 mt-5">
    <p class="section-label">At this age, lessons focus on</p>
    <div class="mt-3 grid grid-cols-3 gap-2 text-center">
      <div class="k-card p-3">
        <div class="size-9 rounded-xl tile-sun grid place-items-center text-lg mx-auto">🔤</div>
        <p class="text-[11px] font-extrabold mt-1 text-ink">Letters</p>
        <p class="text-[10px] text-muted">A–Z &amp; sounds</p>
      </div>
      <div class="k-card p-3">
        <div class="size-9 rounded-xl tile-violet grid place-items-center text-lg mx-auto">➗</div>
        <p class="text-[11px] font-extrabold mt-1 text-ink">Counting</p>
        <p class="text-[10px] text-muted">1–20</p>
      </div>
      <div class="k-card p-3">
        <div class="size-9 rounded-xl tile-mint grid place-items-center text-lg mx-auto">🦁</div>
        <p class="text-[11px] font-extrabold mt-1 text-ink">Animals</p>
        <p class="text-[10px] text-muted">Easy facts</p>
      </div>
      <div class="k-card p-3">
        <div class="size-9 rounded-xl tile-coral grid place-items-center text-lg mx-auto">📚</div>
        <p class="text-[11px] font-extrabold mt-1 text-ink">Words</p>
        <p class="text-[10px] text-muted">Sight words</p>
      </div>
      <div class="k-card p-3">
        <div class="size-9 rounded-xl tile-sky grid place-items-center text-lg mx-auto">🎨</div>
        <p class="text-[11px] font-extrabold mt-1 text-ink">Colors</p>
        <p class="text-[10px] text-muted">Recognise</p>
      </div>
      <div class="k-card p-3">
        <div class="size-9 rounded-xl tile-pink grid place-items-center text-lg mx-auto">⚖️</div>
        <p class="text-[11px] font-extrabold mt-1 text-ink">Opposites</p>
        <p class="text-[10px] text-muted">Big/small</p>
      </div>
    </div>
  </section>

  <!-- =============== PARENT TIP =============== -->
  <section class="px-6 mt-5">
    <div class="tip-card rounded-2xl p-3 flex items-start gap-3">
      <div class="mascot shrink-0 size-10 text-base">🦉</div>
      <div class="grow">
        <p class="font-extrabold text-sm text-ink">Safe choice</p>
        <p class="text-[11px] text-muted">Pick the closest range — you can change it later in Settings → Age group.</p>
      </div>
    </div>
  </section>

  <!-- =============== CTA =============== -->
  <div class="mt-auto px-6 pb-8 pt-5 safe-bottom">
    <button type="button" id="continueBtn" class="btn btn-primary w-full" wire:click="save">
      Continue · <span id="continueAge">{{ $this->selected()->label() }} {{ $this->selected()->range() }}</span>
    </button>
    <p class="text-[11px] text-center text-muted mt-2">4 quick steps · takes under a minute</p>
  </div>
</main>
