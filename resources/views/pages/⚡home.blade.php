<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<main class="device-frame min-h-screen flex flex-col">

    <livewire:profile-header />
    <!-- =============== QUICK STATS RIBBON =============== -->
    <section class="px-5 mt-5 grid grid-cols-3 gap-2">
        <a href="streak.html" class="stat items-start hover:ring-primary transition">
            <span class="stat-label flex items-center gap-1">🔥 {{ __('home.streak') }}</span>
            <span class="stat-value">7 <span class="text-xs font-bold text-muted">{{ __('home.days') }}</span></span>
        </a>
        <a href="xp-progress.html" class="stat items-start hover:ring-primary transition">
            <span class="stat-label flex items-center gap-1">⭐ {{ __('home.xp') }}</span>
            <span class="stat-value" id="xpStat" data-target="1240">0</span>
        </a>
        <a href="league.html" class="stat items-start hover:ring-primary transition">
            <span class="stat-label flex items-center gap-1">🏆 {{ __('home.league') }}</span>
            <span class="stat-value">{{ __('home.gold_league') }}</span>
        </a>
    </section>

    <!-- =============== DAILY MISSION HERO =============== -->
    <section class="px-5 mt-4">
        <a href="daily-mission.html"
            class="block k-card-lg card-hero-primary relative overflow-hidden focus:outline-none">
            <span class="watermark-emoji" aria-hidden="true">🎯</span>
            <div class="relative flex items-center gap-2">
                <span class="chip bg-white/20 border-0 text-white">
                    <i class="ph-fill ph-sparkle"></i> {{ __('home.todays_mission') }}
                </span>
                <span class="chip bg-white/20 border-0 text-white ml-auto">
                    <i class="ph-fill ph-clock"></i> {{ __('home.hours_left') }}
                </span>
            </div>
            <p class="h-display text-2xl mt-2 relative">{{ __('home.finish_3_lessons') }}</p>
            <p class="text-sm text-white relative">{{ __('home.earn_120_xp') }}</p>
            <div class="mt-3 flex items-center gap-2 relative">
                <div class="progress on-gradient grow"><span class="w-66"></span></div>
                <span class="text-xs font-extrabold">{{ __('home.progress_2_of_3') }}</span>
            </div>
            <div class="mt-3 flex items-center gap-2 relative">
                <p class="cta-soft block">{{ __('home.continue') }} <i class="ph-fill ph-arrow-right"></i></p>
                <p class="chip bg-white/20 border-0 block text-white">{{ __('home.plus_120_xp') }}</p>
            </div>
        </a>
    </section>

    <!-- =============== CONTINUE + WEEKLY STREAK =============== -->
    <section class="px-5 mt-4 grid grid-cols-2 gap-3">
        <a href="lesson-continue.html" class="k-card p-4 relative overflow-hidden">
            <div class="flex items-center gap-2">
                <div class="size-9 rounded-xl tile-mint grid place-items-center">➗</div>
                <span class="text-xs font-extrabold text-mint-ink">{{ __('home.continue') }}</span>
            </div>
            <p class="h-display mt-2">{{ __('home.counting_1_20') }}</p>
            <p class="text-xs text-muted">{{ __('home.activities_progress') }}</p>
            <div class="progress progress-mint mt-2"><span class="w-72"></span></div>
        </a>
        <a href="streak.html" class="k-card p-4 relative overflow-hidden">
            <div class="flex items-center gap-2">
                <div class="size-9 rounded-xl tile-sun grid place-items-center">🔥</div>
                <span class="text-xs font-extrabold text-sun-ink">{{ __('home.this_week') }}</span>
            </div>
            <p class="h-display mt-2">5 / 7 {{ __('home.days') }}</p>
            <div class="grid grid-cols-7 gap-1 mt-2" aria-label="Weekly streak" id="weekStreak">
                <span class="streak-dot on opacity-0 transition-all duration-300">M</span>
                <span class="streak-dot on opacity-0 transition-all duration-300">T</span>
                <span class="streak-dot on opacity-0 transition-all duration-300">W</span>
                <span class="streak-dot on opacity-0 transition-all duration-300">T</span>
                <span class="streak-dot on today opacity-0 transition-all duration-300">F</span>
                <span class="streak-dot opacity-0 transition-all duration-300">S</span>
                <span class="streak-dot opacity-0 transition-all duration-300">S</span>
            </div>
        </a>
    </section>

    <!-- =============== TODAY'S PLAN (new section) =============== -->
    <section class="px-5 mt-4">
        <div class="section-head">
            <h2 class="h-display text-lg">{{ __('home.todays_plan') }}</h2>
            <a href="daily-mission.html" class="link">{{ __('home.view_all') }}</a>
        </div>
        <div class="k-card p-0 overflow-hidden">
            <div class="flex items-center gap-3 p-3">
                <div class="size-10 rounded-xl tile-mint grid place-items-center"><i
                        class="ph-fill ph-check-circle text-lg text-mint-ink"></i></div>
                <div class="grow">
                    <p class="font-extrabold text-sm">{{ __('home.finish_a_lesson') }}</p>
                    <p class="text-xs text-muted">{{ __('home.counting_to_20_5min') }}</p>
                </div>
                <span class="chip chip-mint">{{ __('home.plus_40_xp') }}</span>
            </div>
            <div class="flex items-center gap-3 p-3 border-t border-token">
                <div class="size-10 rounded-xl tile-mint grid place-items-center"><i
                        class="ph-fill ph-check-circle text-lg text-mint-ink"></i></div>
                <div class="grow">
                    <p class="font-extrabold text-sm">{{ __('home.play_1_minigame') }}</p>
                    <p class="text-xs text-muted">{{ __('home.any_category') }}</p>
                </div>
                <span class="chip chip-mint">{{ __('home.plus_40_xp') }}</span>
            </div>
            <a href="game-multiple-choice.html" class="flex items-center gap-3 p-3 border-t border-token">
                <div class="size-10 rounded-xl tile-violet grid place-items-center">🎯</div>
                <div class="grow">
                    <p class="font-extrabold text-sm">{{ __('home.get_5_answers_right') }}</p>
                    <p class="text-xs text-muted">{{ __('home.progress_keep_going') }}</p>
                    <div class="progress mt-1"><span class="w-60"></span></div>
                </div>
                <span class="btn btn-primary h-9 min-h-0 px-4 text-sm">{{ __('home.play') }}</span>
            </a>
        </div>
    </section>

    <!-- =============== EXPLORE SUBJECTS (Swiper) =============== -->
    <section class="mt-5">
        <div class="section-head px-5">
            <h2 class="h-display text-lg">{{ __('home.explore_subjects') }}</h2>
            <a href="learn-categories.html" class="link">{{ __('home.see_all') }}</a>
        </div>
        <div class="swiper subjects-swiper" data-swiper-rail>
            <div class="swiper-wrapper">
                <a href="learn-math.html" class="swiper-slide tile tile-violet">
                    <span class="chip chip-on-tile text-violet-ink">{{ __('home.lessons_24') }}</span>
                    <h3 class="mt-3">{{ __('home.math') }}</h3>
                    <p class="text-xs mt-1 text-violet-ink opacity-80">{{ __('home.numbers_shapes') }}</p>
                    <span class="tile-emoji">➗</span>
                </a>
                <a href="learn-alphabet.html" class="swiper-slide tile tile-sun">
                    <span class="chip chip-on-tile text-sun-ink">{{ __('home.lessons_18') }}</span>
                    <h3 class="mt-3">{{ __('home.alphabet') }}</h3>
                    <p class="text-xs mt-1 text-sun-ink opacity-80">{{ __('home.a_to_z_phonics') }}</p>
                    <span class="tile-emoji">🔤</span>
                </a>
                <a href="learn-animals.html" class="swiper-slide tile tile-mint">
                    <span class="chip chip-on-tile text-mint-ink">{{ __('home.lessons_14') }}</span>
                    <h3 class="mt-3">{{ __('home.animals') }}</h3>
                    <p class="text-xs mt-1 text-mint-ink opacity-80">{{ __('home.wildlife_habitats') }}</p>
                    <span class="tile-emoji">🦁</span>
                </a>
                <a href="learn-words.html" class="swiper-slide tile tile-coral">
                    <span class="chip chip-on-tile text-coral-ink">{{ __('home.lessons_20') }}</span>
                    <h3 class="mt-3">{{ __('home.words') }}</h3>
                    <p class="text-xs mt-1 text-coral-ink opacity-80">{{ __('home.read_spell') }}</p>
                    <span class="tile-emoji">📚</span>
                </a>
                <a href="learn-knowledge.html" class="swiper-slide tile tile-sky">
                    <span class="chip chip-on-tile text-sky-ink">{{ __('home.lessons_12') }}</span>
                    <h3 class="mt-3">{{ __('home.knowledge') }}</h3>
                    <p class="text-xs mt-1 text-sky-ink opacity-80">{{ __('home.world_science') }}</p>
                    <span class="tile-emoji">🌍</span>
                </a>
            </div>
        </div>
    </section>

    <!-- =============== FEATURED GAMES =============== -->
    <section class="px-5 mt-4">
        <div class="section-head">
            <h2 class="h-display text-lg">{{ __('home.featured_games') }}</h2>
            <a href="learn-categories.html" class="link">{{ __('home.more') }}</a>
        </div>

        <a href="game-multiple-choice.html" class="k-card-lg card-hero-success relative overflow-hidden block">
            <span class="watermark-emoji" aria-hidden="true">❓</span>
            <div class="relative flex items-center gap-3">
                <div class="size-14 rounded-2xl bg-white/25 grid place-items-center text-3xl">❓</div>
                <div class="grow">
                    <p class="text-xs uppercase tracking-wider font-extrabold">{{ __('home.quick_quiz') }}</p>
                    <p class="h-display text-xl">{{ __('home.beat_yesterdays_score') }}</p>
                    <p class="text-xs text-white mt-1">{{ __('home.quiz_description') }}</p>
                </div>
                <span class="cta-soft">{{ __('home.play') }}</span>
            </div>
        </a>

        <div class="grid grid-cols-2 gap-3 mt-3">
            <a href="game-word-search.html" class="k-card p-4">
                <div class="size-10 rounded-xl tile-pink grid place-items-center mb-2">🔎</div>
                <p class="font-extrabold text-sm">{{ __('home.word_search') }}</p>
                <p class="text-xs text-muted">{{ __('home.word_search_desc') }}</p>
            </a>
            <a href="game-counting.html" class="k-card p-4">
                <div class="size-10 rounded-xl tile-sky grid place-items-center mb-2">🔢</div>
                <p class="font-extrabold text-sm">{{ __('home.counting_fun') }}</p>
                <p class="text-xs text-muted">{{ __('home.add_subtract') }}</p>
            </a>
        </div>
    </section>

    <!-- =============== FRIENDS ACTIVITY (new) =============== -->
    <section class="px-5 mt-5">
        <div class="section-head">
            <h2 class="h-display text-lg">{{ __('home.friends_today') }}</h2>
            <a href="ranking-friends.html" class="link">{{ __('home.ranking') }}</a>
        </div>
        <div class="k-card p-0 overflow-hidden">
            <div class="flex items-center gap-3 p-3">
                <div class="size-10 rounded-full tile-sun grid place-items-center text-xl">👦</div>
                <div class="grow">
                    <p class="font-extrabold text-sm">{{ __('home.leo_finished_math') }}</p>
                    <p class="text-xs text-muted">{{ __('home.minutes_ago_5') }} · {{ __('home.plus_80_xp') }}</p>
                </div>
                <span class="chip chip-sun">
                    <i class="ph-fill ph-fire"></i> {{ __('home.streak_count_12') }}
                </span>
            </div>
            <div class="flex items-center gap-3 p-3 border-t border-token">
                <div class="size-10 rounded-full tile-mint grid place-items-center text-xl">🐰</div>
                <div class="grow">
                    <p class="font-extrabold text-sm">{{ __('home.ana_earned_badge') }}</p>
                    <p class="text-xs text-muted">{{ __('home.hours_ago_1') }}</p>
                </div>
                <span class="chip chip-mint">{{ __('home.new') }}</span>
            </div>
            <a href="ranking-friends.html"
                class="flex items-center justify-center gap-2 p-3 border-t border-token text-sm font-extrabold text-primary-ink">
                {{ __('home.beat_your_friends') }} <i class="ph ph-arrow-right"></i>
            </a>
        </div>
    </section>

    <!-- =============== RECENT ACHIEVEMENTS (Swiper) =============== -->
    <section class="px-5 mt-5">
        <div class="section-head">
            <h2 class="h-display text-lg">{{ __('home.recent_achievements') }}</h2>
            <a href="badges.html" class="link">{{ __('home.see_all') }}</a>
        </div>
        <div class="swiper achievements-swiper" data-swiper-rail>
            <div class="swiper-wrapper">
                <a href="badge-unlock.html" class="swiper-slide k-card text-center flex flex-col items-center gap-2">
                    <span class="badge-medal">🏆</span>
                    <p class="text-xs font-extrabold">{{ __('home.first_win') }}</p>
                    <span class="chip chip-mint">{{ __('home.new') }}</span>
                </a>
                <a href="badges.html" class="swiper-slide k-card text-center flex flex-col items-center gap-2">
                    <span class="badge-medal silver">📚</span>
                    <p class="text-xs font-extrabold">{{ __('home.bookworm') }}</p>
                    <p class="text-[11px] text-muted">{{ __('home.yesterday') }}</p>
                </a>
                <a href="badges.html" class="swiper-slide k-card text-center flex flex-col items-center gap-2">
                    <span class="badge-medal bronze">🔥</span>
                    <p class="text-xs font-extrabold">{{ __('home.on_fire') }}</p>
                    <p class="text-[11px] text-muted">{{ __('home.seven_day_streak') }}</p>
                </a>
                <a href="badges.html" class="swiper-slide k-card text-center flex flex-col items-center gap-2 locked">
                    <span class="badge-medal">🔒</span>
                    <p class="text-xs font-extrabold">{{ __('home.math_pro') }}</p>
                    <p class="text-[11px] text-muted">{{ __('home.lessons_20') }}</p>
                </a>
            </div>
        </div>
    </section>

    <!-- =============== PARENT TIP (new, informative) =============== -->
    <section class="px-5 mt-5">
        <div class="k-card parent-note flex items-start gap-3">
            <div class="size-10 rounded-xl grid place-items-center text-xl bg-white/60 dark:bg-white/10">👨‍👩‍👧</div>
            <div class="grow">
                <p class="font-extrabold text-sm text-ink">{{ __('home.parent_tip') }}</p>
                <p class="text-xs text-muted mt-0.5">{{ __('home.parent_tip_text') }}</p>
            </div>
            <a href="settings.html" class="chip chip-primary">{{ __('home.report') }}</a>
        </div>
    </section>

    <!-- =============== INSTALL PROMPT (only if available) =============== -->
    <section class="px-5 mt-4 mb-5">
        <button data-install class="w-full k-card flex items-center gap-3 text-left">
            <div class="size-10 rounded-xl tile-violet grid place-items-center"><i
                    class="ph-fill ph-download-simple"></i>
            </div>
            <div class="grow">
                <p class="font-extrabold text-sm">{{ __('home.install_kidzio') }}</p>
                <p class="text-xs text-muted">{{ __('home.install_desc') }}</p>
            </div>
            <i class="ph ph-caret-right text-xl"></i>
        </button>
    </section>

    <livewire:bottom-nav-bar />

    <!-- =============== SEARCH OVERLAY =============== -->
    <div id="searchOverlay" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true"
        aria-labelledby="searchTitle">
        <button type="button" id="searchBackdrop"
            class="absolute inset-0 size-full bg-black/50 backdrop-blur-sm opacity-0 transition-opacity duration-300"
            aria-label="{{ __('home.close') }}"></button>
        <div id="searchPanel"
            class="absolute inset-x-0 top-0 bottom-0 mx-auto max-w-[430px] bg-surface translate-y-full transition-transform duration-300 ease-out flex flex-col">
            <header class="appbar safe-top">
                <button type="button" id="searchClose" class="icon-btn" aria-label="{{ __('home.close') }}"><i
                        class="ph ph-caret-left text-xl"></i></button>
                <div class="grow">
                    <p class="text-xs text-muted">{{ __('home.find_anything') }}</p>
                    <h2 id="searchTitle" class="h-display text-lg leading-tight">{{ __('home.search') }}</h2>
                </div>
            </header>

            <section class="px-5">
                <div class="input-wrap">
                    <i class="ph ph-magnifying-glass i-left"></i>
                    <input id="homeSearch" class="input has-left" placeholder="{{ __('home.search_placeholder') }}"
                        aria-label="{{ __('home.search_label') }}" autocomplete="off" />
                    <button id="clearBtn" type="button" class="i-right hidden"
                        aria-label="{{ __('home.clear_search') }}"><i
                            class="ph ph-x-circle text-muted text-xl"></i></button>
                    <button id="micBtn" type="button" class="i-right"
                        aria-label="{{ __('home.voice_search') }}"><i
                            class="ph ph-microphone text-xl text-muted"></i></button>
                </div>
            </section>

            <!-- Suggestions (shown when query is empty) -->
            <div id="searchSuggest" class="overflow-y-auto grow">
                <section class="px-5 mt-4">
                    <p class="section-label">{{ __('home.recent') }}</p>
                    <div class="mt-2 flex flex-wrap gap-2" id="recentChips">
                        <button type="button" class="chip" data-recent>counting</button>
                        <button type="button" class="chip" data-recent>animals</button>
                        <button type="button" class="chip" data-recent>quiz</button>
                    </div>
                </section>

                <section class="px-5 mt-4">
                    <p class="section-label">{{ __('home.popular_right_now') }}</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button type="button" class="chip" data-recent>{{ __('home.search_chip_alphabet') }}</button>
                        <button type="button" class="chip" data-recent>{{ __('home.search_chip_spell') }}</button>
                        <button type="button" class="chip" data-recent>{{ __('home.search_chip_space') }}</button>
                        <button type="button" class="chip" data-recent>{{ __('home.search_chip_shapes') }}</button>
                        <button type="button" class="chip" data-recent>{{ __('home.search_chip_match') }}</button>
                    </div>
                </section>

                <section class="px-5 mt-5">
                    <p class="section-label">{{ __('home.jump_to') }}</p>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <a href="learn-math.html" class="k-card p-3 flex items-center gap-2">
                            <div class="size-9 rounded-xl tile-violet grid place-items-center text-base">➗</div>
                            <span class="font-extrabold text-sm text-ink">{{ __('home.math') }}</span>
                        </a>
                        <a href="learn-alphabet.html" class="k-card p-3 flex items-center gap-2">
                            <div class="size-9 rounded-xl tile-sun grid place-items-center text-base">🔤</div>
                            <span class="font-extrabold text-sm text-ink">{{ __('home.alphabet') }}</span>
                        </a>
                        <a href="learn-animals.html" class="k-card p-3 flex items-center gap-2">
                            <div class="size-9 rounded-xl tile-mint grid place-items-center text-base">🦁</div>
                            <span class="font-extrabold text-sm text-ink">{{ __('home.animals') }}</span>
                        </a>
                        <a href="learn-words.html" class="k-card p-3 flex items-center gap-2">
                            <div class="size-9 rounded-xl tile-coral grid place-items-center text-base">📚</div>
                            <span class="font-extrabold text-sm text-ink">{{ __('home.words') }}</span>
                        </a>
                    </div>
                </section>
            </div>

            <!-- Results (shown when query is not empty) -->
            <div id="searchResults" class="overflow-y-auto grow px-5 mt-4 space-y-2 hidden"></div>

            <div class="px-5 pb-6 pt-3 safe-bottom">
                <a href="learn-categories.html" class="btn btn-ghost w-full">
                    <i class="ph ph-books"></i> {{ __('home.browse_full_library') }}
                </a>
            </div>
        </div>
    </div>

    <!-- =============== NOTIFICATIONS BOTTOM SHEET =============== -->
    <div id="notifSheet" class="hidden fixed inset-0 z-50" role="dialog" aria-modal="true"
        aria-labelledby="notifTitle">
        <button type="button" data-sheet="notifSheet"
            class="absolute inset-0 size-full bg-black/50 backdrop-blur-sm"
            aria-label="{{ __('home.close') }}"></button>
        <div
            class="absolute left-0 right-0 bottom-0 mx-auto max-w-[430px] bg-surface rounded-t-3xl border-t border-token shadow-2xl safe-bottom">
            <div class="flex justify-center pt-3">
                <span class="block w-10 h-1.5 rounded-full bg-[var(--color-k-border)]"></span>
            </div>
            <div class="px-5 pt-4 pb-6">
                <div class="flex items-start gap-3">
                    <div class="size-12 rounded-2xl tile-coral grid place-items-center text-2xl shrink-0">🔔</div>
                    <div class="grow min-w-0">
                        <p id="notifTitle" class="h-display text-xl leading-tight text-ink">
                            {{ __('home.notifications') }}</p>
                        <p class="text-xs text-muted mt-1">{{ __('home.new_today') }}</p>
                    </div>
                    <button type="button" id="markAllBtn" class="chip chip-primary shrink-0"
                        aria-label="{{ __('home.mark_all') }}">
                        <i class="ph ph-check"></i> {{ __('home.mark_all') }}
                    </button>
                    <button type="button" class="icon-btn shrink-0" data-sheet="notifSheet"
                        aria-label="{{ __('home.close') }}">
                        <i class="ph ph-x"></i>
                    </button>
                </div>

                <div class="mt-4 space-y-2 max-h-[55vh] overflow-y-auto" id="notifList">
                    <a href="streak.html" class="setting-row" data-notif>
                        <div class="setting-ico tile-sun"><i class="ph-fill ph-fire"></i></div>
                        <div class="grow min-w-0">
                            <p class="setting-text font-extrabold text-sm text-ink">{{ __('home.streak_at_risk') }}
                            </p>
                            <p class="text-[11px] text-muted">{{ __('home.streak_at_risk_desc') }}</p>
                            <p class="text-[10px] text-muted mt-0.5">{{ __('home.just_now') }}</p>
                        </div>
                        <span class="size-2 rounded-full bg-[var(--color-k-coral)] shrink-0"
                            aria-label="{{ __('home.unread') }}"></span>
                    </a>
                    <a href="badges.html" class="setting-row" data-notif>
                        <div class="setting-ico tile-mint"><i class="ph-fill ph-medal"></i></div>
                        <div class="grow min-w-0">
                            <p class="setting-text font-extrabold text-sm text-ink">
                                {{ __('home.leo_earned_new_badge') }}</p>
                            <p class="text-[11px] text-muted">{{ __('home.leo_badge_desc') }}</p>
                            <p class="text-[10px] text-muted mt-0.5">{{ __('home.minutes_ago_5') }}</p>
                        </div>
                        <span class="size-2 rounded-full bg-[var(--color-k-coral)] shrink-0"
                            aria-label="{{ __('home.unread') }}"></span>
                    </a>
                    <a href="rewards-dashboard.html" class="setting-row" data-notif>
                        <div class="setting-ico tile-violet"><i class="ph-fill ph-gift"></i></div>
                        <div class="grow min-w-0">
                            <p class="setting-text font-extrabold text-sm text-ink">{{ __('home.daily_gift_ready') }}
                            </p>
                            <p class="text-[11px] text-muted">{{ __('home.daily_gift_desc') }}</p>
                            <p class="text-[10px] text-muted mt-0.5">{{ __('home.hours_ago_1') }}</p>
                        </div>
                        <span class="size-2 rounded-full bg-[var(--color-k-coral)] shrink-0"
                            aria-label="{{ __('home.unread') }}"></span>
                    </a>
                    <a href="daily-mission.html" class="setting-row opacity-70" data-notif data-read>
                        <div class="setting-ico tile-sky"><i class="ph-fill ph-target"></i></div>
                        <div class="grow min-w-0">
                            <p class="setting-text font-extrabold text-sm text-ink">
                                {{ __('home.todays_mission_unlocked') }}</p>
                            <p class="text-[11px] text-muted">{{ __('home.todays_mission_desc') }}</p>
                            <p class="text-[10px] text-muted mt-0.5">{{ __('home.this_morning') }}</p>
                        </div>
                    </a>
                    <a href="league.html" class="setting-row opacity-70" data-notif data-read>
                        <div class="setting-ico tile-pink"><i class="ph-fill ph-trophy"></i></div>
                        <div class="grow min-w-0">
                            <p class="setting-text font-extrabold text-sm text-ink">{{ __('home.promoted_to_gold') }}
                            </p>
                            <p class="text-[11px] text-muted">{{ __('home.gold_league_desc') }}</p>
                            <p class="text-[10px] text-muted mt-0.5">{{ __('home.yesterday') }}</p>
                        </div>
                    </a>
                </div>

                <a href="settings.html" class="btn btn-ghost w-full mt-4"><i class="ph ph-gear"></i>
                    {{ __('home.notification_settings') }}</a>
            </div>
        </div>
    </div>
</main>

@push('scripts')
    <script defer src="{{ asset('assets/js/home.js') }}"></script>
@endpush
