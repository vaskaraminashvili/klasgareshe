<?php

use App\Repositories\UserRepository;
use App\Services\UserStatService;
use Illuminate\View\View;
use Livewire\Component;

new class extends Component
{
    public int $xp = 0;

    public int $level = 1;

    public string $levelTitle = '';

    public string $nextTitle = '';

    public int $nextLevel = 2;

    public int $xpToNext = 0;

    public int $percent = 0;

    public int $todayXp = 0;

    public int $weekXp = 0;

    public int $avgPerDay = 0;

    public int $vsLastWeekPercent = 0;

    public int $bestDayXp = 0;

    public string $bestDayLabel = '';

    public string $quietDayLabel = '';

    public int $activeDays = 0;

    public string $chartJson = '[]';

    public function title(): string
    {
        return __('ranking.xp_page_title');
    }

    public function rendering(View $view): void
    {
        $view->title($this->title());
    }

    public function mount(UserStatService $stats, UserRepository $users): void
    {
        $snap = $stats->xpProgressSnapshot($users->authenticated());
        $this->xp = $snap->level->xp;
        $this->level = $snap->level->level;
        $this->levelTitle = $snap->level->title;
        $this->nextTitle = $snap->level->nextTitle;
        $this->nextLevel = $snap->level->nextLevel;
        $this->xpToNext = $snap->level->xpToNext;
        $this->percent = $snap->level->percent;
        $this->todayXp = $snap->todayXp;
        $this->weekXp = $snap->weekXp;
        $this->avgPerDay = $snap->avgPerDay;
        $this->vsLastWeekPercent = $snap->vsLastWeekPercent;
        $this->bestDayXp = $snap->bestDayXp;
        $this->bestDayLabel = $snap->bestDayLabel;
        $this->quietDayLabel = $snap->quietDayLabel;
        $this->activeDays = $snap->activeDays;
        $this->chartJson = $snap->chartJson;
    }
};
?>

<main class="device-frame min-h-screen flex flex-col">

    <header class="appbar safe-top">
        <a href="{{ route('home') }}" class="icon-btn" data-back aria-label="{{ __('ranking.your_growth') }}"><i
                class="ph ph-caret-left"></i></a>
        <div class="grow">
            <p class="text-xs text-muted">{{ __('ranking.your_growth') }}</p>
            <h1 class="h-display text-lg leading-tight">{{ __('ranking.xp_progress') }}</h1>
        </div>
        <button type="button" class="icon-btn" aria-label="Share"><i class="ph ph-share-fat text-xl"></i></button>
        <button type="button" class="icon-btn" data-theme-toggle aria-label="Toggle theme"><i
                class="ph ph-moon text-xl"></i></button>
    </header>

    <section class="px-5">
        <div class="hero-xp k-card-lg">
            <div class="relative xp-ring" style="--pct: {{ $percent }}">
                <div class="xp-ring-inner">
                    <div>
                        <p class="xp-level">Lv {{ $level }}</p>
                        <p class="xp-label">{{ $levelTitle }}</p>
                    </div>
                </div>
            </div>

            <p class="relative chip bg-white/20 border-0 text-white mt-4">
                <i class="ph-fill ph-sparkle"></i> {{ __('ranking.total_xp_chip') }}
            </p>
            <p class="relative h-display text-5xl mt-2 leading-none">{{ number_format($xp) }}</p>
            <p class="relative text-xs text-white/90 mt-1">
                {{ __('ranking.xp_to_level', ['xp' => number_format($xpToNext), 'level' => $nextLevel, 'title' => $nextTitle]) }}
            </p>

            <div class="relative mt-4 flex items-center gap-3">
                <div class="progress on-gradient grow"><span style="width: {{ $percent }}%"></span></div>
                <span class="text-sm font-extrabold shrink-0">{{ $percent }}%</span>
            </div>

            <div class="relative mt-4 grid grid-cols-4 gap-2">
                <div class="hero-metric">
                    <p class="hm-v">+{{ number_format($todayXp) }}</p>
                    <p class="hm-l">{{ __('ranking.today') }}</p>
                </div>
                <div class="hero-metric">
                    <p class="hm-v">+{{ number_format($weekXp) }}</p>
                    <p class="hm-l">{{ __('ranking.week') }}</p>
                </div>
                <div class="hero-metric">
                    <p class="hm-v">—</p>
                    <p class="hm-l">{{ __('ranking.friend_rank') }}</p>
                </div>
                <div class="hero-metric">
                    <p class="hm-v">{{ number_format($avgPerDay) }}</p>
                    <p class="hm-l">{{ __('ranking.avg_day') }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="px-5 mt-4 grid grid-cols-3 gap-3">
        <div class="k-card p-3 text-center">
            <div class="size-10 rounded-2xl tile-mint grid place-items-center text-xl mx-auto">📈</div>
            <p class="h-display text-lg mt-1">{{ $vsLastWeekPercent >= 0 ? '+' : '' }}{{ $vsLastWeekPercent }}%</p>
            <p class="text-[11px] text-muted font-extrabold">{{ __('ranking.vs_last_week') }}</p>
        </div>
        <div class="k-card p-3 text-center">
            <div class="size-10 rounded-2xl tile-sun grid place-items-center text-xl mx-auto">⚡</div>
            <p class="h-display text-lg mt-1">+{{ number_format($bestDayXp) }}</p>
            <p class="text-[11px] text-muted font-extrabold">{{ __('ranking.best_day') }}</p>
        </div>
        <div class="k-card p-3 text-center">
            <div class="size-10 rounded-2xl tile-coral grid place-items-center text-xl mx-auto">🎯</div>
            <p class="h-display text-lg mt-1">{{ $activeDays }} / 7</p>
            <p class="text-[11px] text-muted font-extrabold">{{ __('ranking.active_days') }}</p>
        </div>
    </section>

    <section class="px-5 mt-5">
        <div class="section-head">
            <h2 class="h-display text-lg">{{ __('ranking.last_7_days') }}</h2>
            <span class="trend-pill"><i class="ph-fill ph-trend-up"></i>
                {{ $vsLastWeekPercent >= 0 ? '+' : '' }}{{ $vsLastWeekPercent }}%</span>
        </div>
        <div class="k-card text-ink">
            <div id="line" data-points='@json(json_decode($chartJson, true))'></div>
            <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                <div class="p-2 rounded-xl bg-base">
                    <p class="text-[10px] font-extrabold uppercase tracking-wide text-muted">
                        {{ __('ranking.chart_total') }}
                    </p>
                    <p class="h-display text-base">+{{ number_format($weekXp) }} XP</p>
                </div>
                <div class="p-2 rounded-xl bg-base">
                    <p class="text-[10px] font-extrabold uppercase tracking-wide text-muted">
                        {{ __('ranking.chart_peak') }}
                    </p>
                    <p class="h-display text-base">{{ $bestDayLabel }}</p>
                </div>
                <div class="p-2 rounded-xl bg-base">
                    <p class="text-[10px] font-extrabold uppercase tracking-wide text-muted">
                        {{ __('ranking.chart_quiet') }}
                    </p>
                    <p class="h-display text-base">{{ $quietDayLabel }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="px-5 mt-4">
        <div class="flex items-center justify-between">
            <p class="section-label">{{ __('ranking.time_range') }}</p>
            <div class="segmented">
                <button type="button">{{ __('ranking.today') }}</button>
                <button type="button" class="is-active">{{ __('ranking.week') }}</button>
                <button type="button">{{ __('ranking.month') }}</button>
                <button type="button">{{ __('ranking.all') }}</button>
            </div>
        </div>
    </section>

    <section class="px-5 mt-5">
        <div class="section-head">
            <h2 class="h-display text-lg">{{ __('ranking.xp_by_subject') }}</h2>
            <span class="link cursor-default">{{ __('ranking.subjects_placeholder') }}</span>
        </div>
        <div class="k-card p-4 text-center text-sm text-muted">{{ __('ranking.subjects_placeholder') }}</div>
    </section>

    <section class="px-5 mt-5">
        <div class="section-head">
            <h2 class="h-display text-lg">{{ __('ranking.level_roadmap') }}</h2>
            <span class="link cursor-default">{{ __('ranking.you_are_lv', ['level' => $level]) }}</span>
        </div>
        <div class="space-y-2">
            <div class="reward-row">
                <div class="rr-ico tile-mint">✓</div>
                <div class="grow min-w-0">
                    <p class="font-extrabold text-sm text-ink">Level {{ $level }} · {{ $levelTitle }}</p>
                    <p class="text-[11px] text-muted">{{ __('ranking.current') }}</p>
                </div>
                <span class="chip chip-mint">{{ __('ranking.current') }}</span>
            </div>
            <div class="reward-row">
                <div class="rr-ico tile-violet">{{ $nextLevel }}</div>
                <div class="grow min-w-0">
                    <p class="font-extrabold text-sm text-ink">Level {{ $nextLevel }} · {{ $nextTitle }}</p>
                    <div class="progress mt-1"><span style="width: {{ $percent }}%"></span></div>
                </div>
                <span class="chip chip-primary">{{ number_format($xpToNext) }} XP</span>
            </div>
        </div>
    </section>

    <section class="px-5 mt-5">
        <div class="section-head">
            <h2 class="h-display text-lg">{{ __('ranking.where_xp_comes') }}</h2>
            <span class="link cursor-default">{{ __('ranking.this_week') }}</span>
        </div>
        <div class="k-card p-4 text-center text-sm text-muted">{{ __('ranking.sources_placeholder') }}</div>
    </section>

    <section class="px-5 mt-5">
        <a href="{{ route('game-multiple-choice') }}" wire:navigate
            class="k-card-lg card-hero-primary relative overflow-hidden block">
            <span class="watermark-emoji" aria-hidden="true">⚡</span>
            <div class="relative flex items-center gap-3">
                <div class="text-4xl">⚡</div>
                <div class="grow">
                    <p class="text-xs uppercase font-extrabold tracking-wider opacity-90">
                        {{ __('ranking.level_up_faster') }}
                    </p>
                    <p class="h-display text-lg leading-tight">{{ __('ranking.finish_mission') }}</p>
                </div>
                <span class="cta-soft shrink-0">{{ __('ranking.start') }}</span>
            </div>
        </a>
    </section>

    <section class="px-5 mt-5 mb-5">
        <div class="tip-card rounded-2xl p-4 flex items-start gap-3">
            <div class="mascot shrink-0 size-11 text-xl">🦉</div>
            <div class="grow">
                <p class="font-extrabold text-sm text-ink">{{ __('ranking.what_is_xp') }}</p>
                <p class="text-xs text-muted">{{ __('ranking.what_is_xp_body') }}</p>
            </div>
            <span class="chip chip-primary">{{ __('ranking.learn') }}</span>
        </div>
    </section>

    <livewire:bottom-nav-bar />
</main>

@push('scripts')
    <script src="{{ asset('assets/js/charts.js') }}"></script>
    <script src="{{ asset('assets/js/xp-progress.js') }}"></script>
@endpush
