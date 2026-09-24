<?php

use App\Enums\GameType;
use App\Repositories\UserRepository;
use App\Services\BadgeService;
use App\Services\ProgressReportService;
use App\Services\SearchService;
use App\Services\UserStatService;
use App\Services\WeekPlanService;
use Livewire\Component;

new class extends Component
{
    public int $streak = 0;

    public int $xp = 0;

    public string $leagueLabel = '';

    public int $weekActiveDays = 0;

    /** @var list<array{letter: string, on: bool, today: bool}> */
    public array $weekDays = [];

    public int $missionDone = 0;

    public int $missionTotal = 3;

    public int $hoursLeft = 0;

    public string $heroTitle = '';

    public ?int $continueItemId = null;

    public string $continueHref = '';

    public string $countingHref = '';

    public string $continueTitle = '';

    public int $weekCompleted = 0;

    public int $weekTotal = 0;

    /**
     * @var list<array{id: int|null, subject: string, title: string, subtitle: string, completed: bool, playable: bool, emoji: string, tile: string, inkClass: string, href: string}>
     */
    public array $planTasks = [];

    /**
     * @var list<array{slug: string, name: string, emoji: string, medalClass: string, meta: string, href: string, locked: bool, unseen: bool}>
     */
    public array $recentBadges = [];

    /** @var list<array{name: string, keys: string, href: string, ico: string, tile: string}> */
    public array $searchIndex = [];

    public string $parentTipTitle = '';

    public string $parentTipBody = '';

    public function mount(UserStatService $stats, WeekPlanService $week, UserRepository $users, BadgeService $badges, SearchService $search): void
    {
        $this->syncHome($stats, $week, $users, $badges, $search);
    }

    public function hydrate(UserStatService $stats, WeekPlanService $week, UserRepository $users, BadgeService $badges, SearchService $search): void
    {
        $this->syncHome($stats, $week, $users, $badges, $search);
    }

    public function refreshHome(UserStatService $stats, WeekPlanService $week, UserRepository $users, BadgeService $badges, SearchService $search): void
    {
        $this->syncHome($stats, $week, $users, $badges, $search);
    }

    private function syncHome(UserStatService $stats, WeekPlanService $week, UserRepository $users, BadgeService $badges, SearchService $search): void
    {
        $user = $users->authenticated();
        $home = $stats->homeSnapshot($user);
        $plan = $week->homePlan($user);

        $this->streak = $home->streak;
        $this->xp = $home->xp;
        $this->leagueLabel = $home->leagueLabel;

        $reports = app(ProgressReportService::class);
        $reportWeek = $reports->weekSnapshot($user);
        $this->weekActiveDays = $home->weekActiveDays;
        $this->weekDays = $home->weekDays;
        $this->missionDone = $plan->missionDone;
        $this->missionTotal = $plan->missionTotal;
        $this->hoursLeft = $plan->hoursLeft;
        $this->heroTitle = $plan->heroTitle;
        $this->continueItemId = $plan->continueItemId;
        $this->continueHref = $week->playUrl($plan->continueItemId, missionIfMissing: true);
        $counting = $week->firstIncompleteOfType($user, GameType::Counting);
        $this->countingHref = $counting !== null
            ? $week->playUrl($counting->id)
            : route('game-counting');
        $this->continueTitle = $plan->continueTitle;
        $this->weekCompleted = $plan->weekCompleted;
        $this->weekTotal = $plan->weekTotal;
        $this->planTasks = [];
        $this->recentBadges = array_map(fn ($card) => $card->toArray(), $badges->recentRail($user));

        foreach ($plan->tasks as $task) {
            $this->planTasks[] = [
                'id' => $task->id,
                'subject' => $task->subject->label(),
                'title' => $task->title,
                'subtitle' => $task->subtitle,
                'completed' => $task->completed,
                'playable' => $task->playable,
                'emoji' => $task->emoji,
                'tile' => $task->tile,
                'inkClass' => $task->inkClass,
                'href' => $task->href,
            ];
        }

        $this->searchIndex = $search->homeCatalog($this->planTasks, $this->continueItemId);

        $tip = $reports->homeTip($user, $reportWeek);
        $this->parentTipTitle = $tip['title'];
        $this->parentTipBody = $tip['body'];
    }

    public function missionProgressPercent(): int
    {
        if ($this->missionTotal === 0) {
            return 0;
        }

        return (int) round(($this->missionDone / $this->missionTotal) * 100);
    }

    public function weekProgressPercent(): int
    {
        if ($this->weekTotal === 0) {
            return 0;
        }

        return (int) round(($this->weekCompleted / $this->weekTotal) * 100);
    }
};
?>

<main class="device-frame min-h-screen flex flex-col"
    x-data
    x-on:livewire:navigated.window="$wire.refreshHome()"
    x-on:pageshow.window="if ($event.persisted) $wire.refreshHome()">

    <livewire:profile-header />
    <!-- =============== QUICK STATS RIBBON =============== -->
    <section class="px-5 mt-5 grid grid-cols-3 gap-2">
        <a href="{{ route('streak') }}" wire:navigate class="stat items-start hover:ring-primary transition">
            <span class="stat-label flex items-center gap-1">🔥 {{ __('home.streak') }}</span>
            <span class="stat-value">{{ $streak }} <span class="text-xs font-bold text-muted">{{ __('home.days') }}</span></span>
        </a>
        <a href="{{ route('xp-progress') }}" wire:navigate class="stat items-start hover:ring-primary transition">
            <span class="stat-label flex items-center gap-1">⭐ {{ __('home.xp') }}</span>
            <span class="stat-value" id="xpStat" data-target="{{ $xp }}">0</span>
        </a>
        <a href="{{ route('league') }}" wire:navigate class="stat items-start hover:ring-primary transition">
            <span class="stat-label flex items-center gap-1">🏆 {{ __('home.league') }}</span>
            <span class="stat-value">{{ $leagueLabel }}</span>
        </a>
    </section>

    <!-- =============== DAILY MISSION HERO =============== -->
    <section class="px-5 mt-4">
        <a href="{{ route('daily-mission') }}"
            wire:navigate
            class="block k-card-lg card-hero-primary relative overflow-hidden focus:outline-none">
            <span class="watermark-emoji" aria-hidden="true">🎯</span>
            <div class="relative flex items-center gap-2">
                <span class="chip bg-white/20 border-0 text-white">
                    <i class="ph-fill ph-sparkle"></i> {{ __('home.todays_mission') }}
                </span>
                <span class="chip bg-white/20 border-0 text-white ml-auto">
                    <i class="ph-fill ph-clock"></i> {{ __('home.hours_left', ['hours' => $hoursLeft]) }}
                </span>
            </div>
            <p class="h-display text-2xl mt-2 relative">{{ $heroTitle }}</p>
            <p class="text-sm text-white relative">{{ __('home.earn_120_xp') }}</p>
            <div class="mt-3 flex items-center gap-2 relative">
                <div class="progress on-gradient grow"><span style="width:{{ $this->missionProgressPercent() }}%"></span></div>
                <span class="text-xs font-extrabold">{{ __('home.progress_n_of', ['done' => $missionDone, 'total' => $missionTotal]) }}</span>
            </div>
            <div class="mt-3 flex items-center gap-2 relative">
                <p class="cta-soft block">{{ __('home.continue') }} <i class="ph-fill ph-arrow-right"></i></p>
                <p class="chip bg-white/20 border-0 block text-white">{{ __('home.plus_120_xp') }}</p>
            </div>
        </a>
    </section>

    <!-- =============== CONTINUE + WEEKLY STREAK =============== -->
    <section class="px-5 mt-4 grid grid-cols-2 gap-3">
        <a href="{{ $continueHref }}" wire:navigate class="k-card p-4 relative overflow-hidden">
            <div class="flex items-center gap-2">
                <div class="size-9 rounded-xl tile-mint grid place-items-center">➗</div>
                <span class="text-xs font-extrabold text-mint-ink">{{ __('home.continue') }}</span>
            </div>
            <p class="h-display mt-2">{{ $continueTitle }}</p>
            <p class="text-xs text-muted">{{ __('home.week_progress', ['done' => $weekCompleted, 'total' => $weekTotal]) }}</p>
            <div class="progress progress-mint mt-2"><span style="width:{{ $this->weekProgressPercent() }}%"></span></div>
        </a>
        <a href="{{ route('streak') }}" wire:navigate class="k-card p-4 relative overflow-hidden">
            <div class="flex items-center gap-2">
                <div class="size-9 rounded-xl tile-sun grid place-items-center">🔥</div>
                <span class="text-xs font-extrabold text-sun-ink">{{ __('home.this_week') }}</span>
            </div>
            <p class="h-display mt-2">{{ $weekActiveDays }} / 7 {{ __('home.days') }}</p>
            <div class="grid grid-cols-7 gap-1 mt-2" aria-label="Weekly streak" id="weekStreak">
                @foreach ($weekDays as $day)
                    <span class="streak-dot{{ $day['on'] ? ' on' : '' }}{{ $day['today'] ? ' today' : '' }} opacity-0 transition-all duration-300">{{ $day['letter'] }}</span>
                @endforeach
            </div>
        </a>
    </section>

    <!-- =============== TODAY'S PLAN (new section) =============== -->
    <section class="px-5 mt-4">
        <div class="section-head">
            <h2 class="h-display text-lg">{{ __('home.todays_plan') }}</h2>
            <a href="{{ route('daily-mission') }}" wire:navigate class="link">{{ __('home.view_all') }}</a>
        </div>
        <div class="k-card p-0 overflow-hidden">
            @forelse ($planTasks as $task)
                @if ($task['playable'] && $task['id'] !== null)
                    <a href="{{ $task['href'] }}" wire:navigate class="flex items-center gap-3 p-3{{ ! $loop->first ? ' border-t border-token' : '' }}">
                        <div class="size-10 rounded-xl {{ $task['tile'] }} grid place-items-center">{{ $task['emoji'] }}</div>
                        <div class="grow">
                            <p class="font-extrabold text-sm">{{ $task['title'] }}</p>
                            <p class="text-xs text-muted">{{ $task['subtitle'] }}</p>
                        </div>
                        <span class="btn btn-primary h-9 min-h-0 px-4 text-sm">{{ __('home.play') }}</span>
                    </a>
                @else
                    <div class="flex items-center gap-3 p-3{{ ! $loop->first ? ' border-t border-token' : '' }}">
                        <div class="size-10 rounded-xl tile-mint grid place-items-center"><i
                                class="ph-fill ph-check-circle text-lg text-mint-ink"></i></div>
                        <div class="grow">
                            <p class="font-extrabold text-sm">{{ $task['title'] }}</p>
                            <p class="text-xs text-muted">{{ $task['subtitle'] }}</p>
                        </div>
                        <span class="chip chip-mint">{{ __('home.plus_40_xp') }}</span>
                    </div>
                @endif
            @empty
                <div class="flex items-center gap-3 p-3">
                    <div class="size-10 rounded-xl tile-mint grid place-items-center"><i
                            class="ph-fill ph-check-circle text-lg text-mint-ink"></i></div>
                    <div class="grow">
                        <p class="font-extrabold text-sm">{{ __('home.week_complete') }}</p>
                        <p class="text-xs text-muted">{{ __('home.subject_complete') }}</p>
                    </div>
                </div>
            @endforelse
        </div>
    </section>

    <!-- =============== EXPLORE SUBJECTS (Swiper) =============== -->
    <section class="mt-5">
        <div class="section-head px-5">
            <h2 class="h-display text-lg">{{ __('home.explore_subjects') }}</h2>
            <a href="{{ route('daily-mission') }}" wire:navigate class="link">{{ __('home.see_all') }}</a>
        </div>
        <div class="swiper subjects-swiper" data-swiper-rail>
            <div class="swiper-wrapper">
                @foreach ($planTasks as $task)
                    <a href="{{ $task['href'] }}"
                        wire:navigate class="swiper-slide tile {{ $task['tile'] }}">
                        <span class="chip chip-on-tile {{ $task['inkClass'] }}">{{ $task['subtitle'] }}</span>
                        <h3 class="mt-3">{{ $task['subject'] }}</h3>
                        <p class="text-xs mt-1 {{ $task['inkClass'] }} opacity-80">{{ $task['title'] }}</p>
                        <span class="tile-emoji">{{ $task['emoji'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <!-- =============== FEATURED GAMES =============== -->
    <section class="px-5 mt-4">
        <div class="section-head">
            <h2 class="h-display text-lg">{{ __('home.featured_games') }}</h2>
            <a href="{{ route('daily-mission') }}" wire:navigate class="link">{{ __('home.more') }}</a>
        </div>

        <a href="{{ $continueHref }}" wire:navigate class="k-card-lg card-hero-success relative overflow-hidden block">
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
            {{-- Word-search tile dropped until docs/tasks/T19-minigames-batch-2.md. --}}
            <a href="{{ $countingHref }}" wire:navigate class="k-card p-4">
                <div class="size-10 rounded-xl tile-sky grid place-items-center mb-2">🔢</div>
                <p class="font-extrabold text-sm">{{ __('home.counting_fun') }}</p>
                <p class="text-xs text-muted">{{ __('home.add_subtract') }}</p>
            </a>
        </div>
    </section>

    {{-- FRIENDS ACTIVITY dropped: every row was invented (Leo, Ana, their streaks and XP).
         Re-port the section from kidzio/home.html once a real feed exists
         (docs/tasks/T18-ranking-depth.md). The /profile friends strip is the live one. --}}

    <!-- =============== RECENT ACHIEVEMENTS (Swiper) =============== -->
    <section class="px-5 mt-5 mb-5">
        <div class="section-head">
            <h2 class="h-display text-lg">{{ __('home.recent_achievements') }}</h2>
            <a href="{{ route('badges') }}" wire:navigate class="link">{{ __('home.see_all') }}</a>
        </div>
        <div class="swiper achievements-swiper" data-swiper-rail>
            <div class="swiper-wrapper">
                @foreach ($recentBadges as $badge)
                    <a href="{{ $badge['href'] }}" wire:navigate
                        class="swiper-slide k-card text-center flex flex-col items-center gap-2{{ $badge['locked'] ? ' locked' : '' }}">
                        <span class="badge-medal {{ $badge['medalClass'] }}">{{ $badge['emoji'] }}</span>
                        <p class="text-xs font-extrabold">{{ $badge['name'] }}</p>
                        @if ($badge['unseen'])
                            <span class="chip chip-mint">{{ __('home.new') }}</span>
                        @else
                            <p class="text-[11px] text-muted">{{ $badge['meta'] }}</p>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="px-5 mt-5">
        <div class="k-card parent-note flex items-start gap-3">
            <div class="size-10 rounded-xl grid place-items-center text-xl bg-white/60 dark:bg-white/10">👨‍👩‍👧</div>
            <div class="grow">
                <p class="font-extrabold text-sm text-ink">{{ $parentTipTitle }}</p>
                <p class="text-xs text-muted mt-0.5">{{ $parentTipBody }}</p>
            </div>
            <a href="{{ route('weekly-report') }}" wire:navigate class="chip chip-primary">{{ __('reports.home_tip_chip') }}</a>
        </div>
    </section>

    {{-- INSTALL PROMPT dropped: no manifest or service worker, so `beforeinstallprompt`
         never fires and the row was a dead end (docs/tasks/T15-splash-walkthrough-pwa.md). --}}

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
                    {{-- Mic dropped: voice search recognised en-US only, with no Georgian
                         model (docs/tasks/T21-sound-voice-appearance.md). --}}
                </div>
            </section>

            <!-- Suggestions (shown when query is empty) -->
            <div id="searchSuggest" class="overflow-y-auto grow">
                {{-- "Recent" and "popular" chip rows dropped: recents were three hardcoded
                     English words and the popular chips searched for games that do not
                     exist. Both come back with real query data in docs/tasks/T17-search.md. --}}

                <section class="px-5 mt-5">
                    <p class="section-label">{{ __('home.jump_to') }}</p>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        @foreach ($planTasks as $task)
                            <a href="{{ $task['href'] }}"
                                wire:navigate class="k-card p-3 flex items-center gap-2">
                                <div class="size-9 rounded-xl {{ $task['tile'] }} grid place-items-center text-base">
                                    {{ $task['emoji'] }}</div>
                                <span class="font-extrabold text-sm text-ink">{{ $task['subject'] }}</span>
                            </a>
                        @endforeach
                        <a href="{{ route('learn-categories') }}" wire:navigate class="k-card p-3 flex items-center gap-2">
                            <div class="size-9 rounded-xl tile-coral grid place-items-center text-base">📚</div>
                            <span class="font-extrabold text-sm text-ink">{{ __('home.search_to.library.name') }}</span>
                        </a>
                    </div>
                </section>
            </div>

            <!-- Results (shown when query is not empty) -->
            <div id="searchResults" class="overflow-y-auto grow px-5 mt-4 space-y-2 hidden"
                data-empty-title="{{ __('home.search_no_matches') }}"
                data-empty-hint="{{ __('home.search_no_matches_hint') }}"></div>
            <script type="application/json" id="searchIndex">@json($searchIndex, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE)</script>

            <div class="px-5 pb-6 pt-3 safe-bottom">
                <a href="{{ route('learn-categories') }}" wire:navigate class="btn btn-ghost w-full">
                    <i class="ph ph-books"></i> {{ __('home.browse_full_library') }}
                </a>
            </div>
        </div>
    </div>

    {{-- NOTIFICATIONS SHEET dropped: five invented notifications and a fixed "3 new today".
         Re-port it from kidzio/home.html together with the bell in
         ⚡profile-header.blade.php (docs/tasks/T16-notifications.md). --}}
</main>

@push('scripts')
    <script defer src="{{ asset('assets/js/home.js') }}"></script>
@endpush
