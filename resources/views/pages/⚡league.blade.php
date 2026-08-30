<?php

use App\Data\CohortMemberRow;
use App\Enums\League;
use App\Repositories\UserRepository;
use App\Services\LeagueSeasonService;
use Livewire\Component;

new class extends Component
{
    public string $tier = 'bronze';

    public string $tierLabel = '';

    public int $yourRank = 1;

    public int $yourWeekXp = 0;

    public int $memberCount = 0;

    public int $promoteThresholdXp = 0;

    public int $xpToPromote = 0;

    public string $statusLabel = '';

    public string $endsInShort = '';

    /** @var list<array<string, mixed>> */
    public array $members = [];

    /** @var list<array<string, mixed>> */
    public array $journey = [];

    /** @var list<array{value: string, label: string, emoji: string, state: string}> */
    public array $tiers = [];

    public function title(): string
    {
        return __('ranking.league_page_title');
    }

    public function rendering(\Illuminate\View\View $view): void
    {
        $view->title($this->title());
    }

    public function mount(LeagueSeasonService $leagues, UserRepository $users): void
    {
        $snap = $leagues->weeklySnapshot($users->authenticated());

        $this->tier = $snap->tier->value;
        $this->tierLabel = $snap->tierLabel;
        $this->yourRank = $snap->yourRank;
        $this->yourWeekXp = $snap->yourWeekXp;
        $this->memberCount = $snap->memberCount;
        $this->promoteThresholdXp = $snap->promoteThresholdXp;
        $this->xpToPromote = $snap->xpToPromote;
        $this->statusLabel = $snap->statusLabel;
        $this->endsInShort = $snap->endsInShort;
        $this->journey = $snap->journey;
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

        $currentRank = $snap->tier->rank();
        $this->tiers = [];
        foreach (League::ordered() as $league) {
            $state = 'locked';
            if ($league->rank() < $currentRank) {
                $state = 'done';
            } elseif ($league->rank() === $currentRank) {
                $state = 'current';
            }
            $this->tiers[] = [
                'value' => $league->value,
                'label' => $league->label(),
                'emoji' => $league->emoji(),
                'state' => $state,
            ];
        }
    }
};
?>

@php
    $nextTier = \App\Enums\League::tryFrom($tier)?->promote();
    $progressPct = $promoteThresholdXp > 0
        ? (int) min(100, round(($yourWeekXp / max(1, $promoteThresholdXp)) * 100))
        : 100;
@endphp

<main class="device-frame min-h-screen flex flex-col">

    <header class="appbar safe-top">
        <a href="{{ route('leaderboard') }}" class="icon-btn" data-back aria-label="Back"><i
                class="ph ph-caret-left"></i></a>
        <div class="grow">
            <p class="text-xs text-muted">{{ __('ranking.season_label') }}</p>
            <h1 class="h-display text-lg leading-tight">{{ __('ranking.leagues') }}</h1>
        </div>
        <button type="button" class="icon-btn" aria-label="Help"><i class="ph ph-question text-xl"></i></button>
        <button type="button" class="icon-btn" data-theme-toggle aria-label="Toggle theme"><i
                class="ph ph-moon text-xl"></i></button>
    </header>

    <section class="px-5">
        <div data-swiper-rail-tabs class="swiper rail-swiper" role="tablist" aria-label="Ranking views">
            <div class="swiper-wrapper">
                <a href="{{ route('leaderboard') }}" wire:navigate class="swiper-slide chip"
                    role="tab">🌍 {{ __('ranking.global') }}</a>
                <a href="{{ route('ranking-weekly') }}" wire:navigate class="swiper-slide chip"
                    role="tab">📅 {{ __('ranking.weekly') }}</a>
                <a href="{{ route('ranking-friends') }}" wire:navigate class="swiper-slide chip"
                    role="tab">👫 {{ __('ranking.friends') }}</a>
                <a href="{{ route('league') }}" wire:navigate class="swiper-slide chip chip-primary" role="tab"
                    aria-selected="true">🏆 {{ __('ranking.league') }}</a>
            </div>
        </div>
    </section>

    <section class="px-5 mt-2">
        <div class="hero-league k-card-lg">
            <span class="relative league-trophy" aria-hidden="true">🏆</span>
            <p class="relative chip bg-white/20 border-0 text-white mt-4">
                <span class="live-dot"></span> {{ __('ranking.active_season') }}
            </p>
            <p class="relative h-display text-3xl mt-2 leading-tight">
                {{ __('ranking.league_name', ['name' => $tierLabel]) }}</p>
            <p class="relative text-sm text-white/90">
                {{ __('ranking.week_of_season', ['count' => $memberCount]) }}</p>

            <div class="relative mt-4 grid grid-cols-3 gap-2">
                <div class="mini-stat">
                    <p class="mini-v">#{{ $yourRank }}</p>
                    <p class="mini-l">{{ __('ranking.your_spot') }}</p>
                </div>
                <div class="mini-stat">
                    <p class="mini-v">{{ $statusLabel }}</p>
                    <p class="mini-l">{{ __('ranking.status') }}</p>
                </div>
                <div class="mini-stat">
                    <p class="mini-v">{{ $endsInShort }}</p>
                    <p class="mini-l">{{ __('ranking.ends_in') }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="px-5 mt-4">
        <div class="k-card p-4">
            <div class="flex items-center gap-3">
                <div class="size-11 rounded-2xl tile-mint grid place-items-center text-xl shrink-0">💎</div>
                <div class="grow">
                    <p class="font-extrabold text-sm text-ink">
                        {{ __('ranking.xp_to_promote', ['xp' => number_format($xpToPromote), 'tier' => $nextTier?->label() ?? $tierLabel]) }}
                    </p>
                    <p class="text-[11px] text-muted">{{ __('ranking.promote_hint') }}</p>
                </div>
                <span class="chip chip-primary">{{ $tierLabel }} → {{ $nextTier?->label() ?? $tierLabel }}</span>
            </div>
            <div class="progress mt-3"><span style="width: {{ $progressPct }}%"></span></div>
            <div class="flex items-center justify-between text-[10px] text-muted font-extrabold mt-2">
                <span>{{ __('ranking.you_xp', ['xp' => number_format($yourWeekXp)]) }}</span>
                <span>{{ __('ranking.promote_at', ['xp' => number_format($promoteThresholdXp)]) }}</span>
            </div>
        </div>
    </section>

    <section class="mt-5">
        <div class="section-head px-5">
            <h2 class="h-display text-lg">{{ __('ranking.tier_ladder') }}</h2>
            <span class="link cursor-default">{{ __('ranking.six_tiers') }}</span>
        </div>
        <div data-swiper-rail class="swiper rail-swiper">
            <div class="swiper-wrapper">
                @foreach ($tiers as $t)
                    <div
                        class="swiper-slide tier-card tier-{{ $t['value'] }} {{ $t['state'] === 'done' ? 'is-done' : '' }} {{ $t['state'] === 'current' ? 'is-current' : '' }}">
                        <div class="tier-ico">{{ $t['emoji'] }}</div>
                        <p class="tier-name">{{ $t['label'] }}</p>
                        <p class="tier-sub">
                            @if ($t['state'] === 'done')
                                {{ __('ranking.earned') }}
                            @elseif ($t['state'] === 'current')
                                {{ __('ranking.current') }}
                            @else
                                {{ __('ranking.locked') }}
                            @endif
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="px-5 mt-5">
        <div class="section-head">
            <h2 class="h-display text-lg">{{ __('ranking.this_week_list') }}</h2>
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

    @if (count($journey) > 0)
        <section class="px-5 mt-5">
            <div class="section-head">
                <h2 class="h-display text-lg">{{ __('ranking.season_journey') }}</h2>
            </div>
            <div class="space-y-2">
                @foreach ($journey as $item)
                    <div class="reward-row">
                        <div class="rr-ico tile-violet">#{{ $item['rank'] }}</div>
                        <div class="grow min-w-0">
                            <p class="font-extrabold text-sm text-ink">{{ $item['tier'] }} · {{ $item['weekLabel'] }}
                            </p>
                            <p class="text-[11px] text-muted">{{ $item['outcome'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <div class="mb-5"></div>

    <livewire:bottom-nav-bar />
</main>
