<?php

use App\Repositories\UserRepository;
use App\Services\BadgeService;
use App\Services\FriendshipService;
use App\Services\MonthlyGoalService;
use App\Services\UserStatService;
use App\Services\WeekPlanService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('პროფილი · Kidzio')] class extends Component
{
    public string $displayName = '';

    public string $avatar = '🐻';

    public string $metaLine = '';

    public int $level = 1;

    public string $levelTitle = '';

    public int $nextLevel = 2;

    public int $xpToNext = 0;

    public int $levelPercent = 0;

    public int $xp = 0;

    public int $streak = 0;

    public int $badgeCount = 0;

    public int $catalogCount = 0;

    public int $rank = 1;

    public string $leagueLabel = '';

    public int $weekXp = 0;

    public int $weekActiveDays = 0;

    public int $weekLessons = 0;

    public string $weekRangeLabel = '';

    public int $monthlyGoalsHit = 0;

    public int $monthlyGoalsTotal = 4;

    public int $friendsCount = 0;

    public int $friendsBeating = 0;

    /** @var list<string> */
    public array $friendAvatars = [];

    /** @var list<array{letter: string, on: bool, today: bool}> */
    public array $weekDays = [];

    /**
     * @var list<array{subject: string, label: string, emoji: string, tile: string, progressClass: string, percent: int, done: int, total: int, nextItemId: int|null, href: string}>
     */
    public array $mastery = [];

    /**
     * @var list<array{slug: string, name: string, emoji: string, medalClass: string, meta: string, href: string, locked: bool, unseen: bool}>
     */
    public array $recentBadges = [];

    /**
     * @var list<array{slug: string, name: string, emoji: string, meta: string, href: string, unseen: bool}>
     */
    public array $achievements = [];

    public function mount(
        UserStatService $stats,
        WeekPlanService $week,
        BadgeService $badges,
        MonthlyGoalService $monthlyGoals,
        FriendshipService $friendships,
        UserRepository $users,
    ): void {
        $user = $users->authenticated();
        $snap = $stats->profileSnapshot($user, $week->lessonsCompletedThisWeek($user));
        $monthly = $monthlyGoals->snapshot($user);
        $friendsStrip = $friendships->profileStrip($user);

        $this->displayName = $snap->name;
        $this->avatar = $snap->avatar;
        $this->metaLine = $snap->age !== null
            ? (string) __('profile.age_grade', ['age' => $snap->age, 'grade' => $snap->gradeLabel])
            : $snap->gradeLabel;
        $this->level = $snap->level->level;
        $this->levelTitle = $snap->level->title;
        $this->nextLevel = $snap->level->nextLevel;
        $this->xpToNext = $snap->level->xpToNext;
        $this->levelPercent = $snap->level->percent;
        $this->xp = $snap->xp;
        $this->streak = $snap->streak;
        $this->rank = $snap->rank;
        $this->leagueLabel = $snap->leagueLabel;
        $this->weekXp = $snap->weekXp;
        $this->weekActiveDays = $snap->weekActiveDays;
        $this->weekLessons = $snap->weekLessons;
        $this->weekRangeLabel = $snap->weekRangeLabel;
        $this->weekDays = $snap->weekDays;
        $this->monthlyGoalsHit = $monthly->goalsHit;
        $this->monthlyGoalsTotal = $monthly->goalsTotal;

        $this->badgeCount = $badges->earnedCount($user);
        $this->catalogCount = $badges->catalogCount();
        $this->recentBadges = array_map(fn ($card) => $card->toArray(), $badges->recentRail($user));
        $this->mastery = array_map(fn ($row) => $row->toArray(), $week->subjectMastery($user));
        $this->friendsCount = $friendsStrip->count;
        $this->friendsBeating = $friendsStrip->beatingCount;
        $this->friendAvatars = $friendsStrip->avatars;

        $this->achievements = [];
        foreach ($this->recentBadges as $badge) {
            if ($badge['locked']) {
                continue;
            }

            $this->achievements[] = [
                'slug' => $badge['slug'],
                'name' => $badge['name'],
                'emoji' => $badge['emoji'],
                'meta' => $badge['meta'],
                'href' => $badge['href'],
                'unseen' => $badge['unseen'],
            ];
        }
    }

    public function formattedXp(): string
    {
        return number_format($this->xp);
    }

    public function formattedWeekXp(): string
    {
        return $this->weekXp > 0
            ? '+'.number_format($this->weekXp)
            : '0';
    }

    public function logout(): void
    {
        Auth::logout();

        session()->invalidate();
        session()->regenerateToken();

        $this->redirectRoute('user-login');
    }
};
?>

<main class="device-frame min-h-screen flex flex-col">

    <!-- =============== APPBAR =============== -->
    <header class="appbar safe-top">
        <div class="grow">
            <p class="text-xs text-muted">{{ __('profile.your_learning_world') }}</p>
            <h1 class="h-display text-2xl leading-tight">{{ __('profile.profile') }}</h1>
        </div>
        <a href="{{ route('edit-profile') }}" wire:navigate class="icon-btn" aria-label="{{ __('profile.edit_profile') }}"><i
                class="ph ph-pencil-simple text-xl"></i></a>
        {{-- Settings gear dropped until the screen exists (docs/tasks/T10-settings-screen.md). --}}
        <button class="icon-btn" data-theme-toggle aria-label="{{ __('profile.toggle_theme') }}"><i
                class="ph ph-moon text-xl"></i></button>
    </header>

    <!-- =============== PROFILE HERO =============== -->
    <section class="px-5">
        <div class="k-card-lg hero-profile text-center">
            <div class="relative">
                <span class="profile-avatar">
                    <span>{{ $avatar }}</span>
                    <a href="{{ route('edit-profile') }}" wire:navigate class="camera-badge" aria-label="{{ __('profile.change_avatar') }}">
                        <i class="ph-fill ph-camera text-sm"></i>
                    </a>
                </span>
            </div>

            <h2 class="h-display text-2xl mt-4 relative">{{ $displayName }}</h2>
            <p class="text-xs text-white/90 relative">{{ $metaLine }}</p>

            <div class="relative mt-3 flex items-center justify-center gap-2">
                <span class="chip bg-white/20 border-0 text-white">
                    <i class="ph-fill ph-crown-simple"></i>
                    {{ __('profile.level_rank', ['level' => $level, 'rank' => $levelTitle]) }}
                </span>
                {{-- "Online" chip dropped: nothing tracks presence, so it was always on.
                     Re-port it when sessions exist (docs/tasks/T07-screen-time-bedtime.md). --}}
            </div>

            <div class="relative mt-5 grid grid-cols-4 gap-2">
                <div class="hero-metric">
                    <p class="hm-v">{{ $this->formattedXp() }}</p>
                    <p class="hm-l">{{ __('profile.xp') }}</p>
                </div>
                <div class="hero-metric">
                    <p class="hm-v">{{ $streak }}</p>
                    <p class="hm-l">{{ __('profile.streak') }}</p>
                </div>
                <div class="hero-metric">
                    <p class="hm-v">{{ $badgeCount }}</p>
                    <p class="hm-l">{{ __('profile.badges') }}</p>
                </div>
                <div class="hero-metric">
                    <p class="hm-v">{{ __('profile.rank_n', ['rank' => $rank]) }}</p>
                    <p class="hm-l">{{ __('profile.rank') }}</p>
                </div>
            </div>

            <div class="relative mt-4">
                <div class="flex items-center gap-3">
                    <div class="progress on-gradient grow"><span style="width: {{ $levelPercent }}%"></span></div>
                    <span class="text-sm font-extrabold shrink-0">{{ __('profile.level', ['level' => $nextLevel]) }}</span>
                </div>
                <p class="text-[11px] text-white/90 mt-1">{{ __('profile.xp_to_level_up', ['xp' => $xpToNext]) }}</p>
            </div>

            <div class="relative mt-4 flex items-center justify-center gap-2">
                <a href="{{ route('edit-profile') }}" wire:navigate class="cta-soft">
                    <i class="ph-fill ph-pencil-simple"></i> {{ __('profile.edit_profile') }}
                </a>
                {{-- Share button dropped: no share backend, the button did nothing
                     (docs/tasks/T21-sound-voice-appearance.md). --}}
            </div>
        </div>
    </section>

    <!-- =============== QUICK SHORTCUTS =============== -->
    <section class="px-5 mt-4 grid grid-cols-3 gap-3">
        {{-- Inert until the streak screen exists (docs/tasks/T11-streaks-and-xp.md). --}}
        <div class="k-card p-3 text-center">
            <div class="size-10 rounded-2xl tile-sun grid place-items-center text-xl mx-auto">🔥</div>
            <p class="h-display text-lg mt-1">{{ $streak }}</p>
            <p class="text-[11px] text-muted font-extrabold">{{ __('profile.day_streak') }}</p>
        </div>
        <a href="{{ route('xp-progress') }}" wire:navigate class="k-card p-3 text-center">
            <div class="size-10 rounded-2xl tile-violet grid place-items-center text-xl mx-auto">⭐</div>
            <p class="h-display text-lg mt-1">{{ $this->formattedXp() }}</p>
            <p class="text-[11px] text-muted font-extrabold">{{ __('profile.total_xp') }}</p>
        </a>
        <a href="{{ route('league') }}" wire:navigate class="k-card p-3 text-center">
            <div class="size-10 rounded-2xl tile-mint grid place-items-center text-xl mx-auto">🏆</div>
            <p class="h-display text-lg mt-1">{{ $leagueLabel }}</p>
            <p class="text-[11px] text-muted font-extrabold">{{ __('profile.league') }}</p>
        </a>
    </section>

    <!-- =============== RECENT BADGES =============== -->
    <section class="mt-5">
        <div class="section-head px-5">
            <h2 class="h-display text-lg">{{ __('profile.recent_badges') }}</h2>
            <a href="{{ route('badges') }}" wire:navigate class="link">{{ __('profile.all_count', ['count' => $badgeCount]) }}</a>
        </div>
        <div data-swiper-rail class="swiper rail-swiper">
            <div class="swiper-wrapper">
                @foreach ($recentBadges as $badge)
                    <a href="{{ $badge['href'] }}" wire:navigate
                        class="swiper-slide k-card text-center w-28{{ $badge['locked'] ? ' locked' : '' }}">
                        <span class="badge-medal {{ $badge['medalClass'] }} mx-auto">{{ $badge['emoji'] }}</span>
                        <p class="text-xs font-extrabold mt-2">{{ $badge['name'] }}</p>
                        <p class="text-[10px] text-muted">{{ $badge['meta'] }}</p>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <!-- =============== SUBJECT MASTERY =============== -->
    <section class="px-5 mt-5">
        <div class="section-head">
            <h2 class="h-display text-lg">{{ __('profile.subject_mastery') }}</h2>
            <a href="{{ route('daily-mission') }}" wire:navigate class="link">{{ __('profile.explore') }}</a>
        </div>
        <div class="space-y-2">
            @foreach ($mastery as $row)
                <a href="{{ $row['href'] }}" wire:navigate class="mastery-row">
                    <div class="mastery-ico {{ $row['tile'] }}">{{ $row['emoji'] }}</div>
                    <div class="grow min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="font-extrabold text-sm text-ink">{{ $row['label'] }}</p>
                            <span class="text-[11px] text-muted font-extrabold ml-auto">{{ $row['percent'] }}%</span>
                        </div>
                        <div class="progress {{ $row['progressClass'] }} mt-1"><span style="width: {{ $row['percent'] }}%"></span></div>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    <!-- =============== THIS WEEK ACTIVITY =============== -->
    <section class="px-5 mt-5">
        <div class="section-head">
            <h2 class="h-display text-lg">{{ __('profile.this_week') }}</h2>
            <span class="link cursor-default">{{ $weekRangeLabel }}</span>
        </div>
        <div class="k-card p-4">
            <div class="grid grid-cols-3 gap-3 text-center">
                <div>
                    <p class="h-display text-lg">{{ $this->formattedWeekXp() }}</p>
                    <p class="text-[10px] text-muted font-extrabold uppercase tracking-wide">
                        {{ __('profile.xp_earned') }}</p>
                </div>
                <div>
                    <p class="h-display text-lg">{{ $weekActiveDays }} / 7</p>
                    <p class="text-[10px] text-muted font-extrabold uppercase tracking-wide">
                        {{ __('profile.active_days') }}</p>
                </div>
                <div>
                    <p class="h-display text-lg">{{ $weekLessons }}</p>
                    <p class="text-[10px] text-muted font-extrabold uppercase tracking-wide">
                        {{ __('profile.lessons') }}</p>
                </div>
            </div>
            <div class="mt-3 grid grid-cols-7 gap-2">
                @foreach ($weekDays as $day)
                    <span @class(['streak-dot', 'on' => $day['on'], 'today' => $day['today']])>{{ $day['letter'] }}</span>
                @endforeach
            </div>
        </div>
    </section>

    <!-- =============== ACHIEVEMENTS TIMELINE =============== -->
    <section class="px-5 mt-5">
        <div class="section-head">
            <h2 class="h-display text-lg">{{ __('profile.recent_achievements') }}</h2>
            <a href="{{ route('badges') }}" wire:navigate class="link">{{ __('profile.see_all') }}</a>
        </div>
        <div class="space-y-2">
            @forelse ($achievements as $item)
                <a href="{{ $item['href'] }}" wire:navigate class="ach-mini">
                    <div class="ach-ico">{{ $item['emoji'] }}</div>
                    <div class="grow min-w-0">
                        <p class="font-extrabold text-sm text-ink">{{ __('profile.unlocked_badge', ['name' => $item['name']]) }}</p>
                        <p class="text-[11px] text-muted">{{ $item['meta'] }}</p>
                    </div>
                    @if ($item['unseen'])
                        <span class="chip chip-mint">{{ __('profile.new') }}</span>
                    @endif
                </a>
            @empty
                <div class="k-card p-4 text-center">
                    <p class="text-sm text-muted font-extrabold">{{ __('profile.no_recent_achievements') }}</p>
                </div>
            @endforelse
        </div>
    </section>

    <!-- =============== FRIENDS STRIP =============== -->
    <section class="px-5 mt-5">
        <div class="section-head">
            <h2 class="h-display text-lg">{{ __('profile.friends') }}</h2>
            <a href="{{ route('ranking-friends') }}" wire:navigate class="link">{{ __('profile.view_all') }}</a>
        </div>
        <a href="{{ route('ranking-friends') }}" wire:navigate class="k-card p-3 flex items-center gap-3">
            <div class="avatar-stack">
                @forelse ($friendAvatars as $index => $friendAvatar)
                    @php
                        $tile = ['tile-sun', 'tile-mint', 'tile-coral', 'tile-sky'][$index % 4];
                    @endphp
                    <span class="size-10 rounded-full {{ $tile }} grid place-items-center text-lg">{{ $friendAvatar }}</span>
                @empty
                    <span class="size-10 rounded-full tile-sun grid place-items-center text-lg">👫</span>
                @endforelse
            </div>
            <div class="grow">
                <p class="font-extrabold text-sm text-ink">
                    {{ __('profile.friends_count', ['total' => $friendsCount]) }}</p>
                <p class="text-[11px] text-muted">
                    {{ __('profile.beating_friends_this_week', ['count' => $friendsBeating]) }}</p>
            </div>
            <i class="ph ph-caret-right text-muted"></i>
        </a>
    </section>

    <!-- =============== MENU LIST =============== -->
    <section class="px-5 mt-5">
        <p class="section-label">{{ __('profile.quick_links') }}</p>
        <div class="mt-3 space-y-2">
            {{-- Daily-streak row dropped: it only navigated, and the streak count is already
                 live in the shortcut above (docs/tasks/T11-streaks-and-xp.md). --}}
            <a href="{{ route('xp-progress') }}" wire:navigate class="menu-row">
                <div class="menu-ico tile-violet">📈</div>
                <p class="menu-text font-extrabold text-sm grow">{{ __('profile.xp_progress') }}</p>
                <span class="chip chip-primary">{{ __('profile.level', ['level' => $level]) }}</span>
                <i class="ph ph-caret-right text-muted"></i>
            </a>
            <a href="{{ route('monthly-goals') }}" wire:navigate class="menu-row">
                <div class="menu-ico tile-mint">🎯</div>
                <p class="menu-text font-extrabold text-sm grow">{{ __('profile.monthly_goals') }}</p>
                <span class="chip chip-mint">{{ $monthlyGoalsHit }} / {{ $monthlyGoalsTotal }}</span>
                <i class="ph ph-caret-right text-muted"></i>
            </a>
            <a href="{{ route('badges') }}" wire:navigate class="menu-row">
                <div class="menu-ico tile-coral">🏅</div>
                <p class="menu-text font-extrabold text-sm grow">{{ __('profile.all_badges') }}</p>
                <span class="chip chip-primary">{{ $badgeCount }} / {{ $catalogCount }}</span>
                <i class="ph ph-caret-right text-muted"></i>
            </a>
            {{-- Rewards-dashboard row dropped: dead `href="#"` with a hardcoded "3 new".
                 Re-port it with a real claim count (docs/tasks/T13-rewards-dashboard.md). --}}
        </div>
    </section>

    {{-- PARENT ZONE dropped: none of the three screens exist and the screen-time chip
         hardcoded "30 min". This section must also sit behind the PIN gate, so re-port it
         from kidzio/profile.html with docs/tasks/T06-parent-pin-gate.md (controls),
         T07 (screen time) and T08 (weekly report). --}}

    <!-- =============== SETTINGS / LOGOUT =============== -->
    <section class="px-5 mt-5 mb-5">
        <p class="section-label">{{ __('profile.account') }}</p>
        <div class="mt-3 space-y-2">
            {{-- Settings row dropped until the screen exists (docs/tasks/T10-settings-screen.md). --}}
            <button data-install hidden class="menu-row w-full text-left">
                <div class="menu-ico tile-violet"><i class="ph-fill ph-download-simple"></i></div>
                <p class="menu-text font-extrabold text-sm grow">{{ __('profile.install_kidzio') }}</p>
                <span class="chip chip-primary">PWA</span>
            </button>
            <button type="button" class="menu-row danger w-full text-left" wire:click="logout">
                <div class="menu-ico"><i class="ph-fill ph-sign-out"></i></div>
                <p class="menu-text font-extrabold text-sm grow">{{ __('profile.log_out') }}</p>
            </button>
        </div>
        <p class="text-xs text-center text-muted mt-5">{{ __('profile.footer_version') }}</p>
    </section>

    <livewire:bottom-nav-bar />
</main>
