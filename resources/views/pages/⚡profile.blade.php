<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public function logout(): void
    {
        Auth::logout();

        session()->invalidate();
        session()->regenerateToken();

        $this->redirectRoute('user-login');
    }
};
?>

<main class="device-frame min-h-screen flex flex-col">

    <!-- =============== APPBAR =============== -->
    <header class="appbar safe-top">
        <div class="grow">
            <p class="text-xs text-muted">{{ __('profile.your_learning_world') }}</p>
            <h1 class="h-display text-2xl leading-tight">{{ __('profile.profile') }}</h1>
        </div>
        <a href="edit-profile.html" class="icon-btn" aria-label="{{ __('profile.edit_profile') }}"><i
                class="ph ph-pencil-simple text-xl"></i></a>
        <a href="settings.html" class="icon-btn" aria-label="{{ __('profile.settings') }}"><i
                class="ph ph-gear text-xl"></i></a>
        <button class="icon-btn" data-theme-toggle aria-label="{{ __('profile.toggle_theme') }}"><i
                class="ph ph-moon text-xl"></i></button>
    </header>

    <!-- =============== PROFILE HERO =============== -->
    <section class="px-5">
        <div class="k-card-lg hero-profile text-center">
            <div class="relative">
                <span class="profile-avatar">
                    <span>🐻</span>
                    <a href="edit-profile.html" class="camera-badge" aria-label="{{ __('profile.change_avatar') }}">
                        <i class="ph-fill ph-camera text-sm"></i>
                    </a>
                </span>
            </div>

            <h2 class="h-display text-2xl mt-4 relative">Luna Parker</h2>
            <p class="text-xs text-white/90 relative">
                {{ __('profile.age_grade_country', ['age' => 6, 'grade' => __('onboarding.age.groups.kindergarten'), 'country' => 'USA']) }}
            </p>

            <div class="relative mt-3 flex items-center justify-center gap-2">
                <span class="chip bg-white/20 border-0 text-white">
                    <i class="ph-fill ph-crown-simple"></i>
                    {{ __('profile.level_rank', ['level' => 7, 'rank' => __('onboarding.age.groups.explorer')]) }}
                </span>
                <span class="chip bg-white/20 border-0 text-white">
                    <span class="live-dot"></span> {{ __('profile.online') }}
                </span>
            </div>

            <div class="relative mt-5 grid grid-cols-4 gap-2">
                <div class="hero-metric">
                    <p class="hm-v">1,240</p>
                    <p class="hm-l">{{ __('profile.xp') }}</p>
                </div>
                <div class="hero-metric">
                    <p class="hm-v">7</p>
                    <p class="hm-l">{{ __('profile.streak') }}</p>
                </div>
                <div class="hero-metric">
                    <p class="hm-v">8</p>
                    <p class="hm-l">{{ __('profile.badges') }}</p>
                </div>
                <div class="hero-metric">
                    <p class="hm-v">#5</p>
                    <p class="hm-l">{{ __('profile.rank') }}</p>
                </div>
            </div>

            <div class="relative mt-4">
                <div class="flex items-center gap-3">
                    <div class="progress on-gradient grow"><span class="w-62"></span></div>
                    <span class="text-sm font-extrabold shrink-0">{{ __('profile.level', ['level' => 8]) }}</span>
                </div>
                <p class="text-[11px] text-white/90 mt-1">{{ __('profile.xp_to_level_up', ['xp' => 760]) }}</p>
            </div>

            <div class="relative mt-4 flex items-center justify-center gap-2">
                <a href="edit-profile.html" class="cta-soft">
                    <i class="ph-fill ph-pencil-simple"></i> {{ __('profile.edit_profile') }}
                </a>
                <button class="chip bg-white/20 border-0 text-white">
                    <i class="ph-fill ph-share-fat"></i> {{ __('profile.share') }}
                </button>
            </div>
        </div>
    </section>

    <!-- =============== QUICK SHORTCUTS =============== -->
    <section class="px-5 mt-4 grid grid-cols-3 gap-3">
        <a href="streak.html" class="k-card p-3 text-center">
            <div class="size-10 rounded-2xl tile-sun grid place-items-center text-xl mx-auto">🔥</div>
            <p class="h-display text-lg mt-1">7</p>
            <p class="text-[11px] text-muted font-extrabold">{{ __('profile.day_streak') }}</p>
        </a>
        <a href="xp-progress.html" class="k-card p-3 text-center">
            <div class="size-10 rounded-2xl tile-violet grid place-items-center text-xl mx-auto">⭐</div>
            <p class="h-display text-lg mt-1">1,240</p>
            <p class="text-[11px] text-muted font-extrabold">{{ __('profile.total_xp') }}</p>
        </a>
        <a href="league.html" class="k-card p-3 text-center">
            <div class="size-10 rounded-2xl tile-mint grid place-items-center text-xl mx-auto">🏆</div>
            <p class="h-display text-lg mt-1">{{ __('profile.gold') }}</p>
            <p class="text-[11px] text-muted font-extrabold">{{ __('profile.league') }}</p>
        </a>
    </section>

    <!-- =============== RECENT BADGES =============== -->
    <section class="mt-5">
        <div class="section-head px-5">
            <h2 class="h-display text-lg">{{ __('profile.recent_badges') }}</h2>
            <a href="badges.html" class="link">{{ __('profile.all_count', ['count' => 8]) }}</a>
        </div>
        <div data-swiper-rail class="swiper rail-swiper">
            <div class="swiper-wrapper">
                <a href="badge-unlock.html" class="swiper-slide k-card text-center w-28">
                    <span class="badge-medal mx-auto">🏆</span>
                    <p class="text-xs font-extrabold mt-2">{{ __('profile.first_win') }}</p>
                    <p class="text-[10px] text-muted">{{ __('profile.yesterday') }}</p>
                </a>
                <a href="badges.html" class="swiper-slide k-card text-center w-28">
                    <span class="badge-medal silver mx-auto">📚</span>
                    <p class="text-xs font-extrabold mt-2">{{ __('profile.bookworm') }}</p>
                    <p class="text-[10px] text-muted">{{ __('profile.days_ago', ['days' => 2]) }}</p>
                </a>
                <a href="badges.html" class="swiper-slide k-card text-center w-28">
                    <span class="badge-medal bronze mx-auto">🔥</span>
                    <p class="text-xs font-extrabold mt-2">{{ __('profile.on_fire') }}</p>
                    <p class="text-[10px] text-muted">{{ __('profile.week_2') }}</p>
                </a>
                <a href="badges.html" class="swiper-slide k-card text-center w-28">
                    <span class="badge-medal mx-auto">🎯</span>
                    <p class="text-xs font-extrabold mt-2">{{ __('profile.target') }}</p>
                    <p class="text-[10px] text-muted">{{ __('profile.week_2') }}</p>
                </a>
                <a href="badges.html" class="swiper-slide k-card text-center w-28 locked">
                    <span class="badge-medal mx-auto">🔒</span>
                    <p class="text-xs font-extrabold mt-2">{{ __('profile.math_pro') }}</p>
                    <p class="text-[10px] text-muted">{{ __('profile.next') }}</p>
                </a>
            </div>
        </div>
    </section>

    <!-- =============== SUBJECT MASTERY =============== -->
    <section class="px-5 mt-5">
        <div class="section-head">
            <h2 class="h-display text-lg">{{ __('profile.subject_mastery') }}</h2>
            <a href="learn-categories.html" class="link">{{ __('profile.explore') }}</a>
        </div>
        <div class="space-y-2">
            <a href="learn-math.html" class="mastery-row">
                <div class="mastery-ico tile-violet">➗</div>
                <div class="grow min-w-0">
                    <div class="flex items-center gap-2">
                        <p class="font-extrabold text-sm text-ink">{{ __('profile.math') }}</p>
                        <span class="text-[11px] text-muted font-extrabold ml-auto">74%</span>
                    </div>
                    <div class="progress mt-1"><span class="w-75"></span></div>
                </div>
            </a>
            <a href="learn-alphabet.html" class="mastery-row">
                <div class="mastery-ico tile-sun">🔤</div>
                <div class="grow min-w-0">
                    <div class="flex items-center gap-2">
                        <p class="font-extrabold text-sm text-ink">{{ __('profile.alphabet') }}</p>
                        <span class="text-[11px] text-muted font-extrabold ml-auto">62%</span>
                    </div>
                    <div class="progress progress-sun mt-1"><span class="w-62"></span></div>
                </div>
            </a>
            <a href="learn-animals.html" class="mastery-row">
                <div class="mastery-ico tile-mint">🦁</div>
                <div class="grow min-w-0">
                    <div class="flex items-center gap-2">
                        <p class="font-extrabold text-sm text-ink">{{ __('profile.animals') }}</p>
                        <span class="text-[11px] text-muted font-extrabold ml-auto">50%</span>
                    </div>
                    <div class="progress progress-mint mt-1"><span class="w-50"></span></div>
                </div>
            </a>
            <a href="learn-words.html" class="mastery-row">
                <div class="mastery-ico tile-coral">📚</div>
                <div class="grow min-w-0">
                    <div class="flex items-center gap-2">
                        <p class="font-extrabold text-sm text-ink">{{ __('profile.words') }}</p>
                        <span class="text-[11px] text-muted font-extrabold ml-auto">30%</span>
                    </div>
                    <div class="progress progress-coral mt-1"><span class="w-30"></span></div>
                </div>
            </a>
        </div>
    </section>

    <!-- =============== THIS WEEK ACTIVITY =============== -->
    <section class="px-5 mt-5">
        <div class="section-head">
            <h2 class="h-display text-lg">{{ __('profile.this_week') }}</h2>
            <span
                class="link cursor-default">{{ __('profile.week_range', ['start' => 'Apr 11', 'end' => 'Apr 17']) }}</span>
        </div>
        <div class="k-card p-4">
            <div class="grid grid-cols-3 gap-3 text-center">
                <div>
                    <p class="h-display text-lg">+940</p>
                    <p class="text-[10px] text-muted font-extrabold uppercase tracking-wide">
                        {{ __('profile.xp_earned') }}</p>
                </div>
                <div>
                    <p class="h-display text-lg">6 / 7</p>
                    <p class="text-[10px] text-muted font-extrabold uppercase tracking-wide">
                        {{ __('profile.active_days') }}</p>
                </div>
                <div>
                    <p class="h-display text-lg">12</p>
                    <p class="text-[10px] text-muted font-extrabold uppercase tracking-wide">
                        {{ __('profile.lessons') }}</p>
                </div>
            </div>
            <div class="mt-3 grid grid-cols-7 gap-2">
                <span class="streak-dot on">M</span>
                <span class="streak-dot on">T</span>
                <span class="streak-dot on">W</span>
                <span class="streak-dot on">T</span>
                <span class="streak-dot on">F</span>
                <span class="streak-dot on today">S</span>
                <span class="streak-dot">S</span>
            </div>
        </div>
    </section>

    <!-- =============== ACHIEVEMENTS TIMELINE =============== -->
    <section class="px-5 mt-5">
        <div class="section-head">
            <h2 class="h-display text-lg">{{ __('profile.recent_achievements') }}</h2>
            <a href="badges.html" class="link">{{ __('profile.see_all') }}</a>
        </div>
        <div class="space-y-2">
            <div class="ach-mini">
                <div class="ach-ico">🏆</div>
                <div class="grow min-w-0">
                    <p class="font-extrabold text-sm text-ink">{{ __('profile.unlocked_counter_champ') }}</p>
                    <p class="text-[11px] text-muted">{{ __('profile.today_finished_math_lessons', ['count' => 5]) }}
                    </p>
                </div>
                <span class="chip chip-mint">{{ __('profile.new') }}</span>
            </div>
            <div class="ach-mini">
                <div class="ach-ico">🔥</div>
                <div class="grow min-w-0">
                    <p class="font-extrabold text-sm text-ink">{{ __('profile.seven_day_streak_milestone') }}</p>
                    <p class="text-[11px] text-muted">{{ __('profile.today_bonus_xp_earned', ['bonus' => 50]) }}</p>
                </div>
                <span class="chip chip-sun">+50</span>
            </div>
            <div class="ach-mini">
                <div class="ach-ico">🎯</div>
                <div class="grow min-w-0">
                    <p class="font-extrabold text-sm text-ink">{{ __('profile.daily_mission_complete') }}</p>
                    <p class="text-[11px] text-muted">{{ __('profile.yesterday_all_tasks_done') }}</p>
                </div>
                <span class="chip chip-primary">+120</span>
            </div>
        </div>
    </section>

    <!-- =============== FRIENDS STRIP =============== -->
    <section class="px-5 mt-5">
        <div class="section-head">
            <h2 class="h-display text-lg">{{ __('profile.friends') }}</h2>
            <a href="ranking-friends.html" class="link">{{ __('profile.view_all') }}</a>
        </div>
        <a href="ranking-friends.html" class="k-card p-3 flex items-center gap-3">
            <div class="avatar-stack">
                <span class="size-10 rounded-full tile-sun grid place-items-center text-lg">👦</span>
                <span class="size-10 rounded-full tile-mint grid place-items-center text-lg">👧</span>
                <span class="size-10 rounded-full tile-coral grid place-items-center text-lg">🧒</span>
                <span class="size-10 rounded-full tile-sky grid place-items-center text-lg">🐰</span>
            </div>
            <div class="grow">
                <p class="font-extrabold text-sm text-ink">
                    {{ __('profile.friends_online', ['total' => 8, 'online' => 5]) }}</p>
                <p class="text-[11px] text-muted">{{ __('profile.beating_friends_this_week', ['count' => 3]) }}</p>
            </div>
            <i class="ph ph-caret-right text-muted"></i>
        </a>
    </section>

    <!-- =============== MENU LIST =============== -->
    <section class="px-5 mt-5">
        <p class="section-label">{{ __('profile.quick_links') }}</p>
        <div class="mt-3 space-y-2">
            <a href="streak.html" class="menu-row">
                <div class="menu-ico tile-sun">🔥</div>
                <p class="menu-text font-extrabold text-sm grow">{{ __('profile.daily_streak') }}</p>
                <span class="chip chip-sun">🔥 7</span>
                <i class="ph ph-caret-right text-muted"></i>
            </a>
            <a href="xp-progress.html" class="menu-row">
                <div class="menu-ico tile-violet">📈</div>
                <p class="menu-text font-extrabold text-sm grow">{{ __('profile.xp_progress') }}</p>
                <span class="chip chip-primary">{{ __('profile.level', ['level' => 7]) }}</span>
                <i class="ph ph-caret-right text-muted"></i>
            </a>
            <a href="monthly-goals.html" class="menu-row">
                <div class="menu-ico tile-mint">🎯</div>
                <p class="menu-text font-extrabold text-sm grow">{{ __('profile.monthly_goals') }}</p>
                <span class="chip chip-mint">3 / 4</span>
                <i class="ph ph-caret-right text-muted"></i>
            </a>
            <a href="badges.html" class="menu-row">
                <div class="menu-ico tile-coral">🏅</div>
                <p class="menu-text font-extrabold text-sm grow">{{ __('profile.all_badges') }}</p>
                <span class="chip chip-primary">8 / 24</span>
                <i class="ph ph-caret-right text-muted"></i>
            </a>
            <a href="rewards-dashboard.html" class="menu-row">
                <div class="menu-ico tile-pink">🎁</div>
                <p class="menu-text font-extrabold text-sm grow">{{ __('profile.rewards_dashboard') }}</p>
                <span class="chip chip-coral">3 {{ __('profile.new') }}</span>
                <i class="ph ph-caret-right text-muted"></i>
            </a>
        </div>
    </section>

    <!-- =============== PARENT ZONE =============== -->
    <section class="px-5 mt-5">
        <p class="section-label">{{ __('profile.parent_zone') }}</p>
        <div class="mt-3 space-y-2">
            <a href="parent-verify.html" class="menu-row">
                <div class="menu-ico tile-sky"><i class="ph-fill ph-shield-check text-[#0B476E]"></i></div>
                <p class="menu-text font-extrabold text-sm grow">{{ __('profile.parent_controls') }}</p>
                <i class="ph ph-caret-right text-muted"></i>
            </a>
            <a href="settings.html" class="menu-row">
                <div class="menu-ico tile-violet"><i class="ph-fill ph-chart-bar text-[#2c1680]"></i></div>
                <p class="menu-text font-extrabold text-sm grow">{{ __('profile.weekly_report') }}</p>
                <span class="chip chip-mint">{{ __('profile.new') }}</span>
            </a>
            <a href="settings.html" class="menu-row">
                <div class="menu-ico tile-coral"><i class="ph-fill ph-timer text-[#7E1E34]"></i></div>
                <p class="menu-text font-extrabold text-sm grow">{{ __('profile.screen_time') }}</p>
                <span class="chip">30 {{ __('profile.min') }}</span>
            </a>
        </div>
    </section>

    <!-- =============== SETTINGS / LOGOUT =============== -->
    <section class="px-5 mt-5 mb-5">
        <p class="section-label">{{ __('profile.account') }}</p>
        <div class="mt-3 space-y-2">
            <a href="settings.html" class="menu-row">
                <div class="menu-ico tile-mint">⚙️</div>
                <p class="menu-text font-extrabold text-sm grow">{{ __('profile.settings') }}</p>
                <i class="ph ph-caret-right text-muted"></i>
            </a>
            <button data-install hidden class="menu-row w-full text-left">
                <div class="menu-ico tile-violet"><i class="ph-fill ph-download-simple"></i></div>
                <p class="menu-text font-extrabold text-sm grow">{{ __('profile.install_kidzio') }}</p>
                <span class="chip chip-primary">PWA</span>
            </button>
            <button type="button" class="menu-row danger w-full text-left" wire:click="logout">
                <div class="menu-ico"><i class="ph-fill ph-sign-out"></i></div>
                <p class="menu-text font-extrabold text-sm grow">{{ __('profile.log_out') }}</p>
            </button>
        </div>
        <p class="text-xs text-center text-muted mt-5">{{ __('profile.footer_version') }}</p>
    </section>

    <livewire:bottom-nav-bar />
</main>
