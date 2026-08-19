<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<!-- =============== TAB BAR =============== -->
<nav class="tabbar mt-auto" aria-label="Primary">
    <div class="tabbar-inner">
        <a class="tab {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}" wire:navigate>
            <span class="tab-ico">
                <i class="ph-fill ph-house-simple text-xl"></i>
            </span>Home</a>
        <a class="tab" href="learn-categories.html">
            <span class="tab-ico">
                <i class="ph ph-books text-xl"></i>
            </span>Learn</a>
        <a class="tab" href="rewards-dashboard.html">
            <span class="tab-ico">
                <i class="ph ph-gift text-xl"></i>
            </span>Rewards</a>
        <a class="tab" href="leaderboard.html">
            <span class="tab-ico">
                <i class="ph ph-trophy text-xl"></i>
            </span>Ranking</a>
        <a class="tab {{ request()->routeIs('profile') ? 'active' : '' }}" href="{{ route('profile') }}" wire:navigate>
            <span class="tab-ico">
                <i class="ph ph-user-circle text-xl"></i>
            </span>Profile</a>
    </div>
</nav>
