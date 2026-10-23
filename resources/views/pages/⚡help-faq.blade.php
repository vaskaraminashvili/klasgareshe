<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('დახმარება · Kidzio')] class extends Component
{
    public string $query = '';

    /**
     * @return list<array{id: string, cat: string, emoji: string, tile: string, q: string, a: list<string>}>
     */
    public function items(): array
    {
        $all = __('help.items');

        if (! is_array($all)) {
            return [];
        }

        $needle = trim(mb_strtolower($this->query));
        $rows = [];

        foreach ($all as $item) {
            if (! is_array($item)) {
                continue;
            }

            $answers = is_array($item['a'] ?? null) ? $item['a'] : [];
            $haystack = mb_strtolower((string) ($item['q'] ?? '').' '.implode(' ', $answers));

            if ($needle !== '' && ! str_contains($haystack, $needle)) {
                continue;
            }

            $rows[] = [
                'id' => (string) ($item['id'] ?? ''),
                'cat' => (string) ($item['cat'] ?? ''),
                'emoji' => (string) ($item['emoji'] ?? '🦉'),
                'tile' => (string) ($item['tile'] ?? 'tile-violet'),
                'q' => (string) ($item['q'] ?? ''),
                'a' => array_values(array_map(strval(...), $answers)),
            ];
        }

        return $rows;
    }

    public function countFor(string $cat): int
    {
        return count(array_filter($this->items(), fn (array $item): bool => $item['cat'] === $cat));
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top">

  <header class="appbar">
    <a href="{{ route('settings') }}" class="icon-btn" data-back aria-label="{{ __('help.back') }}"><i class="ph ph-caret-left"></i></a>
    <div class="grow">
      <p class="text-xs text-muted">{{ __('help.eyebrow') }}</p>
      <h1 class="h-display text-lg leading-tight">{{ __('help.heading') }}</h1>
    </div>
    <button type="button" class="icon-btn" data-theme-toggle aria-label="{{ __('help.toggle_theme') }}"><i class="ph ph-moon text-xl"></i></button>
  </header>

  <section class="px-5">
    <div class="k-card-lg hero-knowledge">
      <div class="relative flex items-center gap-3">
        <div class="text-5xl">🦉</div>
        <div class="grow">
          <span class="chip bg-white/20 border-0 text-white">
            <i class="ph-fill ph-lifebuoy"></i> {{ __('help.chip') }}
          </span>
          <p class="h-display text-xl mt-1 leading-tight">{{ __('help.hero_title') }}</p>
          <p class="text-xs text-white/90">{{ __('help.hero_body') }}</p>
        </div>
      </div>
    </div>
  </section>

  <section class="px-5 mt-4">
    <div class="input-wrap">
      <i class="ph ph-magnifying-glass i-left"></i>
      <input class="input has-left" placeholder="{{ __('help.search_placeholder') }}" aria-label="{{ __('help.search_aria') }}" wire:model.live.debounce.200ms="query"/>
    </div>
  </section>

  <section class="px-5 mt-4">
    <p class="section-label">{{ __('help.topics') }}</p>
    <div class="mt-3 grid grid-cols-2 gap-3">
      <a href="#password" class="k-card p-3 flex items-center gap-3">
        <div class="size-10 rounded-2xl tile-violet grid place-items-center">👤</div>
        <div class="grow"><p class="font-extrabold text-sm">{{ __('help.cats.account') }}</p><p class="text-[11px] text-muted">{{ __('help.articles', ['count' => $this->countFor('account')]) }}</p></div>
      </a>
      <a href="#locked" class="k-card p-3 flex items-center gap-3">
        <div class="size-10 rounded-2xl tile-mint grid place-items-center">📚</div>
        <div class="grow"><p class="font-extrabold text-sm">{{ __('help.cats.learn') }}</p><p class="text-[11px] text-muted">{{ __('help.articles', ['count' => $this->countFor('learn')]) }}</p></div>
      </a>
      <a href="#badges" class="k-card p-3 flex items-center gap-3">
        <div class="size-10 rounded-2xl tile-sun grid place-items-center">🎁</div>
        <div class="grow"><p class="font-extrabold text-sm">{{ __('help.cats.rewards') }}</p><p class="text-[11px] text-muted">{{ __('help.articles', ['count' => $this->countFor('rewards')]) }}</p></div>
      </a>
      <a href="#pin" class="k-card p-3 flex items-center gap-3">
        <div class="size-10 rounded-2xl tile-sky grid place-items-center">👨‍👩‍👧</div>
        <div class="grow"><p class="font-extrabold text-sm">{{ __('help.cats.parents') }}</p><p class="text-[11px] text-muted">{{ __('help.articles', ['count' => $this->countFor('parents')]) }}</p></div>
      </a>
    </div>
  </section>

  <section class="px-5 mt-5">
    <p class="section-label">{{ __('help.popular') }}</p>
    @if ($this->items() === [])
      <p class="text-sm text-center text-muted mt-6">{{ __('help.empty') }}</p>
    @endif
    <div class="mt-3 space-y-2">
      @foreach ($this->items() as $item)
      <details class="k-card p-0 overflow-hidden group" id="{{ $item['id'] }}">
        <summary class="flex items-center gap-3 p-4 cursor-pointer list-none">
          <div class="size-10 rounded-xl {{ $item['tile'] }} grid place-items-center text-lg">{{ $item['emoji'] }}</div>
          <p class="font-extrabold text-sm grow text-ink">{{ $item['q'] }}</p>
          <i class="ph ph-caret-down text-muted group-open:rotate-180 transition-transform"></i>
        </summary>
        <div class="px-4 pb-4 text-[12px] text-muted leading-relaxed space-y-2 border-t border-token pt-3">
          @foreach ($item['a'] as $paragraph)
            <p>{{ $paragraph }}</p>
          @endforeach
        </div>
      </details>
      @endforeach
    </div>
  </section>

  <section class="px-5 mt-5 mb-10">
    <p class="section-label">{{ __('help.stuck') }}</p>
    <a href="{{ route('contact-us') }}" wire:navigate class="k-card p-4 text-center mt-3 block">
      <div class="size-10 rounded-2xl tile-violet grid place-items-center text-xl mx-auto">✉️</div>
      <p class="font-extrabold text-sm mt-2">{{ __('help.contact') }}</p>
      <p class="text-[11px] text-muted">{{ __('help.contact_hint') }}</p>
    </a>
    <a href="{{ route('contact-us') }}" wire:navigate class="btn btn-primary w-full mt-3">
      <i class="ph-fill ph-envelope-simple"></i> {{ __('help.email_support') }}
    </a>
  </section>
</main>
