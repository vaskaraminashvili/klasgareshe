<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<!-- =============== TOP APPBAR =============== -->
<header class="px-5 pt-4 safe-top flex items-center gap-3">
    <a href="{{ route('profile') }}" class="size-11 rounded-2xl grid place-items-center text-2xl tile-sun relative"
        aria-label="{{ __('header.open_profile') }}" wire:navigate>
        🐻
        <span
            class="absolute -bottom-1 -right-1 size-4 rounded-full bg-[var(--color-k-mint)] border-2 border-[var(--color-k-surface)]"
            aria-hidden="true"></span>
    </a>
    <div class="grow leading-tight">
        <p class="text-xs text-muted flex items-center gap-1"><span class="live-dot"> </span><span></span> <span
                id="greetLine">{{ __('header.online_lets_learn') }}</span></p>
        <p class="h-display text-lg">{{ __('header.hi_name', ['name' => 'Luna']) }}</p>
    </div>
    <button id="searchIconBtn" type="button" class="icon-btn" aria-label="{{ __('header.search') }}"><i
            class="ph ph-magnifying-glass text-xl"></i></button>
    <button id="bellBtn" type="button" class="icon-btn relative" data-sheet="notifSheet" aria-haspopup="dialog"
        aria-controls="notifSheet" aria-label="{{ __('header.notifications') }}">
        <i class="ph ph-bell text-xl"></i>
        <span id="bellBadge"
            class="absolute -top-1 -right-1 size-5 rounded-full bg-[var(--color-k-coral)] text-white text-[10px] font-extrabold grid place-items-center">3</span>
    </button>
    <button class="icon-btn" data-theme-toggle aria-label="{{ __('header.toggle_theme') }}">
        <i class="ph ph-moon text-xl"></i>
    </button>
</header>
