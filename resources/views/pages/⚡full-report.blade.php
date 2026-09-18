<?php

use App\Enums\ReportScope;
use App\Repositories\UserRepository;
use App\Services\ProgressReportService;
use Livewire\Component;

new class extends Component
{
    public string $scope = 'month';

    public bool $shareSheet = false;

    public bool $compareSheet = false;

    public bool $kpiSheet = false;

    public bool $eventSheet = false;

    public int $openKpi = -1;

    public int $openEvent = -1;

    public string $emailHint = '';

    public function title(): string
    {
        return __('reports.full_title');
    }

    public function rendering(\Illuminate\View\View $view): void
    {
        $view->title($this->title());
    }

    public function pickScope(string $scope): void
    {
        if (ReportScope::tryFrom($scope) instanceof ReportScope) {
            $this->scope = $scope;
        }
    }

    public function sendEmail(): void
    {
        app(ProgressReportService::class)->emailWeek(
            app(UserRepository::class)->authenticated(),
        );
        $this->shareSheet = false;
        $this->emailHint = (string) __('reports.emailed');
    }

    public function scopeEnum(): ReportScope
    {
        return ReportScope::tryFrom($this->scope) ?? ReportScope::Month;
    }

    /**
     * @return array<string, mixed>
     */
    public function snap(): array
    {
        $full = app(ProgressReportService::class)->fullSnapshot(
            app(UserRepository::class)->authenticated(),
            $this->scopeEnum(),
        );

        return [
            'kidName' => $full->kidName,
            'rangeEyebrow' => $full->rangeEyebrow,
            'headline' => $full->headline,
            'subline' => $full->subline,
            'scopeChip' => $full->scopeChip,
            'xp' => $full->figures->xp,
            'packs' => $full->figures->packs,
            'accuracy' => $full->figures->accuracyPercent,
            'paceChip' => $full->paceChip,
            'daysChip' => $full->daysChip,
            'chartTitle' => $this->scope === 'week' ? __('reports.chart_title_week') : __('reports.chart_title_weeks'),
            'trend' => $full->trend,
            'subjects' => array_map(fn ($s) => [
                'label' => $s->label,
                'emoji' => $s->emoji,
                'tile' => $s->tile,
                'progressClass' => $s->progressClass,
                'packs' => $s->packs,
                'masteryPercent' => $s->masteryPercent,
                'barPercent' => $s->barPercent,
                'href' => $s->nextItemId !== null
                    ? route('game-multiple-choice', ['item' => $s->nextItemId])
                    : route('daily-mission'),
            ], $full->subjects),
            'kpis' => $full->kpis,
            'timeline' => array_map(fn ($h) => [
                'title' => $h->title,
                'subtitle' => $h->subtitle,
                'body' => $h->body,
                'emoji' => $h->emoji,
                'tile' => $h->tile,
                'chip' => $h->chip,
            ], $full->timeline),
            'insights' => array_map(fn ($h) => [
                'title' => $h->title,
                'body' => $h->body,
                'emoji' => $h->emoji,
                'chip' => $h->chip,
            ], $full->insights),
            'rangeLabel' => $full->figures->rangeLabel,
            'minutesHint' => $full->minutesHint,
            'vs' => $full->figures->vsPreviousPercent,
            'hasPrevious' => $full->figures->hasPrevious,
            'shareText' => $full->headline.' · '.$full->subline,
        ];
    }
};
?>

@php $s = $this->snap(); $kpi = $s['kpis'][$openKpi] ?? null; $event = $s['timeline'][$openEvent] ?? null; @endphp

<main class="device-frame min-h-screen flex flex-col safe-top">

    <header class="appbar">
        <a href="{{ route('parent-controls') }}" class="icon-btn" data-back aria-label="{{ __('reports.back') }}"><i class="ph ph-caret-left"></i></a>
        <div class="grow">
            <p class="text-xs text-muted">{{ $s['rangeEyebrow'] }}</p>
            <h1 class="h-display text-lg leading-tight">{{ __('reports.full_heading') }}</h1>
        </div>
        <button type="button" wire:click="$set('shareSheet', true)" class="icon-btn" aria-label="{{ __('reports.share') }}"><i class="ph ph-share-fat text-xl"></i></button>
        <button class="icon-btn" data-theme-toggle aria-label="{{ __('reports.toggle_theme') }}"><i class="ph ph-moon text-xl"></i></button>
    </header>

    <section class="px-5">
        <div data-swiper-rail-tabs class="swiper rail-swiper" role="tablist" aria-label="{{ __('reports.scope_tabs') }}">
            <div class="swiper-wrapper">
                @foreach (['week' => 'scope_week', 'month' => 'scope_month', 'season' => 'scope_season', 'all' => 'scope_all'] as $key => $label)
                    <button type="button" wire:click="pickScope('{{ $key }}')" class="swiper-slide chip {{ $scope === $key ? 'chip-primary' : '' }}" @if ($scope === $key) aria-selected="true" @endif>
                        {{ __('reports.'.$label) }}
                    </button>
                @endforeach
            </div>
        </div>
    </section>

    <section class="px-5 mt-3">
        <div class="k-card-lg hero-profile text-center">
            <div class="relative inline-grid place-items-center">
                <div class="size-24 rounded-3xl bg-white/20 backdrop-blur-sm grid place-items-center text-5xl">📈</div>
            </div>
            <p class="relative chip bg-white/20 border-0 text-white mt-4">
                <i class="ph-fill ph-sparkle"></i> {{ $s['scopeChip'] }}
            </p>
            <p class="relative h-display text-2xl mt-2 leading-tight">{{ $s['headline'] }}</p>
            <p class="relative text-xs text-white/90 mt-1">{{ $s['subline'] }}</p>

            <div class="relative mt-4 grid grid-cols-3 gap-2">
                <div class="rounded-2xl bg-white/15 backdrop-blur-sm p-3">
                    <p class="h-display text-xl leading-none text-white">+{{ number_format($s['xp']) }}</p>
                    <p class="text-[10px] text-white/85 mt-1">{{ __('reports.xp_earned') }}</p>
                </div>
                <div class="rounded-2xl bg-white/15 backdrop-blur-sm p-3">
                    <p class="h-display text-xl leading-none text-white">{{ $s['packs'] }}</p>
                    <p class="text-[10px] text-white/85 mt-1">{{ __('reports.lessons') }}</p>
                </div>
                <div class="rounded-2xl bg-white/15 backdrop-blur-sm p-3">
                    <p class="h-display text-xl leading-none text-white">{{ $s['accuracy'] !== null ? $s['accuracy'].'%' : '—' }}</p>
                    <p class="text-[10px] text-white/85 mt-1">{{ __('reports.accuracy') }}</p>
                </div>
            </div>

            <div class="relative mt-4 flex items-center gap-2 flex-wrap">
                <span class="chip bg-white/20 border-0 text-white"><i class="ph-fill ph-trend-up"></i> {{ $s['paceChip'] }}</span>
                <span class="chip bg-white/20 border-0 text-white ml-auto"><i class="ph-fill ph-calendar"></i> {{ $s['daysChip'] }}</span>
            </div>
        </div>
    </section>

    <section class="px-5 mt-5">
        <div class="section-head">
            <h2 class="h-display text-lg">{{ $s['chartTitle'] }}</h2>
            <button type="button" wire:click="$set('compareSheet', true)" class="link"><i class="ph ph-git-diff"></i> {{ __('reports.compare') }}</button>
        </div>
        <div class="k-card p-3 mt-3">
            <div class="flex items-end gap-1 h-32">
                @foreach ($s['trend'] as $bar)
                    <div class="grow flex flex-col items-center justify-end h-full gap-1 min-w-0">
                        <div class="w-full rounded-t-md bg-[linear-gradient(180deg,#8E72FF,#49B8FF)]" style="height: {{ max(8, $bar['height']) }}%"></div>
                        <span class="text-[9px] font-extrabold text-muted truncate w-full text-center">{{ $bar['label'] }}</span>
                    </div>
                @endforeach
            </div>
            <p class="text-[11px] text-muted mt-2">{{ $s['minutesHint'] }}</p>
        </div>
    </section>

    <section class="px-5 mt-5">
        <p class="section-label">{{ __('reports.key_metrics') }}</p>
        <div class="mt-3 grid grid-cols-2 gap-3">
            @foreach ($s['kpis'] as $i => $kpiRow)
                <button type="button" wire:click="$set('openKpi', {{ $i }}); $set('kpiSheet', true)" class="k-card p-4 text-left">
                    <div class="size-10 rounded-2xl {{ $kpiRow['tile'] }} grid place-items-center text-xl">{{ $kpiRow['emoji'] }}</div>
                    <p class="text-[11px] text-muted mt-2">{{ $kpiRow['name'] }}</p>
                    <p class="h-display text-2xl text-ink leading-none mt-1">{{ $kpiRow['value'] }} <span class="text-sm text-muted">{{ $kpiRow['unit'] }}</span></p>
                    <p class="text-[11px] text-mint-ink mt-1">{{ $kpiRow['delta'] }}</p>
                </button>
            @endforeach
        </div>
    </section>

    <section class="px-5 mt-5">
        <div class="section-head">
            <p class="section-label">{{ __('reports.by_subject') }}</p>
            <span class="link cursor-default">{{ __('reports.xp_by_subject', ['xp' => number_format($s['xp'])]) }}</span>
        </div>
        <div class="k-card p-4 mt-3 space-y-3">
            @foreach ($s['subjects'] as $row)
                <a href="{{ $row['href'] }}" wire:navigate class="flex items-center gap-2 w-full text-left">
                    <span class="size-9 rounded-xl {{ $row['tile'] }} grid place-items-center text-lg shrink-0">{{ $row['emoji'] }}</span>
                    <span class="grow min-w-0">
                        <span class="flex items-center justify-between">
                            <span class="font-extrabold text-ink">{{ $row['label'] }}</span>
                            <span class="text-muted text-xs">{{ __('reports.subject_meta', ['packs' => $row['packs'], 'pct' => $row['masteryPercent']]) }}</span>
                        </span>
                        <span class="progress {{ $row['progressClass'] }} mt-1 block"><span style="width: {{ $row['barPercent'] }}%"></span></span>
                    </span>
                </a>
            @endforeach
        </div>
    </section>

    <section class="px-5 mt-5">
        <div class="section-head">
            <p class="section-label">{{ __('reports.timeline') }}</p>
            <a href="{{ route('badges') }}" wire:navigate class="link">{{ __('reports.see_all') }}</a>
        </div>
        <div class="space-y-2 mt-3">
            @forelse ($s['timeline'] as $i => $item)
                <button type="button" wire:click="$set('openEvent', {{ $i }}); $set('eventSheet', true)" class="setting-row w-full text-left">
                    <div class="setting-ico {{ $item['tile'] }}"><span class="text-lg">{{ $item['emoji'] }}</span></div>
                    <div class="grow min-w-0">
                        <p class="setting-text font-extrabold text-sm text-ink">{{ $item['title'] }}</p>
                        <p class="text-[11px] text-muted">{{ $item['subtitle'] }}</p>
                    </div>
                    <span class="chip chip-mint">{{ $item['chip'] }}</span>
                </button>
            @empty
                <p class="text-xs text-muted px-1">{{ __('reports.no_timeline') }}</p>
            @endforelse
        </div>
    </section>

    <section class="px-5 mt-5">
        <p class="section-label">{{ __('reports.insights') }}</p>
        <div class="mt-3 space-y-2">
            @foreach ($s['insights'] as $item)
                <div class="tip-card rounded-2xl p-3 flex items-start gap-3">
                    <div class="mascot shrink-0 size-10 text-base">{{ $item['emoji'] }}</div>
                    <div class="grow">
                        <p class="font-extrabold text-sm text-ink">{{ $item['title'] }}</p>
                        <p class="text-[11px] text-muted">{{ $item['body'] }}</p>
                    </div>
                    <span class="chip chip-mint shrink-0">{{ $item['chip'] }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <div class="mt-auto px-5 pb-8 pt-6 safe-bottom space-y-2">
        @if ($emailHint !== '')
            <p class="text-[11px] text-center text-mint-ink">{{ $emailHint }}</p>
        @endif
        <a href="{{ route('export-progress', ['range' => $scope]) }}" wire:navigate class="btn btn-primary w-full">
            <i class="ph-fill ph-download-simple"></i> {{ __('reports.export_full_pdf') }}
        </a>
        <a href="{{ route('weekly-report') }}" wire:navigate class="btn btn-ghost w-full">
            <i class="ph ph-calendar"></i> {{ __('reports.this_week_only') }}
        </a>
    </div>

<div class="{{ $compareSheet ? '' : 'hidden' }} fixed inset-0 z-50" role="dialog" aria-modal="true">
    <button type="button" wire:click="$set('compareSheet', false)" class="absolute inset-0 size-full bg-black/50 backdrop-blur-sm" aria-label="{{ __('reports.close') }}"></button>
    <div class="absolute left-0 right-0 bottom-0 mx-auto max-w-[430px] bg-surface rounded-t-3xl border-t border-token shadow-2xl safe-bottom">
        <div class="flex justify-center pt-3"><span class="block w-10 h-1.5 rounded-full bg-[var(--color-k-border)]"></span></div>
        <div class="px-5 pt-4 pb-6">
            <div class="flex items-start gap-3">
                <div class="size-12 rounded-2xl tile-sky grid place-items-center text-2xl shrink-0">↔️</div>
                <div class="grow min-w-0">
                    <p class="h-display text-xl leading-tight text-ink">{{ __('reports.compare_title') }}</p>
                    <p class="text-xs text-muted mt-1">{{ __('reports.compare_sub') }}</p>
                </div>
                <button type="button" wire:click="$set('compareSheet', false)" class="icon-btn shrink-0" aria-label="{{ __('reports.close') }}"><i class="ph ph-x"></i></button>
            </div>
            <div class="mt-5 k-card p-0 overflow-hidden">
                <div class="grid grid-cols-2 text-center">
                    <div class="p-3 border-r border-token"><p class="text-[11px] text-muted">{{ __('reports.compare_now') }}</p><p class="h-display text-lg text-ink mt-1">{{ number_format($s['xp']) }}</p></div>
                    <div class="p-3"><p class="text-[11px] text-muted">{{ __('reports.compare_prev') }}</p><p class="h-display text-lg text-ink mt-1">{{ $s['hasPrevious'] ? (($s['vs'] >= 0 ? '+' : '').$s['vs'].'%') : '—' }}</p></div>
                </div>
            </div>
            <p class="text-[11px] text-muted mt-3">{{ $s['rangeLabel'] }}</p>
        </div>
    </div>
</div>

<div class="{{ $kpiSheet && $kpi ? '' : 'hidden' }} fixed inset-0 z-50" role="dialog" aria-modal="true">
    <button type="button" wire:click="$set('kpiSheet', false)" class="absolute inset-0 size-full bg-black/50 backdrop-blur-sm" aria-label="{{ __('reports.close') }}"></button>
    <div class="absolute left-0 right-0 bottom-0 mx-auto max-w-[430px] bg-surface rounded-t-3xl border-t border-token shadow-2xl safe-bottom">
        <div class="flex justify-center pt-3"><span class="block w-10 h-1.5 rounded-full bg-[var(--color-k-border)]"></span></div>
        @if ($kpi)
            <div class="px-5 pt-4 pb-6 text-center">
                <div class="mx-auto size-20 rounded-2xl {{ $kpi['tile'] }} grid place-items-center text-4xl">{{ $kpi['emoji'] }}</div>
                <p class="h-display text-2xl mt-3 text-ink">{{ $kpi['name'] }}</p>
                <p class="h-display text-5xl text-ink mt-2">{{ $kpi['value'] }}</p>
                <p class="text-xs text-mint-ink mt-1">{{ $kpi['delta'] }}</p>
                <p class="text-sm text-muted mt-3 max-w-[300px] mx-auto">{{ $kpi['body'] }}</p>
                <button type="button" wire:click="$set('kpiSheet', false)" class="btn btn-primary w-full mt-5">{{ __('reports.got_it') }}</button>
            </div>
        @endif
    </div>
</div>

<div class="{{ $eventSheet && $event ? '' : 'hidden' }} fixed inset-0 z-50" role="dialog" aria-modal="true">
    <button type="button" wire:click="$set('eventSheet', false)" class="absolute inset-0 size-full bg-black/50 backdrop-blur-sm" aria-label="{{ __('reports.close') }}"></button>
    <div class="absolute left-0 right-0 bottom-0 mx-auto max-w-[430px] bg-surface rounded-t-3xl border-t border-token shadow-2xl safe-bottom">
        <div class="flex justify-center pt-3"><span class="block w-10 h-1.5 rounded-full bg-[var(--color-k-border)]"></span></div>
        @if ($event)
            <div class="px-5 pt-4 pb-6 text-center">
                <div class="mx-auto size-20 rounded-2xl {{ $event['tile'] }} grid place-items-center text-4xl">{{ $event['emoji'] }}</div>
                <p class="h-display text-2xl mt-3 text-ink">{{ $event['title'] }}</p>
                <p class="text-xs text-muted mt-1">{{ $event['subtitle'] }}</p>
                <p class="text-sm text-muted mt-3 max-w-[300px] mx-auto">{{ $event['body'] }}</p>
                <button type="button" wire:click="$set('eventSheet', false)" class="btn btn-primary w-full mt-5">{{ __('reports.got_it') }}</button>
            </div>
        @endif
    </div>
</div>

<div class="{{ $shareSheet ? '' : 'hidden' }} fixed inset-0 z-50" role="dialog" aria-modal="true">
    <button type="button" wire:click="$set('shareSheet', false)" class="absolute inset-0 size-full bg-black/50 backdrop-blur-sm" aria-label="{{ __('reports.close') }}"></button>
    <div class="absolute left-0 right-0 bottom-0 mx-auto max-w-[430px] bg-surface rounded-t-3xl border-t border-token shadow-2xl safe-bottom">
        <div class="flex justify-center pt-3"><span class="block w-10 h-1.5 rounded-full bg-[var(--color-k-border)]"></span></div>
        <div class="px-5 pt-4 pb-6">
            <div class="flex items-start gap-3">
                <div class="size-12 rounded-2xl tile-violet grid place-items-center text-2xl shrink-0">💌</div>
                <div class="grow min-w-0">
                    <p class="h-display text-xl leading-tight text-ink">{{ __('reports.share_full') }}</p>
                    <p class="text-xs text-muted mt-1">{{ __('reports.share_sub') }}</p>
                </div>
                <button type="button" wire:click="$set('shareSheet', false)" class="icon-btn shrink-0" aria-label="{{ __('reports.close') }}"><i class="ph ph-x"></i></button>
            </div>
            <div class="mt-4 grid grid-cols-4 gap-3">
                <button type="button" wire:click="sendEmail" class="k-card p-3 text-center">
                    <div class="size-10 mx-auto rounded-2xl tile-coral grid place-items-center"><i class="ph-fill ph-envelope-simple text-xl"></i></div>
                    <p class="text-[11px] font-extrabold text-ink mt-2 leading-tight">{{ __('reports.share_email') }}</p>
                </button>
                <a href="https://wa.me/?text={{ urlencode($s['shareText']) }}" target="_blank" rel="noopener" class="k-card p-3 text-center">
                    <div class="size-10 mx-auto rounded-2xl tile-mint grid place-items-center"><i class="ph-fill ph-whatsapp-logo text-xl"></i></div>
                    <p class="text-[11px] font-extrabold text-ink mt-2 leading-tight">{{ __('reports.share_whatsapp') }}</p>
                </a>
                <a href="sms:?body={{ urlencode($s['shareText']) }}" class="k-card p-3 text-center">
                    <div class="size-10 mx-auto rounded-2xl tile-sky grid place-items-center"><i class="ph-fill ph-chat-circle text-xl"></i></div>
                    <p class="text-[11px] font-extrabold text-ink mt-2 leading-tight">{{ __('reports.share_messages') }}</p>
                </a>
                <button type="button" class="k-card p-3 text-center" onclick="navigator.clipboard.writeText(location.href)">
                    <div class="size-10 mx-auto rounded-2xl tile-sun grid place-items-center"><i class="ph-fill ph-link text-xl"></i></div>
                    <p class="text-[11px] font-extrabold text-ink mt-2 leading-tight">{{ __('reports.share_copy') }}</p>
                </button>
            </div>
        </div>
    </div>
</div>
</main>
