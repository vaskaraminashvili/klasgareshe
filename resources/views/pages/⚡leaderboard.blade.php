<?php

use App\Data\LeaderboardEntry;
use App\Repositories\UserRepository;
use App\Services\UserStatService;
use Livewire\Component;

new class extends Component
{
    public int $totalPlayers = 0;

    public int $yourRank = 1;

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

    public function title(): string
    {
        return __('ranking.page_title');
    }

    public function rendering(\Illuminate\View\View $view): void
    {
        $view->title($this->title());
    }

    public function mount(UserStatService $stats, UserRepository $users): void
    {
        $snap = $stats->leaderboardSnapshot($users->authenticated());

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
                'xp' => $e->xp,
                'level' => $e->level,
                'streak' => $e->streak,
                'isYou' => $e->isYou,
                'avatar' => $e->avatar,
            ],
            $snap->podium,
        );
        $this->rows = array_map(
            fn (LeaderboardEntry $e) => [
                'rank' => $e->rank,
                'userId' => $e->userId,
                'name' => $e->name,
                'xp' => $e->xp,
                'level' => $e->level,
                'streak' => $e->streak,
                'isYou' => $e->isYou,
                'avatar' => $e->avatar,
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
    $nextRank = max(1, $yourRank - 1);
@endphp

<main class="device-frame min-h-screen flex flex-col">

    <header class="appbar safe-top">
        <div class="grow">
            <p class="text-xs text-muted">{{ __('ranking.compete_worldwide') }}</p>
            <h1 class="h-display text-2xl leading-tight">{{ __('ranking.ranking') }}</h1>
        </div>
        <button id="searchIconBtn" type="button" class="icon-btn" aria-label="Search"><i
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
                <a href="#" class="swiper-slide chip" role="tab">👫 {{ __('ranking.friends') }}</a>
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
                    <p class="mini-v">#{{ $yourRank }}</p>
                    <p class="mini-l">{{ __('ranking.your_rank') }}</p>
                </div>
                <div class="mini-stat">
                    <p class="mini-v">{{ $percentileLabel }}</p>
                    <p class="mini-l">{{ __('ranking.percentile') }}</p>
                </div>
                <div class="mini-stat">
                    <p class="mini-v">{{ number_format($xpToNextRank) }}</p>
                    <p class="mini-l">{{ __('ranking.to_rank', ['rank' => $nextRank]) }}</p>
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
            <div class="you-rank">#{{ $yourRank }}</div>
            <div class="grow min-w-0">
                <p class="font-extrabold text-sm text-ink">
                    {{ __('ranking.you', ['name' => $yourName]) }} · {{ number_format($yourXp) }} XP</p>
                <p class="text-[11px] text-muted">
                    {{ __('ranking.you_meta', ['level' => $yourLevel, 'streak' => $yourStreak, 'xp' => number_format($xpToNextRank), 'rank' => $nextRank]) }}
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
                <div class="rank-row {{ $row['isYou'] ? 'you' : '' }}" @if ($row['isYou']) data-me @endif>
                    <span class="rank-num {{ $medal }}">{{ $row['rank'] }}</span>
                    <div class="rank-av tile-sun">{{ $row['avatar'] }}</div>
                    <div class="grow min-w-0">
                        <p class="font-extrabold text-sm text-ink">
                            {{ $row['isYou'] ? __('ranking.you', ['name' => $row['name']]) : $row['name'] }}</p>
                        <div class="rank-meta">
                            <span>{{ __('ranking.lv', ['level' => $row['level']]) }}</span>
                            @if ($row['streak'] > 0)
                                ·<span class="text-sun-ink">{{ __('ranking.streak_days', ['days' => $row['streak']]) }}</span>
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

    <livewire:bottom-nav-bar />
</main>

@push('scripts')
    <script src="{{ asset('assets/js/leaderboard.js') }}"></script>
@endpush
