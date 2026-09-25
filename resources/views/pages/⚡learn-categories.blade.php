<?php

use App\Enums\SchoolSubject;
use App\Repositories\UserRepository;
use App\Services\LearnLibraryService;
use App\Services\SearchService;
use Illuminate\View\View;
use Livewire\Attributes\Renderless;
use Livewire\Component;

new class extends Component
{
    public int $subjectCount = 0;

    public int $lessonsDone = 0;

    public int $lessonsTotal = 0;

    public int $gamesCount = 0;

    /** @var list<array{subject: string, label: string, emoji: string, tile: string, inkClass: string, ringClass: string, lessons: int, percent: int, blurb: string, difficulty: string, difficultyClass: string, gradeRange: string, tags: string, diff: string, age: int, status: string, href: string, favourite: bool}> */
    public array $subjects = [];

    /** @var array{title: string, subtitle: string, href: string, percent: int, progressLabel: string, xp: int, minutes: int, emoji: string}|null */
    public ?array $spotlight = null;

    /** @var list<array{title: string, subtitle: string, emoji: string, tile: string, href: string, keywords: string, tags: string}> */
    public array $games = [];

    /** @var list<array{title: string, subtitle: string, emoji: string, tile: string, href: string, keywords: string, subject?: string, week?: int, status?: string}> */
    public array $latest = [];

    /** @var list<array{name: string, keys: string, href: string, ico: string, tile: string, kind: string, subject: string|null, week: int|null, status: string|null}> */
    public array $searchIndex = [];

    /** @var list<string> */
    public array $recentSearches = [];

    /** @var list<string> */
    public array $popularSearches = [];

    /** @var list<int> */
    public array $weeks = [];

    /** @var list<array{value: string, label: string}> */
    public array $subjectFilters = [];

    public function title(): string
    {
        return __('learn.page_title');
    }

    public function rendering(View $view): void
    {
        $view->title($this->title());
    }

    public function mount(LearnLibraryService $learn, UserRepository $users, SearchService $search): void
    {
        $user = $users->authenticated();
        $snap = $learn->library($user);
        $this->subjectCount = $snap->subjectCount;
        $this->lessonsDone = $snap->lessonsDone;
        $this->lessonsTotal = $snap->lessonsTotal;
        $this->gamesCount = $snap->gamesCount;
        $this->subjects = $snap->subjects;
        $this->spotlight = $snap->spotlight;
        $this->games = $snap->games;
        $this->latest = $snap->latest;
        $this->searchIndex = $search->indexFor($user);
        $this->recentSearches = $search->recent($user);
        $this->popularSearches = $search->popular();
        $this->weeks = $search->weekNumbers($user);
        $this->subjectFilters = [];

        foreach (SchoolSubject::ordered() as $subject) {
            $this->subjectFilters[] = [
                'value' => $subject->value,
                'label' => $subject->emoji().' '.$subject->label(),
            ];
        }
    }

    #[Renderless]
    public function recordSearch(string $query, SearchService $search, UserRepository $users): void
    {
        $search->record($users->authenticated(), $query);
    }

    public function itemCount(): int
    {
        return count($this->subjects) + count($this->games) + count($this->latest);
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
    @if ($spotlight)
        <section class="px-5 mt-4">
            <a href="{{ $spotlight['href'] }}" wire:navigate class="k-card-lg hero-learn block relative overflow-hidden">
                <div class="relative flex items-center gap-3">
                    <div class="size-14 rounded-2xl bg-white/25 grid place-items-center text-3xl shrink-0">{{ $spotlight['emoji'] }}</div>
                    <div class="grow">
                        <span class="chip bg-white/20 border-0 text-white">
                            <i class="ph-fill ph-sparkle"></i> {{ __('learn.todays_spotlight') }}
                        </span>
                        <p class="h-display text-xl mt-1 leading-tight">{{ $spotlight['title'] }}</p>
                        <p class="text-xs text-white/90">{{ $spotlight['subtitle'] }}</p>
                    </div>
                </div>
                <div class="relative mt-3 flex items-center gap-2">
                    <div class="progress on-gradient grow"><span style="width: {{ $spotlight['percent'] }}%"></span></div>
                    <span class="text-sm font-extrabold">{{ $spotlight['progressLabel'] }}</span>
                </div>
                <div class="relative mt-3 flex items-center gap-2">
                    <span class="cta-soft">{{ __('learn.start_now') }} <i class="ph-fill ph-arrow-right"></i></span>
                    @if ($spotlight['xp'] > 0)
                        <span class="chip bg-white/20 border-0 text-white">{{ __('learn.plus_xp', ['xp' => $spotlight['xp']]) }}</span>
                    @endif
                    @if ($spotlight['minutes'] > 0)
                        <span class="chip bg-white/20 border-0 text-white ml-auto"><i class="ph-fill ph-clock"></i>
                            {{ __('learn.mins_n', ['n' => $spotlight['minutes']]) }}</span>
                    @endif
                </div>
            </a>
        </section>
    @endif

    <!-- =============== SNAPSHOT STATS =============== -->
    <section class="px-5 mt-4 grid grid-cols-3 gap-2">
        <div class="stat items-start">
            <span class="stat-label">{{ __('learn.subjects') }}</span>
            <span class="stat-value">{{ $subjectCount }}</span>
        </div>
        <div class="stat items-start">
            <span class="stat-label">{{ __('learn.lessons') }}</span>
            <span class="stat-value">{{ $lessonsDone }} <span
                    class="text-xs font-bold text-muted">/ {{ $lessonsTotal }}</span></span>
        </div>
        <div class="stat items-start">
            <span class="stat-label">{{ __('learn.games') }}</span>
            <span class="stat-value">{{ $gamesCount }}</span>
        </div>
    </section>


    <!-- =============== CATEGORIES GRID (enhanced) =============== -->
    <section class="px-5 mt-4" data-search-section>
        <div class="section-head">
            <h2 class="h-display text-lg">{{ __('learn.all_subjects') }}</h2>
            <span class="link cursor-default" data-section-count>{{ __('learn.total_n', ['count' => $subjectCount]) }}</span>
        </div>

        {{-- Kidzio extras (Alphabet / Animals / Words / Knowledge / Opposites) are not v1 — three school subjects only. --}}
        <div class="grid grid-cols-2 gap-3">
            @foreach ($subjects as $tile)
                <a href="{{ $tile['href'] }}" wire:navigate class="tile {{ $tile['tile'] }}" data-item
                    data-name="{{ $tile['label'] }}" data-keywords="{{ $tile['label'] }} {{ $tile['blurb'] }}"
                    data-tags="{{ $tile['tags'] }}" data-diff="{{ $tile['diff'] }}" data-age="{{ $tile['age'] }}"
                    data-subject="{{ $tile['subject'] }}" data-week="" data-status="{{ $tile['status'] }}">
                    <div class="flex items-start justify-between">
                        <span class="tile-meta {{ $tile['inkClass'] }}">{{ __('learn.lessons_n', ['count' => $tile['lessons']]) }}</span>
                        <span class="tile-ring {{ $tile['ringClass'] }}" style="--pct: {{ $tile['percent'] }}"
                            aria-label="{{ __('learn.pct_complete', ['pct' => $tile['percent']]) }}"><span>{{ $tile['percent'] }}%</span></span>
                    </div>
                    <h3 class="mt-4">{{ $tile['label'] }}</h3>
                    <p class="text-xs mt-1 {{ $tile['inkClass'] }} opacity-80">{{ $tile['blurb'] }}</p>
                    <div class="mt-2 flex items-center gap-1">
                        <span
                            class="{{ $tile['difficultyClass'] }} rounded-full px-2 py-0.5 text-[10px] font-extrabold">{{ $tile['difficulty'] }}</span>
                        <span
                            class="{{ $tile['inkClass'] }} text-[10px] font-extrabold opacity-80">{{ __('learn.age_range', ['range' => $tile['gradeRange']]) }}</span>
                    </div>
                    <span class="tile-emoji">{{ $tile['emoji'] }}</span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Trending now (fake play counts) — T02. Mini-games rail below is the live formats. --}}

    <!-- =============== MINI-GAMES =============== -->
    <section class="mt-5" data-search-section>
        <div class="section-head px-5">
            <h2 class="h-display text-lg">{{ __('learn.minigames') }}</h2>
            <span class="link cursor-default">{{ __('learn.play_learn') }}</span>
        </div>
        <div data-swiper-rail class="swiper rail-swiper">
            <div class="swiper-wrapper">
                @foreach ($games as $game)
                    <a href="{{ $game['href'] }}" wire:navigate class="swiper-slide k-card w-40 text-center"
                        data-item data-name="{{ $game['title'] }}" data-keywords="{{ $game['keywords'] }}"
                        data-tags="{{ $game['tags'] }}" data-subject="" data-week="" data-status="">
                        <div class="size-12 rounded-2xl {{ $game['tile'] }} grid place-items-center text-2xl mx-auto mb-2">{{ $game['emoji'] }}</div>
                        <p class="font-extrabold text-sm text-ink">{{ $game['title'] }}</p>
                        <p class="text-xs text-muted">{{ $game['subtitle'] }}</p>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <!-- =============== NEW THIS WEEK =============== -->
    @if ($latest !== [])
        <section class="px-5 mt-5" data-search-section>
            <div class="section-head">
                <h2 class="h-display text-lg">{{ __('learn.new_this_week') }}</h2>
                <span class="link cursor-default">{{ __('learn.fresh') }}</span>
            </div>
            <div class="grid grid-cols-1 gap-3">
                @foreach ($latest as $row)
                    <a href="{{ $row['href'] }}" wire:navigate class="k-card flex items-center gap-3" data-item
                        data-name="{{ $row['title'] }}" data-keywords="{{ $row['keywords'] }}" data-tags="new"
                        data-subject="{{ $row['subject'] ?? '' }}" data-week="{{ $row['week'] ?? '' }}"
                        data-status="{{ $row['status'] ?? 'new' }}">
                        <div class="size-12 rounded-2xl {{ $row['tile'] }} grid place-items-center text-2xl shrink-0">{{ $row['emoji'] }}</div>
                        <div class="grow">
                            <div class="flex items-center gap-2">
                                <p class="font-extrabold text-sm text-ink">{{ $row['title'] }}</p>
                                <span class="day-badge">{{ __('learn.new') }}</span>
                            </div>
                            <p class="text-xs text-muted">{{ $row['subtitle'] }}</p>
                        </div>
                        <i class="ph ph-caret-right text-xl text-muted"></i>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <!-- =============== PARENT TIP =============== -->
    <section class="px-5 mt-5 mb-5">
        <div class="tip-card rounded-2xl p-4 flex items-start gap-3">
            <div class="mascot shrink-0 size-11 text-xl">🦉</div>
            <div class="grow">
                <p class="font-extrabold text-sm text-ink">{{ __('learn.parent_friendly') }}</p>
                <p class="text-xs text-muted">{{ __('learn.parent_tip') }}</p>
            </div>
            <a href="{{ route('parent-controls') }}" wire:navigate class="chip chip-primary">{{ __('learn.controls') }}</a>
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
                    {{-- Voice search mic — T21. --}}
                </div>
            </section>

            @if ($recentSearches !== [])
                <section class="px-5 mt-4">
                    <p class="section-label">{{ __('learn.recent') }}</p>
                    <div class="mt-2 flex flex-wrap gap-2" id="recentChips">
                        @foreach ($recentSearches as $chip)
                            <button type="button" class="chip" data-recent>{{ $chip }}</button>
                        @endforeach
                    </div>
                </section>
            @else
                <div id="recentChips" class="hidden"></div>
            @endif

            @if ($popularSearches !== [])
                <section class="px-5 mt-4">
                    <p class="section-label">{{ __('learn.popular_searches') }}</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($popularSearches as $chip)
                            <button type="button" class="chip" data-recent>{{ $chip }}</button>
                        @endforeach
                    </div>
                </section>
            @endif

            <section id="searchResults" class="px-5 mt-4 overflow-y-auto grow space-y-2"
                data-empty="{{ __('learn.nothing_found') }}"></section>
            <script type="application/json" id="searchIndex">@json($searchIndex, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE)</script>

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
                    <p class="section-label">{{ __('learn.subject') }}</p>
                    <div class="mt-2 flex flex-wrap gap-2" id="subjectChips">
                        <button type="button" class="chip chip-primary" data-subject="all"
                            aria-selected="true">{{ __('learn.all') }}</button>
                        @foreach ($subjectFilters as $subject)
                            <button type="button" class="chip" data-subject="{{ $subject['value'] }}"
                                aria-selected="false">{{ $subject['label'] }}</button>
                        @endforeach
                    </div>
                </section>

                <section class="px-5 mt-5">
                    <p class="section-label">{{ __('learn.week') }}</p>
                    <div class="mt-2 flex flex-wrap gap-2" id="weekChips">
                        <button type="button" class="chip chip-primary" data-week="all"
                            aria-selected="true">{{ __('learn.any') }}</button>
                        @foreach ($weeks as $number)
                            <button type="button" class="chip" data-week="{{ $number }}"
                                aria-selected="false">{{ __('learn.week_n', ['n' => $number]) }}</button>
                        @endforeach
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
                        <button type="button" class="chip" data-status="done"
                            aria-selected="false">{{ __('learn.done') }}</button>
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
                    <i class="ph-fill ph-check"></i> {{ __('learn.show') }} <span id="applyCount">{{ $this->itemCount() }}</span>
                    {{ __('learn.results') }}
                </button>
            </div>
        </div>
    </div>

    <livewire:bottom-nav-bar />
</main>

@push('scripts')
    <script src="{{ asset('assets/js/search.js') }}"></script>
    <script defer src="{{ asset('assets/js/learn-categories.js') }}"></script>
@endpush
