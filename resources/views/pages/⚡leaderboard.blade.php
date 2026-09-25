<?php

use App\Data\LeaderboardEntry;
use App\Data\LeaderboardSnapshot;
use App\Repositories\UserRepository;
use App\Services\CountryService;
use App\Services\SearchService;
use App\Services\UserStatService;
use App\Support\CountryCatalog;
use Livewire\Attributes\Renderless;
use Livewire\Component;

new class extends Component
{
    public int $totalPlayers = 0;

    public ?int $yourRank = null;

    public int $yourXp = 0;

    public string $yourName = '';

    public int $yourLevel = 1;

    public int $yourStreak = 0;

    public string $yourAvatar = '🐻';

    public int $xpToNextRank = 0;

    public string $percentileLabel = '';

    /** @var list<array<string, mixed>> */
    public array $podium = [];

    /** @var list<array<string, mixed>> */
    public array $rows = [];

    public string $nicknameQuery = '';

    /** @var list<string> */
    public array $recentSearches = [];

    /** @var list<string> */
    public array $popularSearches = [];

    public string $viewerCountry = '';

    /** @var list<array{code: string, emoji: string, name: string, learners: int}> */
    public array $topCountries = [];

    public function title(): string
    {
        return __('ranking.page_title');
    }

    public function rendering(\Illuminate\View\View $view): void
    {
        $view->title($this->title());
    }

    public function mount(UserStatService $stats, UserRepository $users, SearchService $search, CountryService $countries): void
    {
        $user = $users->authenticated();
        $this->viewerCountry = is_string($user->country) ? $user->country : '';
        $this->topCountries = $countries->topByLearners();
        $this->recentSearches = $search->recent($user);
        $this->popularSearches = $search->popular();
        $this->fillRanking($stats->leaderboardSnapshot($user));
    }

    #[Renderless]
    public function searchPlayers(string $query, SearchService $search): array
    {
        return $search->players($query);
    }

    public function applyPlayerSearch(string $query, SearchService $search, UserRepository $users): void
    {
        $query = trim($query);
        $this->nicknameQuery = $query;
        $user = $users->authenticated();

        if ($query === '') {
            return;
        }

        $search->record($user, $query);
        $this->rows = [];

        foreach ($search->players($query) as $hit) {
            $this->rows[] = [
                'rank' => $hit['rank'] ?? 0,
                'userId' => $hit['userId'],
                'name' => $hit['name'],
                'nickname' => $hit['nickname'],
                'xp' => $hit['xp'],
                'level' => $hit['level'],
                'streak' => $hit['streak'],
                'isYou' => $hit['userId'] === $user->id,
                'avatar' => $hit['avatar'],
                'country' => $hit['country'] ?? '',
                'online' => (bool) ($hit['online'] ?? false),
            ];
        }
    }

    public function clearPlayerSearch(UserStatService $stats, UserRepository $users): void
    {
        $this->nicknameQuery = '';
        $user = $users->authenticated();
        $this->fillRanking($stats->leaderboardSnapshot($user));
    }

    private function fillRanking(LeaderboardSnapshot $snap): void
    {
        $this->totalPlayers = $snap->totalPlayers;
        $this->yourRank = $snap->yourRank;
        $this->yourXp = $snap->yourXp;
        $this->yourName = $snap->yourName;
        $this->yourLevel = $snap->yourLevel;
        $this->yourStreak = $snap->yourStreak;
        $this->yourAvatar = $snap->yourAvatar;
        $this->xpToNextRank = $snap->xpToNextRank;
        $this->percentileLabel = $snap->percentileLabel;
        $this->podium = array_map(
            fn (LeaderboardEntry $e) => [
                'rank' => $e->rank,
                'userId' => $e->userId,
                'name' => $e->name,
                'nickname' => '',
                'xp' => $e->xp,
                'level' => $e->level,
                'streak' => $e->streak,
                'isYou' => $e->isYou,
                'avatar' => $e->avatar,
                'country' => $e->country,
                'online' => $e->online,
            ],
            $snap->podium,
        );
        $this->rows = array_map(
            fn (LeaderboardEntry $e) => [
                'rank' => $e->rank,
                'userId' => $e->userId,
                'name' => $e->name,
                'nickname' => $e->nickname,
                'xp' => $e->xp,
                'level' => $e->level,
                'streak' => $e->streak,
                'isYou' => $e->isYou,
                'avatar' => $e->avatar,
                'country' => $e->country,
                'online' => $e->online,
            ],
            $snap->rows,
        );
    }

    public function podiumAt(int $rank): ?array
    {
        foreach ($this->podium as $entry) {
            if ((int) $entry['rank'] === $rank) {
                return $entry;
            }
        }

        return null;
    }
};
?>

@php
    $second = $this->podiumAt(2);
    $first = $this->podiumAt(1);
    $third = $this->podiumAt(3);
    $nextRank = $yourRank !== null ? max(1, $yourRank - 1) : null;
    $rankLabel = $yourRank !== null ? '#'.$yourRank : __('ranking.unranked');
@endphp

<main class="device-frame min-h-screen flex flex-col" data-viewer-country="{{ $viewerCountry }}">

    <header class="appbar safe-top">
        <div class="grow">
            <p class="text-xs text-muted">{{ __('ranking.compete_worldwide') }}</p>
            <h1 class="h-display text-2xl leading-tight">{{ __('ranking.ranking') }}</h1>
        </div>
        <button id="searchIconBtn" type="button" class="icon-btn" aria-label="{{ __('ranking.search') }}"><i
                class="ph ph-magnifying-glass text-xl"></i></button>
        <a href="{{ route('league') }}" wire:navigate class="icon-btn" aria-label="{{ __('ranking.league') }}"><i
                class="ph-fill ph-trophy text-xl"></i></a>
        <button type="button" class="icon-btn" data-theme-toggle aria-label="Toggle theme"><i
                class="ph ph-moon text-xl"></i></button>
    </header>

    <section class="px-5">
        <div data-swiper-rail-tabs class="swiper rail-swiper" role="tablist" aria-label="Ranking views">
            <div class="swiper-wrapper">
                <a href="{{ route('leaderboard') }}" wire:navigate class="swiper-slide chip chip-primary" role="tab"
                    aria-selected="true">🌍 {{ __('ranking.global') }}</a>
                <a href="{{ route('ranking-weekly') }}" wire:navigate class="swiper-slide chip"
                    role="tab">📅 {{ __('ranking.weekly') }}</a>
                <a href="{{ route('ranking-friends') }}" wire:navigate class="swiper-slide chip"
                    role="tab">👫 {{ __('ranking.friends') }}</a>
                <a href="{{ route('league') }}" wire:navigate class="swiper-slide chip"
                    role="tab">🏆 {{ __('ranking.league') }}</a>
            </div>
        </div>
    </section>

    <section class="px-5 mt-2">
        <div class="k-card-lg hero-global">
            <div class="relative flex items-center gap-3">
                <div class="text-5xl">🌍</div>
                <div class="grow">
                    <span class="chip bg-white/20 border-0 text-white">
                        <i class="ph-fill ph-globe-hemisphere-west"></i> {{ __('ranking.global') }}
                    </span>
                    <p class="h-display text-2xl mt-1 leading-tight">{{ __('ranking.all_time') }}</p>
                    <p class="text-xs text-white/90">
                        {{ __('ranking.competing_against', ['count' => number_format($totalPlayers)]) }}</p>
                </div>
            </div>

            <div class="relative mt-4 flex gap-2">
                <div class="mini-stat">
                    <p class="mini-v">{{ $rankLabel }}</p>
                    <p class="mini-l">{{ __('ranking.your_rank') }}</p>
                </div>
                <div class="mini-stat">
                    <p class="mini-v">{{ $percentileLabel }}</p>
                    <p class="mini-l">{{ __('ranking.percentile') }}</p>
                </div>
                <div class="mini-stat">
                    <p class="mini-v">{{ $nextRank !== null ? number_format($xpToNextRank) : __('ranking.unranked') }}</p>
                    <p class="mini-l">{{ $nextRank !== null ? __('ranking.to_rank', ['rank' => $nextRank]) : __('ranking.to_rank_hidden') }}</p>
                </div>
            </div>

            <div class="relative mt-4 flex flex-wrap items-center gap-2">
                <a href="{{ route('game-multiple-choice') }}" wire:navigate class="cta-soft">
                    {{ __('ranking.earn_xp') }} <i class="ph-fill ph-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>

    <section class="px-5 mt-4">
        <div class="section-head">
            <h2 class="h-display text-lg">{{ __('ranking.top_3_worldwide') }}</h2>
            <span class="link cursor-default">{{ __('ranking.all_time_label') }}</span>
        </div>
        <div class="podium-card">
            <div class="relative grid grid-cols-3 items-end gap-3">
                <div class="podium-slot second">
                    <div class="podium-avatar">{{ is_array($second) ? $second['avatar'] : '—' }}</div>
                    <p class="podium-name">{{ is_array($second) ? $second['name'] : '—' }}</p>
                    <p class="podium-xp">{{ is_array($second) ? number_format((int) $second['xp']).' XP' : '—' }}</p>
                    <div class="podium-base">🥈</div>
                </div>
                <div class="podium-slot first">
                    <span class="podium-crown" aria-hidden="true">👑</span>
                    <div class="podium-avatar">{{ is_array($first) ? $first['avatar'] : '—' }}</div>
                    <p class="podium-name">{{ is_array($first) ? $first['name'] : '—' }}</p>
                    <p class="podium-xp">{{ is_array($first) ? number_format((int) $first['xp']).' XP' : '—' }}</p>
                    <div class="podium-base">🥇</div>
                </div>
                <div class="podium-slot third">
                    <div class="podium-avatar">{{ is_array($third) ? $third['avatar'] : '—' }}</div>
                    <p class="podium-name">{{ is_array($third) ? $third['name'] : '—' }}</p>
                    <p class="podium-xp">{{ is_array($third) ? number_format((int) $third['xp']).' XP' : '—' }}</p>
                    <div class="podium-base">🥉</div>
                </div>
            </div>
        </div>
    </section>

    <section class="px-5 mt-4">
        <div class="you-strip">
            <div class="you-rank">{{ $rankLabel }}</div>
            <div class="grow min-w-0">
                <p class="font-extrabold text-sm text-ink">
                    {{ __('ranking.you', ['name' => $yourName]) }} · {{ number_format($yourXp) }} XP</p>
                <p class="text-[11px] text-muted">
                    @if ($nextRank !== null)
                        {{ __('ranking.you_meta', ['level' => $yourLevel, 'streak' => $yourStreak, 'xp' => number_format($xpToNextRank), 'rank' => $nextRank]) }}
                    @else
                        {{ __('ranking.you_hidden_meta', ['level' => $yourLevel, 'streak' => $yourStreak]) }}
                    @endif
                </p>
            </div>
            <a href="{{ route('game-multiple-choice') }}" wire:navigate
                class="btn btn-primary h-9 min-h-0 px-3 text-xs shrink-0">{{ __('ranking.climb') }}</a>
        </div>
    </section>

    <section class="px-5 mt-4">
        <div data-swiper-rail-tabs class="swiper rail-swiper" role="tablist" aria-label="Leaderboard filter">
            <div class="swiper-wrapper">
                <button type="button" class="swiper-slide chip chip-primary" data-filter="all"
                    aria-selected="true">🌍 {{ __('ranking.worldwide') }}</button>
                <button type="button" class="swiper-slide chip" data-filter="country"
                    aria-selected="false">🇺🇸 {{ __('ranking.country') }}</button>
                <button type="button" class="swiper-slide chip" data-filter="streak"
                    aria-selected="false">🔥 {{ __('ranking.on_streak') }}</button>
                <button type="button" class="swiper-slide chip" data-filter="online"
                    aria-selected="false">🟢 {{ __('ranking.online_now') }}</button>
            </div>
        </div>
    </section>

    @if ($nicknameQuery !== '')
        <section id="queryStrip" class="px-5 mt-3">
            <div class="k-card p-2 flex items-center gap-2">
                <span class="text-xs text-muted shrink-0">{{ __('ranking.searching') }}</span>
                <span class="chip chip-primary grow truncate">{{ $nicknameQuery }}</span>
                <button type="button" class="icon-btn shrink-0" wire:click="clearPlayerSearch"
                    aria-label="{{ __('ranking.clear_search') }}"><i class="ph ph-x"></i></button>
            </div>
        </section>
    @endif

    @if ($nicknameQuery !== '' && $rows === [])
        <section class="px-5 mt-4">
            <div class="k-card text-center p-6">
                <div class="size-16 mx-auto rounded-2xl tile-sky grid place-items-center text-3xl">🔍</div>
                <p class="h-display text-lg mt-3 text-ink">{{ __('ranking.no_learners') }}</p>
                <p class="text-xs text-muted mt-1">{{ __('ranking.no_learners_hint') }}</p>
            </div>
        </section>
    @endif

    <section class="px-5 mt-4">
        <div class="section-head">
            <h2 class="h-display text-lg">{{ __('ranking.full_ranking') }}</h2>
            <span class="link cursor-default">{{ __('ranking.by_total_xp') }}</span>
        </div>

        <div class="space-y-2">
            @foreach ($rows as $row)
                @php
                    $medal = match ((int) $row['rank']) {
                        1 => 'medal-gold',
                        2 => 'medal-silver',
                        3 => 'medal-bronze',
                        default => '',
                    };
                @endphp
                <div class="rank-row {{ $row['isYou'] ? 'you' : '' }}" data-row
                    data-name="{{ ($row['nickname'] ?? '') !== '' ? $row['nickname'] : $row['name'] }}"
                    data-nickname="{{ $row['nickname'] ?? '' }}"
                    data-streak="{{ $row['streak'] > 0 ? '1' : '0' }}"
                    data-online="{{ ! empty($row['online']) ? '1' : '0' }}"
                    data-country="{{ CountryCatalog::keywords($row['country'] ?? '') }}"
                    @if ($row['isYou']) data-me @endif>
                    <span class="rank-num {{ $medal }}">{{ $row['rank'] }}</span>
                    <div class="rank-av tile-sun">{{ $row['avatar'] }}@if (! empty($row['online']))<span class="live"></span>@endif</div>
                    <div class="grow min-w-0">
                        <p class="font-extrabold text-sm text-ink">
                            {{ $row['isYou'] ? __('ranking.you', ['name' => $row['name']]) : $row['name'] }}</p>
                        <div class="rank-meta">
                            <span>{{ __('ranking.lv', ['level' => $row['level']]) }}</span>
                            @if ($row['streak'] > 0)
                                ·<span class="text-sun-ink">{{ __('ranking.streak_days', ['days' => $row['streak']]) }}</span>
                            @endif
                            @if (($row['country'] ?? '') !== '')
                                ·<span>{{ CountryCatalog::emoji($row['country']) }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="chip {{ $row['isYou'] ? 'chip-primary' : ($row['rank'] === 1 ? 'chip-sun' : '') }}">
                            {{ number_format($row['xp']) }}
                        </span>
                        <p class="text-[10px] text-muted mt-1">{{ __('ranking.total_xp') }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    @if ($topCountries !== [])
        <section class="px-5 mt-5">
            <div class="section-head">
                <h2 class="h-display text-lg">{{ __('ranking.top_countries') }}</h2>
                <span class="link cursor-default">{{ __('ranking.by_learners') }}</span>
            </div>
            <div class="k-card p-0 overflow-hidden">
                @foreach ($topCountries as $index => $country)
                    <a href="{{ route('country') }}" wire:navigate
                        class="flex items-center gap-3 p-3 {{ $index > 0 ? 'border-t border-token' : '' }}">
                        <span class="w-7 text-center font-extrabold text-ink">{{ $index + 1 }}</span>
                        <span class="text-2xl">{{ $country['emoji'] }}</span>
                        <p class="font-extrabold text-sm grow text-ink">{{ $country['name'] }}</p>
                        <span class="chip {{ $index === 0 ? 'chip-primary' : '' }}">{{ number_format($country['learners']) }}</span>
                        <i class="ph ph-caret-right text-muted"></i>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if ($xpToNextRank > 0)
        <section class="px-5 mt-5">
            <a href="{{ route('game-multiple-choice') }}" wire:navigate
                class="k-card-lg card-hero-success relative overflow-hidden block">
                <span class="watermark-emoji" aria-hidden="true">🚀</span>
                <div class="relative flex items-center gap-3">
                    <div class="text-4xl">🚀</div>
                    <div class="grow">
                        <p class="text-xs uppercase font-extrabold tracking-wider opacity-90">
                            {{ __('ranking.climb_the_rank') }}</p>
                        <p class="h-display text-lg leading-tight">
                            {{ __('ranking.just_xp_to_reach', ['xp' => number_format($xpToNextRank), 'rank' => $nextRank]) }}
                        </p>
                    </div>
                    <span class="cta-soft shrink-0">{{ __('ranking.play') }}</span>
                </div>
            </a>
        </section>
    @endif

    <div class="mb-5"></div>

    <div id="searchOverlay" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true"
        aria-labelledby="searchTitle">
        <button type="button" id="searchBackdrop"
            class="absolute inset-0 size-full bg-black/50 backdrop-blur-sm opacity-0 transition-opacity duration-300"
            aria-label="{{ __('ranking.close') }}"></button>
        <div id="searchPanel"
            class="absolute inset-x-0 top-0 bottom-0 mx-auto max-w-[430px] bg-surface translate-y-full transition-transform duration-300 ease-out flex flex-col">
            <header class="appbar safe-top">
                <button type="button" id="searchClose" class="icon-btn" aria-label="{{ __('ranking.close') }}"><i
                        class="ph ph-caret-left text-xl"></i></button>
                <div class="grow">
                    <p class="text-xs text-muted">{{ __('ranking.find_learner') }}</p>
                    <h2 id="searchTitle" class="h-display text-lg leading-tight">{{ __('ranking.search_ranking') }}</h2>
                </div>
            </header>

            <section class="px-5">
                <div class="input-wrap">
                    <i class="ph ph-magnifying-glass i-left"></i>
                    <input id="rankSearchInput" class="input has-left" placeholder="{{ __('ranking.search_placeholder') }}"
                        aria-label="{{ __('ranking.search') }}" autocomplete="off" />
                    <button id="clearBtn" type="button" class="i-right hidden"
                        aria-label="{{ __('ranking.clear_search') }}"><i
                            class="ph ph-x-circle text-muted text-xl"></i></button>
                </div>
            </section>

            <div id="searchSuggest" class="overflow-y-auto grow">
                @if ($recentSearches !== [])
                    <section class="px-5 mt-4">
                        <p class="section-label">{{ __('ranking.recent') }}</p>
                        <div class="mt-2 flex flex-wrap gap-2" id="recentChips">
                            @foreach ($recentSearches as $chip)
                                <button type="button" class="chip" data-recent>{{ $chip }}</button>
                            @endforeach
                        </div>
                    </section>
                @endif
                @if ($popularSearches !== [])
                    <section class="px-5 mt-4">
                        <p class="section-label">{{ __('ranking.popular') }}</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($popularSearches as $chip)
                                <button type="button" class="chip" data-recent>{{ $chip }}</button>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            <div id="searchResults" class="overflow-y-auto grow px-5 mt-4 space-y-2 hidden"
                data-empty-title="{{ __('ranking.no_learners') }}"
                data-empty-hint="{{ __('ranking.no_learners_hint') }}"></div>

            <div class="px-5 pb-6 pt-3 safe-bottom">
                <button type="button" id="applySearchBtn" class="btn btn-primary w-full" disabled>
                    <i class="ph-fill ph-check"></i> {{ __('ranking.apply') }}
                </button>
            </div>
        </div>
    </div>

    <livewire:bottom-nav-bar />
</main>

@push('scripts')
    <script src="{{ asset('assets/js/search.js') }}"></script>
    <script src="{{ asset('assets/js/leaderboard.js') }}"></script>
@endpush
