<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('ქეშის გასუფთავება · Kidzio')] class extends Component {};
?>

<main class="device-frame min-h-screen flex flex-col safe-top" id="clearCache">

  <header class="appbar">
    <a href="{{ route('settings') }}" class="icon-btn" data-back aria-label="{{ __('cache.back') }}"><i class="ph ph-caret-left"></i></a>
    <div class="grow">
      <p class="text-xs text-muted">{{ __('cache.eyebrow') }}</p>
      <h1 class="h-display text-lg leading-tight">{{ __('cache.heading') }}</h1>
    </div>
    <button type="button" class="icon-btn" data-theme-toggle aria-label="{{ __('cache.toggle_theme') }}"><i class="ph ph-moon text-xl"></i></button>
  </header>

  <section class="px-5">
    <div class="k-card-lg hero-knowledge text-center">
      <div class="relative inline-grid place-items-center">
        <div class="size-28 rounded-full bg-white/20 backdrop-blur-sm grid place-items-center">
          <i class="ph-fill ph-database text-6xl text-white"></i>
        </div>
      </div>
      <p class="relative chip bg-white/20 border-0 text-white mt-4">{{ __('cache.chip') }}</p>
      <p class="relative h-display text-5xl mt-2 leading-none text-white"><span id="totalVal">0</span> {{ __('cache.unit') }}</p>
      <p class="relative text-xs text-white/90 mt-1">{{ __('cache.hero_body') }}</p>

      <div class="relative mt-4 flex items-center gap-3">
        <div class="progress on-gradient grow"><span id="totalFill" class="w-0 transition-all duration-700"></span></div>
        <span class="text-sm font-extrabold shrink-0 text-white"><span id="totalPct">0</span>%</span>
      </div>

      <div class="relative mt-4 grid grid-cols-3 gap-2">
        <div class="rounded-2xl bg-white/15 backdrop-blur-sm p-3">
          <p class="h-display text-xl leading-none text-white" id="heroFree">0</p>
          <p class="text-[10px] text-white/85 mt-1">{{ __('cache.free') }}</p>
        </div>
        <div class="rounded-2xl bg-white/15 backdrop-blur-sm p-3">
          <p class="h-display text-xl leading-none text-white" id="heroKeep">0</p>
          <p class="text-[10px] text-white/85 mt-1">{{ __('cache.keep') }}</p>
        </div>
        <div class="rounded-2xl bg-white/15 backdrop-blur-sm p-3">
          <p class="h-display text-xl leading-none text-white" id="heroItems">2</p>
          <p class="text-[10px] text-white/85 mt-1">{{ __('cache.selected') }}</p>
        </div>
      </div>
    </div>
  </section>

  <section class="px-5 mt-5">
    <p class="section-label">{{ __('cache.using') }}</p>
    <div class="k-card p-4 mt-3 space-y-3">
      <div class="flex items-center gap-2 text-sm opacity-70">
        <span class="size-9 rounded-xl tile-violet grid place-items-center text-lg shrink-0">📖</span>
        <span class="grow text-left">
          <span class="font-extrabold text-ink block">{{ __('cache.offline') }}</span>
          <span class="text-[10px] text-muted">{{ __('cache.offline_hint') }}</span>
        </span>
        <span class="text-muted text-xs shrink-0">0 {{ __('cache.unit') }}</span>
      </div>
      <div class="flex items-center gap-2 text-sm">
        <span class="size-9 rounded-xl tile-mint grid place-items-center text-lg shrink-0">🔊</span>
        <span class="grow text-left">
          <span class="font-extrabold text-ink block">{{ __('cache.audio') }}</span>
          <span class="text-[10px] text-muted">{{ __('cache.audio_hint') }}</span>
        </span>
        <span class="text-muted text-xs shrink-0">0 {{ __('cache.unit') }}</span>
      </div>
      <div class="flex items-center gap-2 text-sm">
        <span class="size-9 rounded-xl tile-sun grid place-items-center text-lg shrink-0">🖼️</span>
        <span class="grow text-left">
          <span class="font-extrabold text-ink block">{{ __('cache.images') }}</span>
          <span class="text-[10px] text-muted">{{ __('cache.images_hint') }}</span>
        </span>
        <span class="text-muted text-xs shrink-0">0 {{ __('cache.unit') }}</span>
      </div>
      <div class="flex items-center gap-2 text-sm opacity-70">
        <span class="size-9 rounded-xl tile-coral grid place-items-center text-lg shrink-0">📊</span>
        <span class="grow">
          <span class="font-extrabold text-ink block">{{ __('cache.progress') }}</span>
          <span class="text-[10px] text-muted">{{ __('cache.progress_hint') }}</span>
        </span>
      </div>
    </div>
    <p class="text-[11px] text-muted mt-2 text-center">{{ __('cache.tap_hint') }}</p>
  </section>

  <section class="px-5 mt-5">
    <div class="flex items-end justify-between">
      <p class="section-label">{{ __('cache.what') }}</p>
      <button type="button" id="selectAll" class="link">{{ __('cache.select_all') }}</button>
    </div>
    <div class="mt-3 space-y-2">
      <label class="setting-row cursor-pointer">
        <div class="setting-ico tile-mint"><i class="ph-fill ph-speaker-high"></i></div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ __('cache.audio_row') }}</p>
          <p class="text-[11px] text-muted">{{ __('cache.audio_row_hint') }}</p>
        </div>
        <span class="ks-switch">
          <input type="checkbox" checked data-clear="audio"/>
          <span class="track"></span>
          <span class="thumb"></span>
        </span>
      </label>
      <label class="setting-row cursor-pointer">
        <div class="setting-ico tile-sun"><i class="ph-fill ph-image"></i></div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ __('cache.image_row') }}</p>
          <p class="text-[11px] text-muted">{{ __('cache.image_row_hint') }}</p>
        </div>
        <span class="ks-switch">
          <input type="checkbox" checked data-clear="image"/>
          <span class="track"></span>
          <span class="thumb"></span>
        </span>
      </label>
      <label class="setting-row opacity-70">
        <div class="setting-ico tile-violet"><i class="ph-fill ph-download-simple"></i></div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ __('cache.offline_row') }}</p>
          <p class="text-[11px] text-muted">{{ __('cache.offline_row_hint') }}</p>
        </div>
        <span class="ks-switch">
          <input type="checkbox" disabled data-clear="offline"/>
          <span class="track"></span>
          <span class="thumb"></span>
        </span>
      </label>
    </div>
  </section>

  <section class="px-5 mt-5">
    <div class="tip-card rounded-2xl p-4 flex items-start gap-3">
      <div class="mascot shrink-0 size-11 text-xl">🦉</div>
      <div class="grow">
        <p class="font-extrabold text-sm text-ink">{{ __('cache.safe_title') }}</p>
        <p class="text-xs text-muted">{{ __('cache.safe_body') }}</p>
      </div>
    </div>
  </section>

  <section class="px-5 mt-5 mb-10 space-y-2">
    <button type="button" id="clearBtn" class="btn btn-danger w-full">
      <i class="ph-fill ph-trash"></i> {{ __('cache.clear') }}
    </button>
    <a href="{{ route('settings') }}" wire:navigate class="btn btn-ghost w-full">{{ __('cache.cancel') }}</a>
  </section>

<div id="confirmSheet" class="hidden fixed inset-0 z-50" role="dialog" aria-modal="true">
  <button type="button" id="confirmBackdrop" class="absolute inset-0 size-full bg-black/50 backdrop-blur-sm" aria-label="{{ __('cache.close') }}"></button>
  <div class="absolute left-0 right-0 bottom-0 mx-auto max-w-[430px] bg-surface rounded-t-3xl border-t border-token shadow-2xl safe-bottom">
    <div class="flex justify-center pt-3">
      <span class="block w-10 h-1.5 rounded-full bg-[var(--color-k-border)]"></span>
    </div>
    <div class="px-5 pt-4 pb-6">
      <div class="flex items-start gap-3">
        <div class="size-12 rounded-2xl tile-coral grid place-items-center text-2xl shrink-0">🗑️</div>
        <div class="grow min-w-0">
          <p class="h-display text-xl leading-tight text-ink">{{ __('cache.confirm_title') }}</p>
          <p class="text-xs text-muted mt-1">{{ __('cache.confirm_body') }}</p>
        </div>
        <button type="button" id="confirmClose" class="icon-btn shrink-0" aria-label="{{ __('cache.close') }}"><i class="ph ph-x"></i></button>
      </div>
      <p class="text-[11px] text-muted mt-4 text-center">{{ __('cache.confirm_note') }}</p>
      <div class="mt-4 grid grid-cols-2 gap-2">
        <button type="button" id="confirmCancel" class="btn btn-ghost">{{ __('cache.keep_it') }}</button>
        <button type="button" id="confirmYes" class="btn btn-danger"><i class="ph-fill ph-trash"></i> {{ __('cache.yes') }}</button>
      </div>
    </div>
  </div>
</div>

<div id="doneSheet" class="hidden fixed inset-0 z-50" role="dialog" aria-modal="true">
  <button type="button" id="doneBackdrop" class="absolute inset-0 size-full bg-black/50 backdrop-blur-sm" aria-label="{{ __('cache.close') }}"></button>
  <div class="absolute left-0 right-0 bottom-0 mx-auto max-w-[430px] bg-surface rounded-t-3xl border-t border-token shadow-2xl safe-bottom">
    <div class="px-5 pt-6 pb-6 text-center">
      <div class="mx-auto size-20 rounded-full tile-mint grid place-items-center text-4xl">✨</div>
      <p class="h-display text-2xl mt-3 text-ink">{{ __('cache.done_title') }}</p>
      <p class="text-sm text-muted mt-1">{{ __('cache.done_body') }}</p>
      <div class="mt-5 grid grid-cols-2 gap-2">
        <button type="button" id="doneStay" class="btn btn-ghost">{{ __('cache.stay') }}</button>
        <a href="{{ route('settings') }}" wire:navigate class="btn btn-primary">{{ __('cache.done') }}</a>
      </div>
    </div>
  </div>
</div>
</main>
