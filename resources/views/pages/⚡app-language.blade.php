<?php

use App\Services\AppLocaleService;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('აპის ენა · Kidzio')] class extends Component
{
    public string $query = '';

    public function select(string $code): void
    {
        if ($code !== 'ka') {
            return;
        }
    }

    /**
     * @return list<array{code: string, native: string, flag: string, keywords: string}>
     */
    public function locales(): array
    {
        $needle = trim(mb_strtolower($this->query));
        $rows = app(AppLocaleService::class)->available();

        if ($needle === '') {
            return $rows;
        }

        return array_values(array_filter(
            $rows,
            fn (array $row): bool => str_contains(mb_strtolower($row['native'].' '.$row['keywords']), $needle),
        ));
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top">

  <header class="appbar">
    <a href="{{ route('settings') }}" class="icon-btn" data-back aria-label="{{ __('language.back') }}"><i class="ph ph-caret-left"></i></a>
    <div class="grow">
      <p class="text-xs text-muted">{{ __('language.eyebrow') }}</p>
      <h1 class="h-display text-lg leading-tight">{{ __('language.heading') }}</h1>
    </div>
    <button type="button" class="icon-btn" data-theme-toggle aria-label="{{ __('language.toggle_theme') }}"><i class="ph ph-moon text-xl"></i></button>
  </header>

  <section class="px-5">
    <div class="k-card-lg hero-friends text-center">
      <div class="relative inline-grid place-items-center">
        <div class="size-24 rounded-3xl bg-white/20 backdrop-blur-sm grid place-items-center text-5xl">🌐</div>
      </div>
      <p class="relative chip bg-white/20 border-0 text-white mt-4">{{ __('language.current') }}</p>
      <p class="relative h-display text-2xl mt-2 leading-tight">🇬🇪 {{ __('language.native') }}</p>
      <p class="relative text-xs text-white/90 mt-1">{{ __('language.covers') }}</p>
    </div>
  </section>

  <section class="px-5 mt-4">
    <div class="input-wrap">
      <i class="ph ph-magnifying-glass i-left"></i>
      <input id="langSearch" class="input has-left" placeholder="{{ __('language.search_placeholder') }}" aria-label="{{ __('language.search_aria') }}" autocomplete="off" wire:model.live.debounce.200ms="query"/>
    </div>
  </section>

  @if ($this->locales() === [])
  <section class="px-5 mt-6">
    <div class="k-card text-center p-6">
      <div class="size-16 mx-auto rounded-2xl tile-sky grid place-items-center text-3xl">🔍</div>
      <p class="h-display text-lg mt-3 text-ink">{{ __('language.empty_title') }}</p>
      <p class="text-xs text-muted mt-1">{{ __('language.empty_body') }}</p>
    </div>
  </section>
  @endif

  @if ($this->locales() !== [])
  <section class="px-5 mt-4">
    <p class="section-label">{{ __('language.ready') }} · {{ count($this->locales()) }}</p>
    <div class="mt-3 space-y-2">
      @foreach ($this->locales() as $locale)
      <button type="button" class="setting-row w-full text-left" wire:click="select('{{ $locale['code'] }}')">
        <div class="setting-ico tile-sun text-2xl">{{ $locale['flag'] }}</div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ $locale['native'] }}</p>
          <p class="text-[11px] text-muted">{{ __('language.covers') }}</p>
        </div>
        <span class="chip chip-mint">{{ __('language.active') }}</span>
      </button>
      @endforeach
    </div>
  </section>
  @endif

  <section class="px-5 mt-5 mb-10">
    <div class="tip-card rounded-2xl p-4 flex items-start gap-3">
      <div class="mascot shrink-0 size-11 text-xl">🦉</div>
      <div class="grow">
        <p class="font-extrabold text-sm text-ink">{{ __('language.note_title') }}</p>
        <p class="text-xs text-muted">{{ __('language.note_body') }}</p>
      </div>
    </div>
  </section>
</main>
