<?php

use App\Repositories\UserRepository;
use App\Services\KidSetupService;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Categories · Kidzio')] class extends Component
{
    /** @var list<string> */
    public array $selected = [];

    public int $age = 6;

    public function mount(KidSetupService $setup, UserRepository $users): void
    {
        $user = $users->authenticated();
        $this->selected = $setup->selectedSubjects($user);
        $this->age = $user->age ?? 6;
    }

    public function toggle(string $topic): void
    {
        if (in_array($topic, $this->selected, true)) {
            $this->selected = array_values(array_filter(
                $this->selected,
                fn (string $item): bool => $item !== $topic,
            ));

            return;
        }

        $this->selected[] = $topic;
    }

    public function isPicked(string $topic): bool
    {
        return in_array($topic, $this->selected, true);
    }

    public function save(KidSetupService $setup, UserRepository $users): void
    {
        $setup->saveSubjects($users->authenticated(), $this->selected);

        $this->redirectRoute('onboarding-goals', navigate: true);
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top">

  <!-- =============== APPBAR =============== -->
  <header class="appbar">
    <a href="{{ route('onboarding-age') }}" class="icon-btn" data-back aria-label="Back"><i class="ph ph-caret-left"></i></a>
    <div class="grow">
      <div class="progress"><span class="w-50"></span></div>
    </div>
    <span class="text-xs font-extrabold text-muted">2/4</span>
  </header>

  <!-- =============== HEADING =============== -->
  <section class="px-6 pt-4">
    <div class="flex items-center gap-2 mb-3">
      <span class="chip chip-primary">
        <i class="ph-fill ph-sparkle"></i> STEP 2
      </span>
      <span id="pickCount" class="pick-count{{ count($selected) >= 3 ? ' ok' : '' }}">{{ count($selected) }} / 3{{ count($selected) >= 3 ? ' · ready!' : ' selected' }}</span>
    </div>
    <h1 class="h-display text-2xl leading-tight">Pick favourite topics</h1>
    <p class="text-sm mt-1 text-muted">Choose at least <span class="font-extrabold text-ink">3</span>. You can always change later in Settings.</p>
  </section>

  <!-- =============== SECTION LABEL =============== -->
  <section class="px-6 mt-5">
    <p class="section-label">Popular with {{ $age }}-year-olds</p>
  </section>

  <!-- =============== CATEGORY GRID =============== -->
  <section class="px-6 mt-3 grid grid-cols-2 gap-3">
    <button type="button" class="pick-card{{ $this->isPicked('alphabet') ? ' is-selected' : '' }}" wire:click="toggle('alphabet')" data-pick>
      <span class="pc-popular">HOT</span>
      <span class="pc-emoji tile-sun">🔤</span>
      <span class="pc-body">
        <span class="pc-name">Alphabet</span>
        <span class="pc-sub">A–Z &amp; phonics</span>
      </span>
      <span class="pc-check"></span>
    </button>
    <button type="button" class="pick-card{{ $this->isPicked('math') ? ' is-selected' : '' }}" wire:click="toggle('math')" data-pick>
      <span class="pc-popular">HOT</span>
      <span class="pc-emoji tile-violet">➗</span>
      <span class="pc-body">
        <span class="pc-name">Math</span>
        <span class="pc-sub">Numbers &amp; shapes</span>
      </span>
      <span class="pc-check"></span>
    </button>
    <button type="button" class="pick-card{{ $this->isPicked('animals') ? ' is-selected' : '' }}" wire:click="toggle('animals')" data-pick>
      <span class="pc-emoji tile-mint">🦁</span>
      <span class="pc-body">
        <span class="pc-name">Animals</span>
        <span class="pc-sub">Wildlife &amp; habitats</span>
      </span>
      <span class="pc-check"></span>
    </button>
    <button type="button" class="pick-card{{ $this->isPicked('words') ? ' is-selected' : '' }}" wire:click="toggle('words')" data-pick>
      <span class="pc-emoji tile-coral">📚</span>
      <span class="pc-body">
        <span class="pc-name">Words</span>
        <span class="pc-sub">Read &amp; spell</span>
      </span>
      <span class="pc-check"></span>
    </button>
  </section>

  <!-- =============== MORE SECTION =============== -->
  <section class="px-6 mt-5">
    <p class="section-label">More topics</p>
  </section>

  <section class="px-6 mt-3 grid grid-cols-2 gap-3">
    <button type="button" class="pick-card{{ $this->isPicked('world') ? ' is-selected' : '' }}" wire:click="toggle('world')" data-pick>
      <span class="pc-emoji tile-sky">🌍</span>
      <span class="pc-body">
        <span class="pc-name">World</span>
        <span class="pc-sub">Geography &amp; culture</span>
      </span>
      <span class="pc-check"></span>
    </button>
    <button type="button" class="pick-card{{ $this->isPicked('colors') ? ' is-selected' : '' }}" wire:click="toggle('colors')" data-pick>
      <span class="pc-emoji tile-pink">🎨</span>
      <span class="pc-body">
        <span class="pc-name">Colors</span>
        <span class="pc-sub">Recognise colours</span>
      </span>
      <span class="pc-check"></span>
    </button>
    <button type="button" class="pick-card{{ $this->isPicked('logic') ? ' is-selected' : '' }}" wire:click="toggle('logic')" data-pick>
      <span class="pc-emoji tile-violet">🧩</span>
      <span class="pc-body">
        <span class="pc-name">Logic</span>
        <span class="pc-sub">Puzzles &amp; patterns</span>
      </span>
      <span class="pc-check"></span>
    </button>
    <button type="button" class="pick-card{{ $this->isPicked('music') ? ' is-selected' : '' }}" wire:click="toggle('music')" data-pick>
      <span class="pc-emoji tile-sun">🎵</span>
      <span class="pc-body">
        <span class="pc-name">Music</span>
        <span class="pc-sub">Rhythms &amp; sounds</span>
      </span>
      <span class="pc-check"></span>
    </button>
    <button type="button" class="pick-card{{ $this->isPicked('opposites') ? ' is-selected' : '' }}" wire:click="toggle('opposites')" data-pick>
      <span class="pc-emoji tile-mint">⚖️</span>
      <span class="pc-body">
        <span class="pc-name">Opposites</span>
        <span class="pc-sub">Big/small, hot/cold</span>
      </span>
      <span class="pc-check"></span>
    </button>
    <button type="button" class="pick-card{{ $this->isPicked('science') ? ' is-selected' : '' }}" wire:click="toggle('science')" data-pick>
      <span class="pc-emoji tile-coral">🧪</span>
      <span class="pc-body">
        <span class="pc-name">Science</span>
        <span class="pc-sub">Planets &amp; plants</span>
      </span>
      <span class="pc-check"></span>
    </button>
  </section>

  <!-- =============== TIP =============== -->
  <section class="px-6 mt-5 mb-2">
    <div class="tip-card rounded-2xl p-3 flex items-start gap-3">
      <div class="mascot shrink-0 size-9 text-base">🦉</div>
      <div class="grow">
        <p class="text-[11px] text-muted leading-snug">We'll build a personalised plan around these. Your kid will get daily recommendations from their chosen topics.</p>
      </div>
    </div>
  </section>

  <!-- =============== CTA =============== -->
  <div class="mt-auto px-6 pb-8 pt-4 safe-bottom">
    <button type="button" id="continueBtn" class="btn btn-primary w-full" wire:click="save">
      Continue · <span id="countBtn">{{ count($selected) }}</span> selected
    </button>
    <p class="text-[11px] text-center text-muted mt-2">You can skip this step</p>
  </div>
</main>
