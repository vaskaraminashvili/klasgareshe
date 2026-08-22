<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<!-- =============== TAB BAR =============== -->
<nav class="tabbar mt-auto" aria-label="{{ __('nav.primary') }}">
    <div class="tabbar-inner">
        <a class="tab {{ request()->routeIs('home', 'daily-mission') ? 'active' : '' }}" href="{{ route('home') }}" wire:navigate>
            <span class="tab-ico">
                <i class="ph-fill ph-house-simple text-xl"></i>
            </span>{{ __('nav.home') }}</a>
        <a class="tab" href="learn-categories.html">
            <span class="tab-ico">
                <i class="ph ph-books text-xl"></i>
            </span>{{ __('nav.learn') }}</a>
        <a class="tab {{ request()->routeIs('badges', 'badge-unlock') ? 'active' : '' }}" href="{{ route('badges') }}"
            wire:navigate>
            <span class="tab-ico">
                <i class="{{ request()->routeIs('badges', 'badge-unlock') ? 'ph-fill' : 'ph' }} ph-gift text-xl"></i>
            </span>{{ __('nav.rewards') }}</a>
        <a class="tab {{ request()->routeIs('leaderboard', 'ranking-weekly', 'league') ? 'active' : '' }}"
            href="{{ route('leaderboard') }}" wire:navigate>
            <span class="tab-ico">
                <i class="ph ph-trophy text-xl"></i>
            </span>{{ __('nav.ranking') }}</a>
        <a class="tab {{ request()->routeIs('profile') ? 'active' : '' }}" href="{{ route('profile') }}" wire:navigate>
            <span class="tab-ico">
                <i class="ph ph-user-circle text-xl"></i>
            </span>{{ __('nav.profile') }}</a>
    </div>
</nav>
