<?php

use App\Repositories\UserRepository;
use App\Services\RewardService;
use Livewire\Component;

new class extends Component
{
    public int $claimCount = 0;

    public function rendering(): void
    {
        $this->claimCount = app(RewardService::class)->pendingCount(
            app(UserRepository::class)->authenticated(),
        );
    }
};
?>

<nav class="tabbar mt-auto" aria-label="{{ __('nav.primary') }}">
    <div class="tabbar-inner">
        <a class="tab {{ request()->routeIs('home', 'daily-mission') ? 'active' : '' }}" href="{{ route('home') }}">
            <span class="tab-ico">
                <i class="ph-fill ph-house-simple text-xl"></i>
            </span>{{ __('nav.home') }}</a>
        <a class="tab {{ request()->routeIs('learn-categories') ? 'active' : '' }}"
            href="{{ route('learn-categories') }}" wire:navigate>
            <span class="tab-ico">
                <i class="{{ request()->routeIs('learn-categories') ? 'ph-fill' : 'ph' }} ph-books text-xl"></i>
            </span>{{ __('nav.learn') }}</a>
        <a class="tab {{ request()->routeIs('rewards-dashboard', 'badges', 'badge-unlock') ? 'active' : '' }}" href="{{ route('rewards-dashboard') }}"
            wire:navigate>
            <span class="tab-ico relative">
                <i class="{{ request()->routeIs('rewards-dashboard', 'badges', 'badge-unlock') ? 'ph-fill' : 'ph' }} ph-gift text-xl"></i>
                @if ($claimCount > 0)
                    <span class="absolute -top-1 -right-1 size-5 rounded-full bg-[var(--color-k-coral)] text-white text-[10px] font-extrabold grid place-items-center">{{ $claimCount }}</span>
                @endif
            </span>{{ __('nav.rewards') }}</a>
        <a class="tab {{ request()->routeIs('leaderboard', 'ranking-weekly', 'ranking-friends', 'league') ? 'active' : '' }}"
            href="{{ route('leaderboard') }}" wire:navigate>
            <span class="tab-ico">
                <i class="ph ph-trophy text-xl"></i>
            </span>{{ __('nav.ranking') }}</a>
        <a class="tab {{ request()->routeIs('profile', 'settings', 'edit-profile') ? 'active' : '' }}" href="{{ route('profile') }}" wire:navigate>
            <span class="tab-ico">
                <i class="{{ request()->routeIs('profile', 'settings', 'edit-profile') ? 'ph-fill' : 'ph' }} ph-user-circle text-xl"></i>
            </span>{{ __('nav.profile') }}</a>
    </div>
</nav>
