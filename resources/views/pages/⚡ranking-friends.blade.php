<?php

use App\Data\LeaderboardEntry;
use App\Repositories\UserRepository;
use App\Services\FriendshipService;
use Livewire\Component;

new class extends Component
{
    public string $nickname = '';

    public string $filter = '';

    public string $flashMessage = '';

    public int $friendCount = 0;

    public int $onlineCount = 0;

    public int $beatingCount = 0;

    public int $yourRank = 1;

    public int $yourXp = 0;

    public string $yourName = '';

    public int $yourLevel = 1;

    public int $yourStreak = 0;

    public string $yourAvatar = '🐻';

    public int $xpBehindLeader = 0;

    public string $xpBehindName = '';

    public int $xpAheadNext = 0;

    public string $xpAheadName = '';

    /** @var list<array<string, mixed>> */
    public array $podium = [];

    /** @var list<array<string, mixed>> */
    public array $rows = [];

    public function title(): string
    {
        return __('friends.page_title');
    }

    public function rendering(\Illuminate\View\View $view): void
    {
        $view->title($this->title());
    }

    public function mount(FriendshipService $friends, UserRepository $users): void
    {
        $this->loadSnapshot($friends, $users);
    }

    public function requestFriend(FriendshipService $friends, UserRepository $users): void
    {
        $this->flashMessage = '';
        $this->validate([
            'nickname' => ['required', 'string', 'max:40'],
        ], [], [
            'nickname' => __('friends.nickname_label'),
        ]);

        $friends->request($users->authenticated(), $this->nickname);

        $this->nickname = '';
        $this->flashMessage = (string) __('friends.added');
        $this->loadSnapshot($friends, $users);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function filteredRows(): array
    {
        $q = mb_strtolower(trim($this->filter));

        if ($q === '') {
            return $this->rows;
        }

        return array_values(array_filter(
            $this->rows,
            static fn (array $row): bool => str_contains(mb_strtolower((string) $row['name']), $q),
        ));
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

    private function loadSnapshot(FriendshipService $friends, UserRepository $users): void
    {
        $snap = $friends->friendsLeaderboard($users->authenticated());

        $this->friendCount = $snap->friendCount;
        $this->onlineCount = $snap->onlineCount;
        $this->beatingCount = $snap->beatingCount;
        $this->yourRank = $snap->yourRank;
        $this->yourXp = $snap->yourXp;
        $this->yourName = $snap->yourName;
        $this->yourLevel = $snap->yourLevel;
        $this->yourStreak = $snap->yourStreak;
        $this->yourAvatar = $snap->yourAvatar;
        $this->xpBehindLeader = $snap->xpBehindLeader;
        $this->xpBehindName = $snap->xpBehindName;
        $this->xpAheadNext = $snap->xpAheadNext;
        $this->xpAheadName = $snap->xpAheadName;
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
};
?>

@php
    $second = $this->podiumAt(2);
    $first = $this->podiumAt(1);
    $third = $this->podiumAt(3);
    $filtered = $this->filteredRows();
    $tiles = ['tile-sun', 'tile-mint', 'tile-coral', 'tile-sky', 'tile-pink', 'tile-violet'];
@endphp

<main class="device-frame min-h-screen flex flex-col">

    <header class="appbar safe-top">
        <a href="{{ route('leaderboard') }}" class="icon-btn" data-back aria-label="Back"><i
                class="ph ph-caret-left"></i></a>
        <div class="grow">
            <p class="text-xs text-muted">{{ __('friends.ranking_social') }}</p>
            <h1 class="h-display text-lg leading-tight">{{ __('friends.my_friends') }}</h1>
        </div>
        <button type="button" class="icon-btn" aria-label="{{ __('friends.add_friend') }}"
            onclick="document.getElementById('friend-nickname')?.focus()"><i
                class="ph ph-user-plus text-xl"></i></button>
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
                <a href="{{ route('ranking-friends') }}" wire:navigate class="swiper-slide chip chip-primary" role="tab"
                    aria-selected="true">👫 {{ __('ranking.friends') }}</a>
                <a href="{{ route('league') }}" wire:navigate class="swiper-slide chip"
                    role="tab">🏆 {{ __('ranking.league') }}</a>
            </div>
        </div>
    </section>

    <section class="px-5 mt-2">
        <div class="k-card-lg hero-friends">
            <div class="relative flex items-center gap-3">
                <div class="text-5xl">🎉</div>
                <div class="grow">
                    <span class="chip bg-white/20 border-0 text-white">
                        <i class="ph-fill ph-users-three"></i> {{ __('friends.friends_chip', ['count' => $friendCount]) }}
                    </span>
                    <p class="h-display text-xl mt-1 leading-tight">
                        @if ($friendCount > 0)
                            {{ __('friends.beating_friends', ['count' => $beatingCount]) }}
                        @else
                            {{ __('friends.beating_none') }}
                        @endif
                    </p>
                    <p class="text-xs text-white/90">{{ __('friends.keep_learning') }}</p>
                </div>
            </div>
            <div class="relative mt-4 flex gap-2">
                <div class="mini-stat">
                    <p class="mini-v">#{{ $yourRank }}</p>
                    <p class="mini-l">{{ __('friends.your_rank') }}</p>
                </div>
                <div class="mini-stat">
                    <p class="mini-v">{{ $xpBehindLeader > 0 ? number_format($xpBehindLeader) : '—' }}</p>
                    <p class="mini-l">
                        {{ $xpBehindName !== '' ? __('friends.xp_behind', ['name' => $xpBehindName]) : __('friends.your_rank') }}
                    </p>
                </div>
                <div class="mini-stat">
                    <p class="mini-v">{{ $xpAheadNext > 0 ? '+'.number_format($xpAheadNext) : '—' }}</p>
                    <p class="mini-l">
                        {{ $xpAheadName !== '' ? __('friends.xp_ahead', ['name' => $xpAheadName]) : __('friends.your_rank') }}
                    </p>
                </div>
            </div>
            <div class="relative mt-4 flex flex-wrap items-center gap-2">
                <button type="button" class="cta-soft"
                    onclick="document.getElementById('friend-nickname')?.focus()">
                    <i class="ph-fill ph-user-plus"></i> {{ __('friends.invite_friend') }}
                </button>
                <span class="chip bg-white/20 border-0 text-white ml-auto">
                    <span class="live-dot"></span> {{ __('friends.online_count', ['count' => $onlineCount]) }}
                </span>
            </div>
        </div>
    </section>

    <section class="px-5 mt-4">
        <div class="input-wrap">
            <i class="ph ph-magnifying-glass i-left"></i>
            <input id="friend-nickname" wire:model="nickname" class="input has-left"
                placeholder="{{ __('friends.nickname_placeholder') }}"
                aria-label="{{ __('friends.nickname_label') }}" />
            <button type="button" wire:click="requestFriend" class="i-right"
                aria-label="{{ __('friends.send_request') }}"><i class="ph ph-user-plus text-xl"></i></button>
        </div>
        @error('nickname')
            <p class="text-sm mt-2" style="color:var(--color-k-coral)">{{ $message }}</p>
        @enderror
        @if ($flashMessage !== '')
            <p class="text-sm mt-2" style="color:var(--color-k-mint)">{{ $flashMessage }}</p>
        @endif
    </section>

    @if ($friendCount > 0)
        <section class="px-5 mt-4">
            <div class="podium-card">
                <div class="relative grid grid-cols-3 items-end gap-3">
                    <div class="podium-slot second">
                        <div class="podium-avatar">{{ is_array($second) ? $second['avatar'] : '—' }}</div>
                        <p class="podium-name">
                            {{ is_array($second) ? ($second['isYou'] ? __('friends.you', ['name' => $second['name']]) : $second['name']) : '—' }}
                        </p>
                        <p class="podium-xp">{{ is_array($second) ? number_format((int) $second['xp']).' XP' : '—' }}</p>
                        <div class="podium-base">🥈</div>
                    </div>
                    <div class="podium-slot first">
                        <span class="podium-crown" aria-hidden="true">👑</span>
                        <div class="podium-avatar">{{ is_array($first) ? $first['avatar'] : '—' }}</div>
                        <p class="podium-name">
                            {{ is_array($first) ? ($first['isYou'] ? __('friends.you', ['name' => $first['name']]) : $first['name']) : '—' }}
                        </p>
                        <p class="podium-xp">{{ is_array($first) ? number_format((int) $first['xp']).' XP' : '—' }}</p>
                        <div class="podium-base">🥇</div>
                    </div>
                    <div class="podium-slot third">
                        <div class="podium-avatar">{{ is_array($third) ? $third['avatar'] : '—' }}</div>
                        <p class="podium-name">
                            {{ is_array($third) ? ($third['isYou'] ? __('friends.you', ['name' => $third['name']]) : $third['name']) : '—' }}
                        </p>
                        <p class="podium-xp">{{ is_array($third) ? number_format((int) $third['xp']).' XP' : '—' }}</p>
                        <div class="podium-base">🥉</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="px-5 mt-4">
            <div class="input-wrap">
                <i class="ph ph-magnifying-glass i-left"></i>
                <input wire:model.live="filter" class="input has-left"
                    placeholder="{{ __('friends.search_placeholder') }}"
                    aria-label="{{ __('friends.search_placeholder') }}" />
                <button type="button" class="i-right" aria-label="Voice search"><i
                        class="ph ph-microphone text-xl"></i></button>
            </div>
            <div data-swiper-rail-tabs class="swiper rail-swiper mt-3" data-tabs>
                <div class="swiper-wrapper">
                    <button type="button" class="swiper-slide chip chip-primary" data-tab="all">
                        {{ __('friends.filter_all', ['count' => $friendCount]) }}</button>
                    <button type="button" class="swiper-slide chip" data-tab="online">
                        {{ __('friends.filter_online', ['count' => $onlineCount]) }}</button>
                    <button type="button" class="swiper-slide chip" data-tab="streak">
                        {{ __('friends.filter_streak') }}</button>
                    <button type="button" class="swiper-slide chip" data-tab="near">
                        {{ __('friends.filter_near') }}</button>
                </div>
            </div>
        </section>

        <section class="px-5 mt-4">
            <div class="section-head">
                <h2 class="h-display text-lg">{{ __('friends.friend_ranking') }}</h2>
                <span class="link cursor-default">{{ __('friends.total_xp') }}</span>
            </div>

            <div class="space-y-2">
                @foreach ($filtered as $index => $row)
                    @php
                        $rankClass = match ((int) $row['rank']) {
                            1 => 'top-1',
                            2 => 'top-2',
                            3 => 'top-3',
                            default => '',
                        };
                        $tile = $tiles[$index % count($tiles)];
                    @endphp
                    <div class="friend-row {{ $row['isYou'] ? 'you' : '' }}">
                        <span class="rank-num {{ $rankClass }}">{{ $row['rank'] }}</span>
                        <div class="friend-av {{ $tile }}">{{ $row['avatar'] }}@if (!$row['isYou'])<span
                                class="live"></span>@endif</div>
                        <div class="grow min-w-0">
                            <p class="font-extrabold text-sm text-ink">
                                {{ $row['isYou'] ? __('friends.you', ['name' => $row['name']]) : $row['name'] }}</p>
                            <div class="friend-meta">
                                <span>{{ __('friends.lv', ['level' => $row['level']]) }}</span>
                                @if ($row['streak'] > 0)
                                    ·<span class="text-sun-ink">{{ __('friends.streak_days', ['days' => $row['streak']]) }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <span
                                class="chip {{ $row['isYou'] ? 'chip-primary' : ($row['rank'] === 1 ? 'chip-sun' : '') }}">
                                {{ number_format($row['xp']) }} XP
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @else
        <section class="px-5 mt-5">
            <div class="k-card p-5 text-center">
                <div class="text-4xl">👫</div>
                <p class="h-display text-lg mt-2">{{ __('friends.empty_title') }}</p>
                <p class="text-sm text-muted mt-1">{{ __('friends.empty_body') }}</p>
                <button type="button" class="btn btn-primary mt-4"
                    onclick="document.getElementById('friend-nickname')?.focus()">
                    <i class="ph-fill ph-user-plus"></i> {{ __('friends.add_friend') }}
                </button>
            </div>
        </section>
    @endif

    <section class="px-5 mt-5">
        <div class="invite-card">
            <div class="size-12 rounded-2xl tile-violet grid place-items-center text-xl shrink-0">📨</div>
            <div class="grow">
                <p class="font-extrabold text-sm text-ink">{{ __('friends.invite_earn') }}</p>
                <p class="text-xs text-muted">{{ __('friends.invite_earn_sub') }}</p>
            </div>
            <button type="button" class="btn btn-primary h-10 min-h-0 px-4 text-sm shrink-0"
                onclick="document.getElementById('friend-nickname')?.focus()">{{ __('friends.invite') }}</button>
        </div>
    </section>

    <section class="px-5 mt-5 mb-5">
        <div class="tip-card rounded-2xl p-4 flex items-start gap-3">
            <div class="mascot shrink-0 size-11 text-xl">🦉</div>
            <div class="grow">
                <p class="font-extrabold text-sm text-ink">{{ __('friends.safe_title') }}</p>
                <p class="text-xs text-muted">{{ __('friends.safe_body') }}</p>
            </div>
            <span class="chip chip-primary">{{ __('friends.privacy') }}</span>
        </div>
    </section>

    <livewire:bottom-nav-bar />
</main>
