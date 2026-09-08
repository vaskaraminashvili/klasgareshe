<?php

use Livewire\Component;

new class extends Component
{
    public function title(): string
    {
        return __('learn.page_title');
    }

    public function rendering(\Illuminate\View\View $view): void
    {
        $view->title($this->title());
    }
};
?>

<main class="device-frame min-h-screen flex flex-col">

    <!-- =============== APPBAR =============== -->
    <header class="appbar safe-top">
        <div class="grow">
            <p class="text-xs text-muted">{{ __('learn.explore_play') }}</p>
            <h1 class="h-display text-2xl leading-tight">{{ __('learn.library') }}</h1>
        </div>
        <button class="icon-btn" id="searchIconBtn" type="button" aria-label="{{ __('learn.search') }}"><i
                class="ph ph-magnifying-glass text-xl"></i></button>
        <button class="icon-btn relative" id="filterIconBtn" type="button" aria-label="{{ __('learn.filter') }}">
            <i class="ph ph-funnel text-xl"></i>
            <span id="filterDot"
                class="hidden absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-[var(--color-k-primary)] border-2 border-[var(--color-k-surface)]"></span>
        </button>
        <button class="icon-btn" data-theme-toggle aria-label="{{ __('learn.toggle_theme') }}"><i
                class="ph ph-moon text-xl"></i></button>
    </header>

    <!-- =============== ACTIVE QUERY STRIP (shows only when filtering) =============== -->
    <section id="queryStrip" class="px-5 mt-3 hidden">
        <div class="k-card p-2 flex items-center gap-2 flex-wrap">
            <span class="text-xs text-muted shrink-0">{{ __('learn.showing') }}</span>
            <span id="queryChips" class="flex items-center gap-1 flex-wrap grow"></span>
            <button type="button" id="clearAllBtn" class="chip chip-primary shrink-0"><i class="ph ph-x"></i>
                {{ __('learn.clear') }}</button>
        </div>
    </section>

    <!-- =============== NO RESULTS (hidden by default) =============== -->
    <section id="noResults" class="px-5 mt-4 hidden">
        <div class="k-card text-center p-6">
            <div class="size-16 mx-auto rounded-2xl tile-sky grid place-items-center text-3xl">🔍</div>
            <p class="h-display text-lg mt-3 text-ink">{{ __('learn.nothing_found') }}</p>
            <p class="text-xs text-muted mt-1">{{ __('learn.nothing_found_hint') }}</p>
        </div>
    </section>

    <!-- =============== LEARN HERO =============== -->
    <section class="px-5 mt-4">
        <a href="{{ route('daily-mission') }}" wire:navigate class="k-card-lg hero-learn block relative overflow-hidden">
            <div class="relative flex items-center gap-3">
                <div class="size-14 rounded-2xl bg-white/25 grid place-items-center text-3xl shrink-0">🎓</div>
                <div class="grow">
                    <span class="chip bg-white/20 border-0 text-white">
                        <i class="ph-fill ph-sparkle"></i> {{ __('learn.todays_spotlight') }}
                    </span>
                    <p class="h-display text-xl mt-1 leading-tight">{{ __('learn.learn_5_words') }}</p>
                    <p class="text-xs text-white/90">{{ __('learn.finish_todays_plan') }}</p>
                </div>
            </div>
            <div class="relative mt-3 flex items-center gap-2">
                <div class="progress on-gradient grow"><span class="w-40"></span></div>
                <span class="text-sm font-extrabold">{{ __('learn.progress_2_of_5') }}</span>
            </div>
            <div class="relative mt-3 flex items-center gap-2">
                <span class="cta-soft">{{ __('learn.start_now') }} <i class="ph-fill ph-arrow-right"></i></span>
                <span class="chip bg-white/20 border-0 text-white">{{ __('learn.plus_60_xp') }}</span>
                <span class="chip bg-white/20 border-0 text-white ml-auto"><i class="ph-fill ph-clock"></i>
                    {{ __('learn.mins_3') }}</span>
            </div>
        </a>
    </section>

    <!-- =============== SNAPSHOT STATS =============== -->
    <section class="px-5 mt-4 grid grid-cols-3 gap-2">
        <div class="stat items-start">
            <span class="stat-label">{{ __('learn.subjects') }}</span>
            <span class="stat-value">{{ __('learn.subjects_count') }}</span>
        </div>
        <div class="stat items-start">
            <span class="stat-label">{{ __('learn.lessons') }}</span>
            <span class="stat-value">{{ __('learn.lessons_progress') }} <span
                    class="text-xs font-bold text-muted">{{ __('learn.lessons_total') }}</span></span>
        </div>
        <div class="stat items-start">
            <span class="stat-label">{{ __('learn.games') }}</span>
            <span class="stat-value">{{ __('learn.games_count') }}</span>
        </div>
    </section>


    <!-- =============== CATEGORIES GRID (enhanced) =============== -->
    <section class="px-5 mt-4" data-search-section>
        <div class="section-head">
            <h2 class="h-display text-lg">{{ __('learn.all_subjects') }}</h2>
            <span class="link cursor-default" data-section-count>{{ __('learn.total_6') }}</span>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <!-- Math -->
            <a href="#" class="tile tile-violet" data-item data-name="{{ __('learn.math') }}"
                data-keywords="მათემატიკა რიცხვები დათვლა ფორმები შეკრება გამოკლება" data-tags="pop math"
                data-diff="easy" data-age="5" data-status="inprogress">
                <div class="flex items-start justify-between">
                    <span class="tile-meta text-violet-ink">{{ __('learn.lessons_n', ['count' => 24]) }}</span>
                    <span class="tile-ring tile-ring-violet" style="--pct: 35"
                        aria-label="{{ __('learn.pct_complete', ['pct' => 35]) }}"><span>35%</span></span>
                </div>
                <h3 class="mt-4">{{ __('learn.math') }}</h3>
                <p class="text-xs mt-1 text-violet-ink opacity-80">{{ __('learn.math_blurb') }}</p>
                <div class="mt-2 flex items-center gap-1">
                    <span class="pill-easy rounded-full px-2 py-0.5 text-[10px] font-extrabold">{{ __('learn.easy') }}</span>
                    <span class="text-violet-ink text-[10px] font-extrabold opacity-80">{{ __('learn.age_5_9') }}</span>
                </div>
                <span class="tile-emoji">➗</span>
            </a>

            <!-- Alphabet -->
            <a href="#" class="tile tile-sun" data-item data-name="{{ __('learn.alphabet') }}"
                data-keywords="ანბანი ასოები ბგერები კითხვა" data-tags="pop read" data-diff="easy" data-age="4"
                data-status="inprogress">
                <div class="flex items-start justify-between">
                    <span class="tile-meta text-sun-ink">{{ __('learn.lessons_n', ['count' => 18]) }}</span>
                    <span class="tile-ring tile-ring-sun" style="--pct: 62"
                        aria-label="{{ __('learn.pct_complete', ['pct' => 62]) }}"><span>62%</span></span>
                </div>
                <h3 class="mt-4">{{ __('learn.alphabet') }}</h3>
                <p class="text-xs mt-1 text-sun-ink opacity-80">{{ __('learn.alphabet_blurb') }}</p>
                <div class="mt-2 flex items-center gap-1">
                    <span class="pill-easy rounded-full px-2 py-0.5 text-[10px] font-extrabold">{{ __('learn.easy') }}</span>
                    <span class="text-sun-ink text-[10px] font-extrabold opacity-80">{{ __('learn.age_4_7') }}</span>
                </div>
                <span class="tile-emoji">🔤</span>
            </a>

            <!-- Animals -->
            <a href="#" class="tile tile-mint" data-item data-name="{{ __('learn.animals') }}"
                data-keywords="ცხოველები ველური ბუნება ჰაბიტატები ლომი ჟირაფი ძაღლი კატა" data-tags="pop"
                data-diff="medium" data-age="6" data-status="inprogress">
                <div class="flex items-start justify-between">
                    <span class="tile-meta text-mint-ink">{{ __('learn.lessons_n', ['count' => 14]) }}</span>
                    <span class="tile-ring tile-ring-mint" style="--pct: 50"
                        aria-label="{{ __('learn.pct_complete', ['pct' => 50]) }}"><span>50%</span></span>
                </div>
                <h3 class="mt-4">{{ __('learn.animals') }}</h3>
                <p class="text-xs mt-1 text-mint-ink opacity-80">{{ __('learn.animals_blurb') }}</p>
                <div class="mt-2 flex items-center gap-1">
                    <span
                        class="pill-medium rounded-full px-2 py-0.5 text-[10px] font-extrabold">{{ __('learn.medium') }}</span>
                    <span class="text-mint-ink text-[10px] font-extrabold opacity-80">{{ __('learn.age_6_plus') }}</span>
                </div>
                <span class="tile-emoji">🦁</span>
            </a>

            <!-- Words -->
            <a href="#" class="tile tile-coral" data-item data-name="{{ __('learn.words') }}"
                data-keywords="სიტყვები მართლწერა ლექსიკა კითხვა" data-tags="read" data-diff="medium" data-age="5"
                data-status="inprogress">
                <div class="flex items-start justify-between">
                    <span class="tile-meta text-coral-ink">{{ __('learn.lessons_n', ['count' => 20]) }}</span>
                    <span class="tile-ring tile-ring-coral" style="--pct: 20"
                        aria-label="{{ __('learn.pct_complete', ['pct' => 20]) }}"><span>20%</span></span>
                </div>
                <h3 class="mt-4">{{ __('learn.words') }}</h3>
                <p class="text-xs mt-1 text-coral-ink opacity-80">{{ __('learn.words_blurb') }}</p>
                <div class="mt-2 flex items-center gap-1">
                    <span
                        class="pill-medium rounded-full px-2 py-0.5 text-[10px] font-extrabold">{{ __('learn.medium') }}</span>
                    <span class="text-coral-ink text-[10px] font-extrabold opacity-80">{{ __('learn.age_5_8') }}</span>
                </div>
                <span class="tile-emoji">📚</span>
            </a>

            <!-- Knowledge -->
            <a href="#" class="tile tile-sky" data-item data-name="{{ __('learn.knowledge') }}"
                data-keywords="ცოდნა მსოფლიო მეცნიერება კოსმოსი პლანეტები" data-tags="new" data-diff="hard"
                data-age="8" data-status="new">
                <div class="flex items-start justify-between">
                    <span class="tile-meta text-sky-ink">{{ __('learn.lessons_n', ['count' => 12]) }}</span>
                    <span class="day-badge">{{ __('learn.new') }}</span>
                </div>
                <h3 class="mt-4">{{ __('learn.knowledge') }}</h3>
                <p class="text-xs mt-1 text-sky-ink opacity-80">{{ __('learn.knowledge_blurb') }}</p>
                <div class="mt-2 flex items-center gap-1">
                    <span
                        class="pill-hard rounded-full px-2 py-0.5 text-[10px] font-extrabold">{{ __('learn.challenge') }}</span>
                    <span class="text-sky-ink text-[10px] font-extrabold opacity-80">{{ __('learn.age_7_plus') }}</span>
                </div>
                <span class="tile-emoji">🌍</span>
            </a>

            <!-- Opposites -->
            <a href="#" class="tile tile-pink" data-item data-name="{{ __('learn.opposites') }}"
                data-keywords="საპირისპიროები დიდი პატარა ცხელი ცივი ზევით ქვევით" data-tags="new" data-diff="easy"
                data-age="4" data-status="new">
                <div class="flex items-start justify-between">
                    <span class="tile-meta text-coral-ink">{{ __('learn.lessons_n', ['count' => 10]) }}</span>
                    <span class="tile-ring tile-ring-pink" style="--pct: 0"
                        aria-label="{{ __('learn.not_started') }}"><span>0%</span></span>
                </div>
                <h3 class="mt-4">{{ __('learn.opposites') }}</h3>
                <p class="text-xs mt-1 text-coral-ink opacity-80">{{ __('learn.opposites_blurb') }}</p>
                <div class="mt-2 flex items-center gap-1">
                    <span class="pill-easy rounded-full px-2 py-0.5 text-[10px] font-extrabold">{{ __('learn.easy') }}</span>
                    <span class="text-coral-ink text-[10px] font-extrabold opacity-80">{{ __('learn.age_4_6') }}</span>
                </div>
                <span class="tile-emoji">⚖️</span>
            </a>
        </div>
    </section>

    <!-- =============== TRENDING NOW =============== -->
    <section class="mt-6" data-search-section>
        <div class="section-head px-5">
            <h2 class="h-display text-lg">{{ __('learn.trending_now') }}</h2>
            <a href="#" class="link">{{ __('learn.see_all') }}</a>
        </div>
        <div data-swiper-rail class="swiper rail-swiper">
            <div class="swiper-wrapper">
                <a href="{{ route('game-multiple-choice') }}" wire:navigate class="swiper-slide trend-card" data-item
                    data-name="{{ __('learn.quick_quiz') }}" data-keywords="ვიქტორინა კითხვები მრავალარჩევანი"
                    data-tags="pop games">
                    <div class="trend-ico tile-violet">❓</div>
                    <div class="grow">
                        <p class="font-extrabold text-sm text-ink">{{ __('learn.quick_quiz') }}</p>
                        <p class="text-xs text-muted">{{ __('learn.played_n_today', ['count' => 240]) }}</p>
                    </div>
                    <i class="ph ph-caret-right text-muted"></i>
                </a>
                <a href="#" class="swiper-slide trend-card" data-item data-name="{{ __('learn.match_animal') }}"
                    data-keywords="ცხოველები შესატყვისი ჟირაფი ზებრა ლომი" data-tags="pop games">
                    <div class="trend-ico tile-mint">🦒</div>
                    <div class="grow">
                        <p class="font-extrabold text-sm text-ink">{{ __('learn.match_animal') }}</p>
                        <p class="text-xs text-muted">{{ __('learn.played_n_today', ['count' => 180]) }}</p>
                    </div>
                    <i class="ph ph-caret-right text-muted"></i>
                </a>
                <a href="#" class="swiper-slide trend-card" data-item data-name="{{ __('learn.spell_the_word') }}"
                    data-keywords="მართლწერა სიტყვები ასოები" data-tags="pop games read">
                    <div class="trend-ico tile-sun">✏️</div>
                    <div class="grow">
                        <p class="font-extrabold text-sm text-ink">{{ __('learn.spell_the_word') }}</p>
                        <p class="text-xs text-muted">{{ __('learn.played_n_today', ['count' => 150]) }}</p>
                    </div>
                    <i class="ph ph-caret-right text-muted"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- =============== MINI-GAMES =============== -->
    <section class="mt-5" data-search-section>
        <div class="section-head px-5">
            <h2 class="h-display text-lg">{{ __('learn.minigames') }}</h2>
            <a href="#" class="link">{{ __('learn.play_learn') }}</a>
        </div>
        <div data-swiper-rail class="swiper rail-swiper">
            <div class="swiper-wrapper">
                <a href="{{ route('game-multiple-choice') }}" wire:navigate class="swiper-slide k-card w-40 text-center"
                    data-item data-name="{{ __('learn.quick_quiz') }}"
                    data-keywords="ვიქტორინა კითხვები მრავალარჩევანი" data-tags="games pop">
                    <div class="size-12 rounded-2xl tile-violet grid place-items-center text-2xl mx-auto mb-2">❓</div>
                    <p class="font-extrabold text-sm text-ink">{{ __('learn.quick_quiz') }}</p>
                    <p class="text-xs text-muted">{{ __('learn.quiz_meta') }}</p>
                </a>
                <a href="#" class="swiper-slide k-card w-40 text-center" data-item
                    data-name="{{ __('learn.match_words') }}" data-keywords="შესატყვისი სიტყვები ლექსიკა"
                    data-tags="games read">
                    <div class="size-12 rounded-2xl tile-mint grid place-items-center text-2xl mx-auto mb-2">🧩</div>
                    <p class="font-extrabold text-sm text-ink">{{ __('learn.match_words') }}</p>
                    <p class="text-xs text-muted">{{ __('learn.drag_drop') }}</p>
                </a>
                <a href="#" class="swiper-slide k-card w-40 text-center" data-item
                    data-name="{{ __('learn.word_search') }}" data-keywords="სიტყვის ძიება ასოები"
                    data-tags="games read">
                    <div class="size-12 rounded-2xl tile-coral grid place-items-center text-2xl mx-auto mb-2">🔎</div>
                    <p class="font-extrabold text-sm text-ink">{{ __('learn.word_search') }}</p>
                    <p class="text-xs text-muted">{{ __('learn.find_5_words') }}</p>
                </a>
                <a href="#" class="swiper-slide k-card w-40 text-center" data-item
                    data-name="{{ __('learn.counting') }}" data-keywords="დათვლა რიცხვები მათემატიკა"
                    data-tags="games math">
                    <div class="size-12 rounded-2xl tile-sky grid place-items-center text-2xl mx-auto mb-2">🔢</div>
                    <p class="font-extrabold text-sm text-ink">{{ __('learn.counting') }}</p>
                    <p class="text-xs text-muted">{{ __('learn.counting_range') }}</p>
                </a>
                <a href="#" class="swiper-slide k-card w-40 text-center" data-item
                    data-name="{{ __('learn.trace_letter') }}" data-keywords="ასოს დახაზვა ხელწერა ანბანი"
                    data-tags="games read">
                    <div class="size-12 rounded-2xl tile-sun grid place-items-center text-2xl mx-auto mb-2">✍️</div>
                    <p class="font-extrabold text-sm text-ink">{{ __('learn.trace_letter') }}</p>
                    <p class="text-xs text-muted">{{ __('learn.follow_dots') }}</p>
                </a>
            </div>
        </div>
    </section>

    <!-- =============== NEW THIS WEEK =============== -->
    <section class="px-5 mt-5" data-search-section>
        <div class="section-head">
            <h2 class="h-display text-lg">{{ __('learn.new_this_week') }}</h2>
            <span class="link cursor-default">{{ __('learn.fresh') }}</span>
        </div>
        <div class="grid grid-cols-1 gap-3">
            <a href="#" class="k-card flex items-center gap-3" data-item data-name="{{ __('learn.space_planets') }}"
                data-keywords="კოსმოსი პლანეტები მეცნიერება ცოდნა" data-tags="new">
                <div class="size-12 rounded-2xl tile-sky grid place-items-center text-2xl shrink-0">🪐</div>
                <div class="grow">
                    <div class="flex items-center gap-2">
                        <p class="font-extrabold text-sm text-ink">{{ __('learn.space_planets') }}</p>
                        <span class="day-badge">{{ __('learn.new') }}</span>
                    </div>
                    <p class="text-xs text-muted">{{ __('learn.space_meta') }}</p>
                </div>
                <i class="ph ph-caret-right text-xl text-muted"></i>
            </a>
            <a href="#" class="k-card flex items-center gap-3" data-item data-name="{{ __('learn.big_word_hunt') }}"
                data-keywords="სიტყვის ძიება ნადირობა ასოები" data-tags="new games read">
                <div class="size-12 rounded-2xl tile-coral grid place-items-center text-2xl shrink-0">🔎</div>
                <div class="grow">
                    <div class="flex items-center gap-2">
                        <p class="font-extrabold text-sm text-ink">{{ __('learn.big_word_hunt') }}</p>
                        <span class="chip chip-mint">{{ __('learn.free') }}</span>
                    </div>
                    <p class="text-xs text-muted">{{ __('learn.word_hunt_meta') }}</p>
                </div>
                <i class="ph ph-caret-right text-xl text-muted"></i>
            </a>
        </div>
    </section>

    <!-- =============== PARENT TIP =============== -->
    <section class="px-5 mt-5 mb-5">
        <div class="tip-card rounded-2xl p-4 flex items-start gap-3">
            <div class="mascot shrink-0 size-11 text-xl">🦉</div>
            <div class="grow">
                <p class="font-extrabold text-sm text-ink">{{ __('learn.parent_friendly') }}</p>
                <p class="text-xs text-muted">{{ __('learn.parent_tip') }}</p>
            </div>
            <a href="#" class="chip chip-primary">{{ __('learn.controls') }}</a>
        </div>
    </section>

    <!-- =============== SEARCH OVERLAY =============== -->
    <div id="searchOverlay" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true"
        aria-labelledby="searchTitle">
        <button type="button" id="searchBackdrop"
            class="absolute inset-0 size-full bg-black/50 backdrop-blur-sm opacity-0 transition-opacity duration-300"
            aria-label="{{ __('learn.close') }}"></button>
        <div id="searchPanel"
            class="absolute inset-x-0 top-0 bottom-0 mx-auto max-w-[430px] bg-surface translate-y-full transition-transform duration-300 ease-out flex flex-col">
            <header class="appbar safe-top">
                <button type="button" id="searchClose" class="icon-btn" aria-label="{{ __('learn.close') }}"><i
                        class="ph ph-caret-left text-xl"></i></button>
                <div class="grow">
                    <p class="text-xs text-muted">{{ __('learn.find_anything') }}</p>
                    <h2 id="searchTitle" class="h-display text-lg leading-tight">{{ __('learn.search') }}</h2>
                </div>
            </header>

            <section class="px-5">
                <div class="input-wrap">
                    <i class="ph ph-magnifying-glass i-left"></i>
                    <input id="libSearch" class="input has-left" placeholder="{{ __('learn.search_placeholder') }}"
                        aria-label="{{ __('learn.search') }}" autocomplete="off" />
                    <button id="clearBtn" type="button" class="i-right hidden"
                        aria-label="{{ __('learn.clear_search') }}"><i
                            class="ph ph-x-circle text-muted text-xl"></i></button>
                    <button id="micBtn" type="button" class="i-right" aria-label="{{ __('learn.voice_search') }}"><i
                            class="ph ph-microphone text-xl text-muted"></i></button>
                </div>
            </section>

            <section class="px-5 mt-4">
                <p class="section-label">{{ __('learn.recent') }}</p>
                <div class="mt-2 flex flex-wrap gap-2" id="recentChips">
                    <button type="button" class="chip" data-recent>{{ __('learn.chip_animals') }}</button>
                    <button type="button" class="chip" data-recent>{{ __('learn.chip_counting') }}</button>
                    <button type="button" class="chip" data-recent>{{ __('learn.chip_alphabet') }}</button>
                </div>
            </section>

            <section class="px-5 mt-4">
                <p class="section-label">{{ __('learn.popular_searches') }}</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <button type="button" class="chip" data-recent>{{ __('learn.chip_quiz') }}</button>
                    <button type="button" class="chip" data-recent>{{ __('learn.chip_spell') }}</button>
                    <button type="button" class="chip" data-recent>{{ __('learn.chip_space') }}</button>
                    <button type="button" class="chip" data-recent>{{ __('learn.chip_shapes') }}</button>
                </div>
            </section>

            <section id="searchResults" class="px-5 mt-4 overflow-y-auto grow space-y-2"></section>

            <div class="px-5 pb-6 safe-bottom">
                <button type="button" id="applySearchBtn" class="btn btn-primary w-full" disabled>
                    <i class="ph-fill ph-check"></i> {{ __('learn.apply_to_library') }}
                </button>
            </div>
        </div>
    </div>

    <!-- =============== FILTER OVERLAY =============== -->
    <div id="filterOverlay" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true"
        aria-labelledby="filterTitle">
        <button type="button" id="filterBackdrop"
            class="absolute inset-0 size-full bg-black/50 backdrop-blur-sm opacity-0 transition-opacity duration-300"
            aria-label="{{ __('learn.close') }}"></button>
        <div id="filterPanel"
            class="absolute inset-x-0 top-0 bottom-0 mx-auto max-w-[430px] bg-surface translate-y-full transition-transform duration-300 ease-out flex flex-col">
            <header class="appbar safe-top">
                <button type="button" id="filterClose" class="icon-btn" aria-label="{{ __('learn.close') }}"><i
                        class="ph ph-caret-left text-xl"></i></button>
                <div class="grow">
                    <p class="text-xs text-muted">{{ __('learn.narrow_the_list') }}</p>
                    <h2 id="filterTitle" class="h-display text-lg leading-tight">{{ __('learn.filter') }}</h2>
                </div>
                <button type="button" id="filterReset" class="chip">{{ __('learn.reset') }}</button>
            </header>

            <div class="overflow-y-auto grow">
                <section class="px-5 mt-2">
                    <p class="section-label">{{ __('learn.category') }}</p>
                    <div class="mt-2 flex flex-wrap gap-2" id="catChips">
                        <button type="button" class="chip chip-primary" data-filter="all"
                            aria-selected="true">{{ __('learn.all') }}</button>
                        <button type="button" class="chip" data-filter="pop"
                            aria-selected="false">{{ __('learn.popular') }}</button>
                        <button type="button" class="chip" data-filter="new"
                            aria-selected="false">{{ __('learn.new_chip') }}</button>
                        <button type="button" class="chip" data-filter="games"
                            aria-selected="false">{{ __('learn.games_chip') }}</button>
                        <button type="button" class="chip" data-filter="read"
                            aria-selected="false">{{ __('learn.reading') }}</button>
                        <button type="button" class="chip" data-filter="math"
                            aria-selected="false">{{ __('learn.math_chip') }}</button>
                    </div>
                </section>

                <section class="px-5 mt-5">
                    <p class="section-label">{{ __('learn.difficulty') }}</p>
                    <div class="mt-2 flex flex-wrap gap-2" id="diffChips">
                        <button type="button" class="chip chip-primary" data-diff="all"
                            aria-selected="true">{{ __('learn.any') }}</button>
                        <button type="button" class="chip" data-diff="easy"
                            aria-selected="false">{{ __('learn.easy_chip') }}</button>
                        <button type="button" class="chip" data-diff="medium"
                            aria-selected="false">{{ __('learn.medium_chip') }}</button>
                        <button type="button" class="chip" data-diff="hard"
                            aria-selected="false">{{ __('learn.hard_chip') }}</button>
                    </div>
                </section>

                <section class="px-5 mt-5">
                    <p class="section-label">{{ __('learn.age') }}</p>
                    <div class="mt-2 flex flex-wrap gap-2" id="ageChips">
                        <button type="button" class="chip chip-primary" data-age="all"
                            aria-selected="true">{{ __('learn.any') }}</button>
                        <button type="button" class="chip" data-age="4"
                            aria-selected="false">{{ __('learn.age_4_6_chip') }}</button>
                        <button type="button" class="chip" data-age="6"
                            aria-selected="false">{{ __('learn.age_6_8_chip') }}</button>
                        <button type="button" class="chip" data-age="8"
                            aria-selected="false">{{ __('learn.age_8_plus_chip') }}</button>
                    </div>
                </section>

                <section class="px-5 mt-5 mb-5">
                    <p class="section-label">{{ __('learn.status') }}</p>
                    <div class="mt-2 flex flex-wrap gap-2" id="statusChips">
                        <button type="button" class="chip chip-primary" data-status="all"
                            aria-selected="true">{{ __('learn.status_all') }}</button>
                        <button type="button" class="chip" data-status="inprogress"
                            aria-selected="false">{{ __('learn.in_progress') }}</button>
                        <button type="button" class="chip" data-status="new"
                            aria-selected="false">{{ __('learn.not_started_chip') }}</button>
                    </div>
                </section>

                <div class="px-5 mt-5 pb-24">
                    <div class="tip-card rounded-2xl p-4 flex items-start gap-3">
                        <div class="mascot shrink-0 size-11 text-xl">🦉</div>
                        <div class="grow">
                            <p class="font-extrabold text-sm text-ink">{{ __('learn.tip') }}</p>
                            <p class="text-xs text-muted">{{ __('learn.filter_tip') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-5 pb-6 pt-3 safe-bottom border-t border-token bg-surface">
                <button type="button" id="applyFilterBtn" class="btn btn-primary w-full">
                    <i class="ph-fill ph-check"></i> {{ __('learn.show') }} <span id="applyCount">6</span>
                    {{ __('learn.results') }}
                </button>
            </div>
        </div>
    </div>

    <livewire:bottom-nav-bar />
</main>

@push('scripts')
    <script defer src="{{ asset('assets/js/learn-categories.js') }}"></script>
@endpush
