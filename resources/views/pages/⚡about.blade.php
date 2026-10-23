<?php

use App\Services\AboutService;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('შესახებ · Kidzio')] class extends Component
{
    public string $version = '';

    public int $badges = 0;

    public int $subjects = 0;

    public int $games = 0;

    public int $locales = 0;

    public function mount(AboutService $about): void
    {
        $this->version = $about->version();
        $this->badges = $about->badgeCount();
        $this->subjects = $about->subjectCount();
        $this->games = $about->liveGameCount();
        $this->locales = $about->localeCount();
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top">

  <header class="appbar">
    <a href="{{ route('settings') }}" class="icon-btn" data-back aria-label="{{ __('about.back') }}"><i class="ph ph-caret-left"></i></a>
    <div class="grow">
      <p class="text-xs text-muted">{{ __('about.eyebrow') }}</p>
      <h1 class="h-display text-lg leading-tight">{{ __('about.heading') }}</h1>
    </div>
    <button type="button" class="icon-btn" data-theme-toggle aria-label="{{ __('about.toggle_theme') }}"><i class="ph ph-moon text-xl"></i></button>
  </header>

  <section class="px-5">
    <div class="k-card-lg hero-profile text-center">
      <div class="relative mx-auto profile-avatar">
        <span>🦉</span>
      </div>
      <h2 class="h-display text-3xl mt-4 relative leading-none">Kidzio</h2>
      <p class="relative text-sm text-white/90 mt-1">{{ __('about.tagline') }}</p>
      <p class="relative mt-3 text-xs text-white/85 max-w-[280px] mx-auto leading-relaxed">{{ __('about.blurb') }}</p>
      <div class="relative mt-4 flex items-center justify-center gap-2 flex-wrap">
        <span class="chip bg-white/20 border-0 text-white">{{ __('about.version', ['version' => $version]) }}</span>
        <span class="chip bg-white/20 border-0 text-white">{{ __('about.latest') }}</span>
      </div>
    </div>
  </section>

  <section class="px-5 mt-5">
    <p class="section-label">{{ __('about.inside') }}</p>
    <div class="mt-3 grid grid-cols-2 gap-3">
      <div class="k-card p-3 text-center">
        <div class="size-10 rounded-2xl tile-violet grid place-items-center text-xl mx-auto">🎮</div>
        <p class="font-extrabold text-sm mt-2 text-ink">{{ __('about.games', ['count' => $games]) }}</p>
        <p class="text-[11px] text-muted">{{ __('about.games_hint') }}</p>
      </div>
      <div class="k-card p-3 text-center">
        <div class="size-10 rounded-2xl tile-sun grid place-items-center text-xl mx-auto">📚</div>
        <p class="font-extrabold text-sm mt-2 text-ink">{{ __('about.subjects', ['count' => $subjects]) }}</p>
        <p class="text-[11px] text-muted">{{ __('about.subjects_hint') }}</p>
      </div>
      <div class="k-card p-3 text-center">
        <div class="size-10 rounded-2xl tile-mint grid place-items-center text-xl mx-auto">🏅</div>
        <p class="font-extrabold text-sm mt-2 text-ink">{{ __('about.badges', ['count' => $badges]) }}</p>
        <p class="text-[11px] text-muted">{{ __('about.badges_hint') }}</p>
      </div>
      <div class="k-card p-3 text-center">
        <div class="size-10 rounded-2xl tile-coral grid place-items-center text-xl mx-auto">🔥</div>
        <p class="font-extrabold text-sm mt-2 text-ink">{{ __('about.streak') }}</p>
        <p class="text-[11px] text-muted">{{ __('about.streak_hint') }}</p>
      </div>
    </div>
  </section>

  <section class="px-5 mt-5">
    <p class="section-label">{{ __('about.values') }}</p>
    <div class="mt-3 space-y-2">
      <div class="setting-row">
        <div class="setting-ico tile-mint"><i class="ph-fill ph-shield-check"></i></div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ __('about.safe') }}</p>
          <p class="text-[11px] text-muted">{{ __('about.safe_hint') }}</p>
        </div>
      </div>
      <div class="setting-row">
        <div class="setting-ico tile-sky"><i class="ph-fill ph-sun"></i></div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ __('about.play') }}</p>
          <p class="text-[11px] text-muted">{{ __('about.play_hint') }}</p>
        </div>
      </div>
      <div class="setting-row">
        <div class="setting-ico tile-sun"><i class="ph-fill ph-users-three"></i></div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ __('about.parents') }}</p>
          <p class="text-[11px] text-muted">{{ __('about.parents_hint') }}</p>
        </div>
      </div>
      <div class="setting-row">
        <div class="setting-ico tile-violet"><i class="ph-fill ph-translate"></i></div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ __('about.georgian') }}</p>
          <p class="text-[11px] text-muted">{{ __('about.georgian_hint') }}</p>
        </div>
      </div>
    </div>
  </section>

  <section class="px-5 mt-5">
    <p class="section-label">{{ __('about.info') }}</p>
    <div class="k-card p-0 overflow-hidden mt-3">
      <div class="flex items-center gap-3 p-3">
        <i class="ph-fill ph-package text-xl text-muted"></i>
        <p class="font-extrabold text-sm grow text-ink">{{ __('about.version_label') }}</p>
        <span class="chip chip-primary">{{ $version }}</span>
      </div>
      <div class="flex items-center gap-3 p-3 border-t border-token">
        <i class="ph-fill ph-translate text-xl text-muted"></i>
        <p class="font-extrabold text-sm grow text-ink">{{ __('about.languages') }}</p>
        <span class="text-xs text-muted font-extrabold">{{ __('about.languages_value', ['count' => $locales]) }}</span>
      </div>
      <div class="flex items-center gap-3 p-3 border-t border-token">
        <i class="ph-fill ph-star text-xl text-muted"></i>
        <p class="font-extrabold text-sm grow text-ink">{{ __('about.store') }}</p>
        <span class="text-xs text-muted font-extrabold">{{ __('about.store_value') }}</span>
      </div>
      <a href="{{ route('terms-privacy') }}" wire:navigate class="flex items-center gap-3 p-3 border-t border-token">
        <i class="ph-fill ph-file-text text-xl text-muted"></i>
        <p class="font-extrabold text-sm grow text-ink">{{ __('about.legal') }}</p>
        <i class="ph ph-caret-right text-muted"></i>
      </a>
    </div>
  </section>

  <section class="px-5 mt-5 mb-10 space-y-2">
    <a href="{{ route('contact-us', ['topic' => 'rate']) }}" wire:navigate class="btn btn-primary w-full">
      <i class="ph-fill ph-star"></i> {{ __('about.rate') }}
    </a>
    <a href="{{ route('contact-us') }}" wire:navigate class="btn btn-ghost w-full">{{ __('about.contact') }}</a>
    <a href="{{ route('help-faq') }}" wire:navigate class="btn btn-ghost w-full">{{ __('about.help') }}</a>
  </section>
</main>
