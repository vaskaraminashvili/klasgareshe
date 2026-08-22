<?php

use App\Repositories\UserRepository;
use App\Services\BadgeService;
use Illuminate\View\View;
use Livewire\Component;

new class extends Component
{
    public int $earnedCount = 0;

    public int $totalCount = 0;

    public int $inProgressCount = 0;

    public int $lockedCount = 0;

    public int $percentComplete = 0;

    public int $remaining = 0;

    public int $commonCount = 0;

    public int $rareCount = 0;

    public int $epicCount = 0;

    public int $legendCount = 0;

    public int $holderPercentile = 0;

    public string $kidName = '';

    /**
     * @var array{slug: string, name: string, emoji: string, medalClass: string, unseen: bool, href: string}|null
     */
    public ?array $featured = null;

    /**
     * @var list<array<string, mixed>>
     */
    public array $earned = [];

    /**
     * @var list<array<string, mixed>>
     */
    public array $inProgress = [];

    /**
     * @var list<array<string, mixed>>
     */
    public array $locked = [];

    public function title(): string
    {
        return __('badges.page_title');
    }

    public function rendering(View $view): void
    {
        $view->title($this->title());
    }

    public function mount(BadgeService $badges, UserRepository $users): void
    {
        $user = $users->authenticated();
        $snap = $badges->collection($user);

        $this->kidName = $user->name;
        $this->earnedCount = $snap->earnedCount;
        $this->totalCount = $snap->totalCount;
        $this->inProgressCount = $snap->inProgressCount;
        $this->lockedCount = $snap->lockedCount;
        $this->percentComplete = $snap->percentComplete;
        $this->remaining = $snap->remaining;
        $this->commonCount = $snap->commonCount;
        $this->rareCount = $snap->rareCount;
        $this->epicCount = $snap->epicCount;
        $this->legendCount = $snap->legendCount;
        $this->holderPercentile = $snap->holderPercentile;
        $this->featured = $snap->featured?->toArray();
        $this->earned = array_map(fn ($card) => $card->toArray(), $snap->earned);
        $this->inProgress = array_map(fn ($card) => $card->toArray(), $snap->inProgress);
        $this->locked = array_map(fn ($card) => $card->toArray(), $snap->locked);
    }
};
?>

<main class="device-frame min-h-screen flex flex-col">

    <!-- =============== APPBAR =============== -->
    <header class="appbar safe-top">
        <a href="{{ route('home') }}" class="icon-btn" data-back aria-label="{{ __('badges.your_collection') }}"><i
                class="ph ph-caret-left"></i></a>
        <div class="grow">
            <p class="text-xs text-muted">{{ __('badges.your_collection') }}</p>
            <h1 class="h-display text-lg leading-tight">{{ __('badges.badges') }}</h1>
        </div>
        <button class="icon-btn" type="button" aria-label="{{ __('badges.share') }}" id="shareBtn"
            data-share-text="{{ __('badges.share_text', ['name' => $kidName, 'count' => $earnedCount]) }}"
            data-share-copied="{{ __('badges.link_copied') }}"><i class="ph ph-share-fat text-xl"></i></button>
        <button class="icon-btn" data-theme-toggle aria-label="{{ __('badges.toggle_theme') }}"><i
                class="ph ph-moon text-xl"></i></button>
    </header>

    <!-- =============== HERO COLLECTION =============== -->
    <section class="px-5">
        <div class="k-card-lg hero-badges text-center">
            <div class="relative inline-flex items-center justify-center">
                <div class="badge-medal" aria-hidden="true">🏆</div>
            </div>
            <p class="relative chip bg-white/20 border-0 text-white mt-3">
                <i class="ph-fill ph-sparkle"></i> {{ __('badges.collection_chip') }}
            </p>
            <p class="relative h-display text-3xl mt-2 leading-none">{{ $earnedCount }} <span
                    class="text-xl opacity-80">/ {{ $totalCount }}</span></p>
            <p class="relative text-xs text-white/90">{{ __('badges.earned_of', ['percent' => $holderPercentile]) }}</p>

            <div class="relative mt-4">
                <div class="coll-bar"><span style="width: {{ $percentComplete }}%"></span></div>
                <div class="flex items-center justify-between text-[11px] mt-2 text-white/85 font-extrabold">
                    <span>{{ __('badges.percent_complete', ['percent' => $percentComplete]) }}</span>
                    <span>{{ __('badges.to_go', ['count' => $remaining]) }}</span>
                </div>
            </div>

            <div class="relative mt-4 grid grid-cols-4 gap-2">
                <div class="hero-metric">
                    <p class="hm-v">{{ $rareCount }}</p>
                    <p class="hm-l">{{ __('badges.hero.rare') }}</p>
                </div>
                <div class="hero-metric">
                    <p class="hm-v">{{ $commonCount }}</p>
                    <p class="hm-l">{{ __('badges.hero.common') }}</p>
                </div>
                <div class="hero-metric">
                    <p class="hm-v">{{ $epicCount }}</p>
                    <p class="hm-l">{{ __('badges.hero.epic') }}</p>
                </div>
                <div class="hero-metric">
                    <p class="hm-v">{{ $legendCount }}</p>
                    <p class="hm-l">{{ __('badges.hero.legend') }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- =============== FEATURED / LATEST =============== -->
    @if ($featured)
        <section class="px-5 mt-4">
            <div class="badge-feature">
                <div class="badge-medal {{ $featured['medalClass'] }} shrink-0">{{ $featured['emoji'] }}</div>
                <div class="grow">
                    <p class="text-[10px] font-extrabold uppercase tracking-wider text-muted">{{ __('badges.just_unlocked') }}
                    </p>
                    <p class="font-extrabold text-base text-ink">{{ $featured['name'] }}</p>
                    <p class="text-[11px] text-muted">{{ __('badges.today_bonus', ['xp' => 100]) }}</p>
                </div>
                <a href="{{ $featured['unseen'] ? $featured['href'] : route('badges') }}"
                    @if ($featured['unseen']) wire:navigate @endif
                    class="btn btn-primary h-9 min-h-0 px-3 text-xs shrink-0">{{ __('badges.view') }}</a>
            </div>
        </section>
    @endif

    <!-- =============== FILTER CHIPS =============== -->
    <section class="px-5 mt-4">
        <div data-swiper-rail-tabs class="swiper rail-swiper" role="tablist" aria-label="{{ __('badges.badges') }}">
            <div class="swiper-wrapper">
                <button type="button" class="swiper-slide chip chip-primary" data-status="all"
                    aria-selected="true">✨ {{ __('badges.all', ['count' => $totalCount]) }}</button>
                <button type="button" class="swiper-slide chip"
                    data-status="got">🏆 {{ __('badges.earned_chip', ['count' => $earnedCount]) }}</button>
                <button type="button" class="swiper-slide chip"
                    data-status="inprog">⏳ {{ __('badges.in_progress_chip', ['count' => $inProgressCount]) }}</button>
                <button type="button" class="swiper-slide chip"
                    data-status="lock">🔒 {{ __('badges.locked_chip', ['count' => $lockedCount]) }}</button>
                <button type="button" class="swiper-slide chip" data-status="rare">💎 {{ __('badges.rare_chip') }}</button>
            </div>
        </div>
    </section>

    <!-- =============== CATEGORY FILTERS =============== -->
    <section class="px-5 mt-3">
        <div data-swiper-rail-tabs class="swiper rail-swiper" role="tablist"
            aria-label="{{ __('badges.all_categories') }}">
            <div class="swiper-wrapper">
                <button type="button" class="swiper-slide chip chip-primary" data-cat="all"
                    aria-selected="true">🌟 {{ __('badges.all_categories') }}</button>
                <button type="button" class="swiper-slide chip"
                    data-cat="milestone">🏁 {{ __('badges.categories.milestone') }}</button>
                <button type="button" class="swiper-slide chip" data-cat="math">➗ {{ __('badges.categories.math') }}
                </button>
                <button type="button" class="swiper-slide chip"
                    data-cat="alphabet">🔤 {{ __('badges.categories.alphabet') }}</button>
                <button type="button" class="swiper-slide chip"
                    data-cat="animals">🦁 {{ __('badges.categories.animals') }}</button>
                <button type="button" class="swiper-slide chip" data-cat="streak">🔥 {{ __('badges.categories.streak') }}
                </button>
                <button type="button" class="swiper-slide chip" data-cat="league">🏆 {{ __('badges.categories.league') }}
                </button>
            </div>
        </div>
    </section>

    <!-- =============== NO RESULTS =============== -->
    <section id="noBadges" class="px-5 mt-6 hidden">
        <div class="k-card text-center p-6">
            <div class="size-16 mx-auto rounded-2xl tile-sky grid place-items-center text-3xl">🔍</div>
            <p class="h-display text-lg mt-3 text-ink">{{ __('badges.nothing_here') }}</p>
            <p class="text-xs text-muted mt-1">{{ __('badges.try_filter') }}</p>
        </div>
    </section>

    <!-- =============== EARNED GRID =============== -->
    @if ($earned !== [])
        <section class="px-5 mt-4" data-badge-section="got">
            <div class="section-head">
                <h2 class="h-display text-lg">🏆 {{ __('badges.earned') }}</h2>
                <span class="link cursor-default" data-count>{{ $earnedCount }} {{ __('badges.earned') }}</span>
            </div>

            <div class="grid grid-cols-3 gap-3">
                @foreach ($earned as $badge)
                    <a href="{{ $badge['href'] }}" @if ($badge['unseen']) wire:navigate @endif class="badge-card"
                        data-badge-item data-status="got" data-cat="{{ $badge['category'] }}"
                        data-rarity="{{ $badge['rarity'] }}" data-name="{{ $badge['name'] }}">
                        @if ($badge['unseen'])
                            <span class="bc-flag">{{ __('badges.new') }}</span>
                        @endif
                        <span
                            class="badge-medal {{ $badge['medalClass'] }} bc-medal">{{ $badge['emoji'] }}</span>
                        <p class="bc-name">{{ $badge['name'] }}</p>
                        <span class="{{ $badge['rarityClass'] }}">{{ $badge['rarityLabel'] }}</span>
                        <p class="bc-meta">{{ $badge['meta'] }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <!-- =============== IN PROGRESS =============== -->
    @if ($inProgress !== [])
        <section class="px-5 mt-6" data-badge-section="inprog">
            <div class="section-head">
                <h2 class="h-display text-lg">⏳ {{ __('badges.in_progress') }}</h2>
                <span class="link cursor-default" data-count>{{ $inProgressCount }} {{ __('badges.in_progress') }}</span>
            </div>
            <div class="space-y-2">
                @foreach ($inProgress as $badge)
                    <button type="button" class="setting-row w-full text-left" data-badge-item data-status="inprog"
                        data-cat="{{ $badge['category'] }}" data-rarity="{{ $badge['rarity'] }}"
                        data-name="{{ $badge['name'] }}" data-hint="{{ $badge['hint'] }}">
                        <div class="badge-locked shrink-0 p-0 border-0 bg-transparent">
                            <div class="bl-slot"><span>{{ $badge['emoji'] }}</span></div>
                        </div>
                        <div class="grow min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="font-extrabold text-sm text-ink">{{ $badge['name'] }}</p>
                                <span class="{{ $badge['rarityClass'] }}">{{ $badge['rarityLabel'] }}</span>
                            </div>
                            <p class="text-[11px] text-muted">{{ $badge['progressLabel'] }}</p>
                            <div class="progress mt-1"><span style="width: {{ $badge['percent'] }}%"></span></div>
                        </div>
                        <span class="{{ $badge['chipClass'] }}">{{ $badge['percent'] }}%</span>
                    </button>
                @endforeach
            </div>
        </section>
    @endif

    <!-- =============== LOCKED =============== -->
    @if ($locked !== [])
        <section class="px-5 mt-6 mb-5" data-badge-section="lock">
            <div class="section-head">
                <h2 class="h-display text-lg">🔒 {{ __('badges.locked') }}</h2>
                <span class="link cursor-default" data-count>{{ $lockedCount }} {{ __('badges.locked') }}</span>
            </div>

            <div class="grid grid-cols-3 gap-3">
                @foreach ($locked as $badge)
                    <button type="button" class="badge-locked" data-badge-item data-status="lock"
                        data-cat="{{ $badge['category'] }}" data-rarity="{{ $badge['rarity'] }}"
                        data-name="{{ $badge['name'] }}" data-hint="{{ $badge['hint'] }}">
                        <div class="bl-slot">
                            @if ($badge['isSecret'])
                                <i class="ph-fill ph-question"></i>
                            @else
                                <i class="ph-fill ph-lock"></i>
                            @endif
                        </div>
                        <p class="bc-name text-ink font-extrabold text-xs mt-3">{{ $badge['name'] }}</p>
                        <span class="{{ $badge['rarityClass'] }}">{{ $badge['rarityLabel'] }}</span>
                        <p class="bc-meta">{{ $badge['meta'] }}</p>
                    </button>
                @endforeach
            </div>
        </section>
    @endif

    <!-- =============== PARENT TIP =============== -->
    <section class="px-5 mb-5 mt-4">
        <div class="tip-card rounded-2xl p-4 flex items-start gap-3">
            <div class="mascot shrink-0 size-11 text-xl">🦉</div>
            <div class="grow">
                <p class="font-extrabold text-sm text-ink">{{ __('badges.did_you_know') }}</p>
                <p class="text-xs text-muted">{{ __('badges.tip') }}</p>
            </div>
            <a href="#" class="chip chip-primary">{{ __('badges.rewards') }}</a>
        </div>
    </section>

    <livewire:bottom-nav-bar />
</main>

@push('scripts')
    <script defer src="{{ asset('assets/js/badges.js') }}"></script>
@endpush
