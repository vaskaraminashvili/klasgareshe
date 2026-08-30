<?php

use App\Data\CohortMemberRow;
use App\Repositories\UserRepository;
use App\Services\LeagueSeasonService;
use Livewire\Component;

new class extends Component
{
    public string $tierLabel = '';

    public int $yourRank = 1;

    public int $yourWeekXp = 0;

    public int $memberCount = 0;

    public int $xpGapToNext = 0;

    public string $statusLabel = '';

    public string $endsInShort = '';

    public string $weekRangeLabel = '';

    public int $weekXpTotal = 0;

    public int $vsLastWeekPercent = 0;

    public int $bestDayXp = 0;

    public string $bestDayLabel = '';

    public string $chartJson = '[]';

    /** @var list<array<string, mixed>> */
    public array $members = [];

    public function title(): string
    {
        return __('ranking.weekly_page_title');
    }

    public function rendering(\Illuminate\View\View $view): void
    {
        $view->title($this->title());
    }

    public function mount(LeagueSeasonService $leagues, UserRepository $users): void
    {
        $snap = $leagues->weeklySnapshot($users->authenticated());

        $this->tierLabel = $snap->tierLabel;
        $this->yourRank = $snap->yourRank;
        $this->yourWeekXp = $snap->yourWeekXp;
        $this->memberCount = $snap->memberCount;
        $this->xpGapToNext = $snap->xpGapToNext;
        $this->statusLabel = $snap->statusLabel;
        $this->endsInShort = $snap->endsInShort;
        $this->weekRangeLabel = $snap->weekRangeLabel;
        $this->weekXpTotal = $snap->weekXpTotal;
        $this->vsLastWeekPercent = $snap->vsLastWeekPercent;
        $this->bestDayXp = $snap->bestDayXp;
        $this->bestDayLabel = $snap->bestDayLabel;
        $this->chartJson = $snap->chartJson;
        $this->members = array_map(
            fn (CohortMemberRow $row) => [
                'rank' => $row->rank,
                'name' => $row->name,
                'weekXp' => $row->weekXp,
                'level' => $row->level,
                'streak' => $row->streak,
                'isYou' => $row->isYou,
                'avatar' => $row->avatar,
                'zone' => $row->zone,
            ],
            $snap->members,
        );
    }
};
?>

@php
    $targetRank = min(3, max(1, $yourRank - 1));
@endphp

<main class="device-frame min-h-screen flex flex-col">

    <header class="appbar safe-top">
        <a href="{{ route('leaderboard') }}" class="icon-btn" data-back aria-label="Back"><i
                class="ph ph-caret-left"></i></a>
        <div class="grow">
            <p class="text-xs text-muted">{{ __('ranking.ranking_week_range', ['from' => '', 'to' => $weekRangeLabel]) }}
            </p>
            <h1 class="h-display text-lg leading-tight">{{ __('ranking.weekly_ranking') }}</h1>
        </div>
        <button type="button" class="icon-btn" aria-label="Share"><i class="ph ph-share-fat text-xl"></i></button>
        <button type="button" class="icon-btn" data-theme-toggle aria-label="Toggle theme"><i
                class="ph ph-moon text-xl"></i></button>
    </header>

    <section class="px-5">
        <div data-swiper-rail-tabs class="swiper rail-swiper" role="tablist" aria-label="Ranking views">
            <div class="swiper-wrapper">
                <a href="{{ route('leaderboard') }}" wire:navigate class="swiper-slide chip"
                    role="tab">🌍 {{ __('ranking.global') }}</a>
                <a href="{{ route('ranking-weekly') }}" wire:navigate class="swiper-slide chip chip-primary" role="tab"
                    aria-selected="true">📅 {{ __('ranking.weekly') }}</a>
                <a href="{{ route('ranking-friends') }}" wire:navigate class="swiper-slide chip"
                    role="tab">👫 {{ __('ranking.friends') }}</a>
                <a href="{{ route('league') }}" wire:navigate class="swiper-slide chip"
                    role="tab">🏆 {{ __('ranking.league') }}</a>
            </div>
        </div>
    </section>

    <section class="px-5 mt-2">
        <div class="k-card-lg hero-weekly">
            <div class="relative flex items-center gap-3">
                <div class="text-5xl">⚡</div>
                <div class="grow">
                    <span class="chip bg-white/20 border-0 text-white">
                        <i class="ph-fill ph-lightning"></i> {{ __('ranking.this_week_chip') }}
                    </span>
                    <p class="h-display text-2xl mt-1 leading-tight">+{{ number_format($weekXpTotal) }} XP</p>
                    <p class="text-xs text-white/90"><i class="ph-fill ph-trend-up"></i>
                        {{ $vsLastWeekPercent >= 0 ? '+' : '' }}{{ $vsLastWeekPercent }}%</p>
                </div>
            </div>

            <div class="relative mt-4 flex gap-2">
                <div class="mini-stat">
                    <p class="mini-v">#{{ $yourRank }}</p>
                    <p class="mini-l">{{ __('ranking.your_rank') }}</p>
                </div>
                <div class="mini-stat">
                    <p class="mini-v">{{ $statusLabel }}</p>
                    <p class="mini-l">{{ __('ranking.status') }}</p>
                </div>
                <div class="mini-stat">
                    <p class="mini-v">#{{ $targetRank }}</p>
                    <p class="mini-l">{{ __('ranking.target') }}</p>
                </div>
            </div>

            <div class="relative mt-4 flex flex-wrap items-center gap-2">
                <a href="{{ route('game-multiple-choice') }}" wire:navigate class="cta-soft">
                    {{ __('ranking.earn_more_xp') }} <i class="ph-fill ph-arrow-right"></i>
                </a>
                <span class="chip bg-white/20 border-0 text-white ml-auto">
                    {{ __('ranking.cohort_players', ['count' => $memberCount]) }}
                </span>
            </div>
        </div>
    </section>

    <section class="px-5 mt-4">
        <div class="deadline-box">
            <div class="deadline-ico">⏳</div>
            <div class="grow">
                <p class="font-extrabold text-sm text-ink">
                    {{ __('ranking.week_ends_in', ['time' => $endsInShort]) }}</p>
                <p class="text-[11px] text-muted">{{ __('ranking.top_3_promote') }}</p>
            </div>
            <a href="{{ route('game-multiple-choice') }}" wire:navigate
                class="btn btn-primary h-9 min-h-0 px-3 text-xs shrink-0">{{ __('ranking.mission') }}</a>
        </div>
    </section>

    <section class="px-5 mt-4">
        <div class="section-head">
            <h2 class="h-display text-lg">{{ __('ranking.xp_this_week') }}</h2>
            <span class="chip chip-mint"><i class="ph-fill ph-trend-up"></i>
                {{ $vsLastWeekPercent >= 0 ? '+' : '' }}{{ $vsLastWeekPercent }}%</span>
        </div>
        <div class="k-card text-ink">
            <div id="line" data-points='@json(json_decode($chartJson, true))'></div>
            <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                <div class="p-2 rounded-xl bg-base">
                    <p class="text-[10px] font-extrabold uppercase tracking-wide text-muted">
                        {{ __('ranking.best_day') }}</p>
                    <p class="h-display text-base">{{ $bestDayLabel }} +{{ number_format($bestDayXp) }}</p>
                </div>
                <div class="p-2 rounded-xl bg-base">
                    <p class="text-[10px] font-extrabold uppercase tracking-wide text-muted">
                        {{ __('ranking.week_xp') }}</p>
                    <p class="h-display text-base">{{ number_format($yourWeekXp) }}</p>
                </div>
                <div class="p-2 rounded-xl bg-base">
                    <p class="text-[10px] font-extrabold uppercase tracking-wide text-muted">
                        {{ __('ranking.gap_to_next', ['xp' => '']) }}</p>
                    <p class="h-display text-base">{{ number_format($xpGapToNext) }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="px-5 mt-4">
        <div class="section-head">
            <h2 class="h-display text-lg">{{ $tierLabel }}</h2>
            <span class="link cursor-default">{{ __('ranking.cohort_players', ['count' => $memberCount]) }}</span>
        </div>
        <div class="space-y-2">
            @foreach ($members as $row)
                @php
                    $zoneClass = match ($row['zone']) {
                        'promote' => 'zone-promote',
                        'relegate' => 'zone-relegate',
                        default => '',
                    };
                @endphp
                <div class="rank-row {{ $row['isYou'] ? 'you' : '' }} {{ $zoneClass }}">
                    <span class="rank-num">{{ $row['rank'] }}</span>
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
                        <span class="chip {{ $row['isYou'] ? 'chip-primary' : '' }}">{{ number_format($row['weekXp']) }}</span>
                        <p class="text-[10px] text-muted mt-1">{{ __('ranking.week_xp') }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <div class="mb-5"></div>

    <livewire:bottom-nav-bar />
</main>

@push('scripts')
    <script src="{{ asset('assets/js/charts.js') }}"></script>
    <script src="{{ asset('assets/js/ranking-weekly.js') }}"></script>
@endpush
