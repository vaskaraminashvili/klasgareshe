<?php

use App\Repositories\UserRepository;
use App\Services\KidSetupService;
use Livewire\Component;

new class extends Component
{
    /** @var list<string> */
    public array $selected = [];

    public int $age = 6;

    public function title(): string
    {
        return __('onboarding.categories.page_title');
    }

    public function rendering(\Illuminate\View\View $view): void
    {
        $view->title($this->title());
    }

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
    <a href="{{ route('onboarding-age') }}" class="icon-btn" data-back aria-label="{{ __('onboarding.back') }}"><i class="ph ph-caret-left"></i></a>
    <div class="grow">
      <div class="progress"><span class="w-50"></span></div>
    </div>
    <span class="text-xs font-extrabold text-muted">{{ __('onboarding.progress', ['current' => 2, 'total' => 4]) }}</span>
  </header>

  <!-- =============== HEADING =============== -->
  <section class="px-6 pt-4">
    <div class="flex items-center gap-2 mb-3">
      <span class="chip chip-primary">
        <i class="ph-fill ph-sparkle"></i> {{ __('onboarding.step', ['n' => 2]) }}
      </span>
      <span id="pickCount" class="pick-count{{ count($selected) >= 3 ? ' ok' : '' }}">{{ count($selected) >= 3 ? __('onboarding.categories.pick_ready', ['count' => count($selected)]) : __('onboarding.categories.pick_count', ['count' => count($selected)]) }}</span>
    </div>
    <h1 class="h-display text-2xl leading-tight">{{ __('onboarding.categories.heading') }}</h1>
    <p class="text-sm mt-1 text-muted">{{ __('onboarding.categories.subtitle_before') }} <span class="font-extrabold text-ink">3</span>{{ __('onboarding.categories.subtitle_after') }}</p>
  </section>

  <!-- =============== SECTION LABEL =============== -->
  <section class="px-6 mt-5">
    <p class="section-label">{{ __('onboarding.categories.popular_with_age', ['age' => $age]) }}</p>
  </section>

  <!-- =============== CATEGORY GRID =============== -->
  <section class="px-6 mt-3 grid grid-cols-2 gap-3">
    <button type="button" class="pick-card{{ $this->isPicked('georgian') ? ' is-selected' : '' }}" wire:click="toggle('georgian')" data-pick>
      <span class="pc-popular">{{ __('onboarding.categories.hot') }}</span>
      <span class="pc-emoji tile-sun">🔤</span>
      <span class="pc-body">
        <span class="pc-name">{{ __('onboarding.categories.topics.georgian') }}</span>
        <span class="pc-sub">{{ __('onboarding.categories.topics.georgian_sub') }}</span>
      </span>
      <span class="pc-check"></span>
    </button>
    <button type="button" class="pick-card{{ $this->isPicked('math') ? ' is-selected' : '' }}" wire:click="toggle('math')" data-pick>
      <span class="pc-popular">{{ __('onboarding.categories.hot') }}</span>
      <span class="pc-emoji tile-violet">➗</span>
      <span class="pc-body">
        <span class="pc-name">{{ __('onboarding.categories.topics.math') }}</span>
        <span class="pc-sub">{{ __('onboarding.categories.topics.math_sub') }}</span>
      </span>
      <span class="pc-check"></span>
    </button>
    <button type="button" class="pick-card{{ $this->isPicked('history') ? ' is-selected' : '' }}" wire:click="toggle('history')" data-pick>
      <span class="pc-emoji tile-sky">🏛️</span>
      <span class="pc-body">
        <span class="pc-name">{{ __('onboarding.categories.topics.history') }}</span>
        <span class="pc-sub">{{ __('onboarding.categories.topics.history_sub') }}</span>
      </span>
      <span class="pc-check"></span>
    </button>
  </section>

  <!-- =============== TIP =============== -->
  <section class="px-6 mt-5 mb-2">
    <div class="tip-card rounded-2xl p-3 flex items-start gap-3">
      <div class="mascot shrink-0 size-9 text-base">🦉</div>
      <div class="grow">
        <p class="text-[11px] text-muted leading-snug">{{ __('onboarding.categories.tip') }}</p>
      </div>
    </div>
  </section>

  <!-- =============== CTA =============== -->
  <div class="mt-auto px-6 pb-8 pt-4 safe-bottom">
    <button type="button" id="continueBtn" class="btn btn-primary w-full" wire:click="save">
      {{ __('onboarding.categories.continue', ['count' => count($selected)]) }}
    </button>
    <p class="text-[11px] text-center text-muted mt-2">{{ __('onboarding.categories.skip_hint') }}</p>
  </div>
</main>
