<?php

use App\Enums\ReportScope;
use App\Repositories\UserRepository;
use App\Services\AccountService;
use App\Services\ProgressPdfService;
use App\Services\ProgressReportService;
use Illuminate\Support\Js;
use Livewire\Component;

new class extends Component
{
    public string $range = 'month';

    public string $format = 'pdf';

    public bool $includeXp = true;

    public bool $includeStreak = true;

    public bool $includeBadges = true;

    public bool $includeLessons = true;

    public bool $includeLeague = false;

    public function title(): string
    {
        return __('reports.export_title');
    }

    public function rendering(\Illuminate\View\View $view): void
    {
        $view->title($this->title());
    }

    public function mount(): void
    {
        $fromQuery = request()->query('range');

        if (is_string($fromQuery) && ReportScope::tryFrom($fromQuery) instanceof ReportScope) {
            $this->range = $fromQuery;
        }
    }

    public function pickRange(string $range): void
    {
        if (ReportScope::tryFrom($range) instanceof ReportScope) {
            $this->range = $range;
        }
    }

    public function pickFormat(string $format): void
    {
        if (in_array($format, ['pdf', 'json'], true)) {
            $this->format = $format;
        }
    }

    public function download(ProgressPdfService $pdf, AccountService $accounts, UserRepository $users)
    {
        if ($this->format === 'json') {
            $accounts->emailDataExport($users->authenticated());
            $this->js('toast('.Js::from(__('account.export_json_sent')).')');

            return;
        }

        return $pdf->download(
            $users->authenticated(),
            ReportScope::from($this->range),
            $this->includeXp,
            $this->includeStreak,
            $this->includeBadges,
            $this->includeLessons,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function snap(): array
    {
        $export = app(ProgressReportService::class)->exportSnapshot(
            app(UserRepository::class)->authenticated(),
            ReportScope::from($this->range),
        );

        return [
            'kidName' => $export->kidName,
            'rangeLabel' => $export->rangeLabel,
            'coverKicker' => $export->coverKicker,
            'coverTitle' => $export->coverTitle,
            'coverMeta' => $export->coverMeta,
        ];
    }
};
?>

@php $s = $this->snap(); @endphp

<main class="device-frame min-h-screen flex flex-col safe-top">

    <header class="appbar">
        <a href="{{ route('parent-controls') }}" class="icon-btn" data-back aria-label="{{ __('reports.back') }}"><i class="ph ph-caret-left"></i></a>
        <div class="grow">
            <p class="text-xs text-muted">{{ __('reports.storage') }}</p>
            <h1 class="h-display text-lg leading-tight">{{ __('reports.export_heading') }}</h1>
        </div>
        <button class="icon-btn" data-theme-toggle aria-label="{{ __('reports.toggle_theme') }}"><i class="ph ph-moon text-xl"></i></button>
    </header>

    <section class="px-5">
        <div class="k-card-lg hero-friends text-center">
            <div class="relative inline-grid place-items-center">
                <div class="size-24 rounded-3xl bg-white/20 backdrop-blur-sm grid place-items-center text-5xl">📄</div>
            </div>
            <p class="relative chip bg-white/20 border-0 text-white mt-4">PDF · {{ __('reports.export_kicker') }}</p>
            <p class="relative h-display text-2xl mt-2 leading-tight">{{ $s['coverTitle'] }}</p>
            <p class="relative text-xs text-white/90 mt-1">{{ $s['coverMeta'] }}</p>
        </div>
    </section>

    <section class="px-5 mt-5">
        <p class="section-label">{{ __('reports.time_range') }}</p>
        <div class="mt-3 grid grid-cols-4 gap-2">
            @foreach (['week' => 'scope_week', 'month' => 'scope_month', 'season' => 'scope_season', 'all' => 'scope_all'] as $key => $label)
                <button type="button" wire:click="pickRange('{{ $key }}')" class="pick-card !p-2 !gap-0 flex-col text-center {{ $range === $key ? 'is-selected' : '' }}">
                    <span class="h-display text-sm text-ink">{{ __('reports.'.$label) }}</span>
                </button>
            @endforeach
        </div>
        <p class="text-[11px] text-muted text-center mt-2">{{ $s['rangeLabel'] }}</p>
    </section>

    <section class="px-5 mt-5">
        <p class="section-label">{{ __('reports.what_to_include') }}</p>
        <div class="mt-3 space-y-2">
            <label class="setting-row cursor-pointer">
                <div class="setting-ico tile-violet"><i class="ph-fill ph-chart-bar"></i></div>
                <div class="grow min-w-0">
                    <p class="setting-text font-extrabold text-sm text-ink">{{ __('reports.include_xp') }}</p>
                    <p class="text-[11px] text-muted">{{ __('reports.include_xp_hint') }}</p>
                </div>
                <span class="ks-switch"><input type="checkbox" wire:model.live="includeXp"/><span class="track"></span><span class="thumb"></span></span>
            </label>
            <label class="setting-row cursor-pointer">
                <div class="setting-ico tile-sun"><i class="ph-fill ph-fire"></i></div>
                <div class="grow min-w-0">
                    <p class="setting-text font-extrabold text-sm text-ink">{{ __('reports.include_streak') }}</p>
                    <p class="text-[11px] text-muted">{{ __('reports.include_streak_hint') }}</p>
                </div>
                <span class="ks-switch"><input type="checkbox" wire:model.live="includeStreak"/><span class="track"></span><span class="thumb"></span></span>
            </label>
            <label class="setting-row cursor-pointer">
                <div class="setting-ico tile-mint"><i class="ph-fill ph-medal"></i></div>
                <div class="grow min-w-0">
                    <p class="setting-text font-extrabold text-sm text-ink">{{ __('reports.include_badges') }}</p>
                    <p class="text-[11px] text-muted">{{ __('reports.include_badges_hint') }}</p>
                </div>
                <span class="ks-switch"><input type="checkbox" wire:model.live="includeBadges"/><span class="track"></span><span class="thumb"></span></span>
            </label>
            <label class="setting-row cursor-pointer">
                <div class="setting-ico tile-coral"><i class="ph-fill ph-book-open"></i></div>
                <div class="grow min-w-0">
                    <p class="setting-text font-extrabold text-sm text-ink">{{ __('reports.include_lessons') }}</p>
                    <p class="text-[11px] text-muted">{{ __('reports.include_lessons_hint') }}</p>
                </div>
                <span class="ks-switch"><input type="checkbox" wire:model.live="includeLessons"/><span class="track"></span><span class="thumb"></span></span>
            </label>
            <label class="setting-row cursor-pointer opacity-60">
                <div class="setting-ico tile-sky"><i class="ph-fill ph-trophy"></i></div>
                <div class="grow min-w-0">
                    <p class="setting-text font-extrabold text-sm text-ink">{{ __('reports.include_league') }}</p>
                    <p class="text-[11px] text-muted">{{ __('reports.include_league_hint') }}</p>
                </div>
                <span class="ks-switch"><input type="checkbox" wire:model="includeLeague" disabled/><span class="track"></span><span class="thumb"></span></span>
            </label>
        </div>
    </section>

    <section class="px-5 mt-5">
        <p class="section-label">{{ __('reports.export_format') }}</p>
        <div class="mt-3 grid grid-cols-3 gap-3">
            <button type="button" wire:click="pickFormat('pdf')" class="pick-card !p-3 flex-col text-center {{ $format === 'pdf' ? 'is-selected' : '' }}">
                <span class="text-3xl">📄</span>
                <span class="pc-name mt-1">{{ __('reports.fmt_pdf') }}</span>
                <span class="pc-sub">{{ __('reports.fmt_pdf_sub') }}</span>
            </button>
            <button type="button" class="pick-card !p-3 flex-col text-center opacity-50" disabled>
                <span class="text-3xl">📊</span>
                <span class="pc-name mt-1">{{ __('reports.fmt_csv') }}</span>
                <span class="pc-sub">{{ __('reports.fmt_later') }}</span>
            </button>
            <button type="button" wire:click="pickFormat('json')" class="pick-card !p-3 flex-col text-center {{ $format === 'json' ? 'is-selected' : '' }}">
                <span class="text-3xl">🌐</span>
                <span class="pc-name mt-1">{{ __('reports.fmt_json') }}</span>
                <span class="pc-sub">{{ __('reports.fmt_json_sub') }}</span>
            </button>
        </div>
        <p class="text-[11px] text-muted text-center mt-2">{{ __('reports.pdf_only_v1') }}</p>
    </section>

    <section class="px-5 mt-5">
        <p class="section-label">{{ __('reports.preview_cover') }}</p>
        <div class="k-card p-0 overflow-hidden mt-3">
            <div class="tile-violet p-5 text-center">
                <p class="text-[10px] font-extrabold uppercase tracking-wider opacity-80">{{ $s['coverKicker'] }}</p>
                <p class="h-display text-2xl mt-1">{{ $s['coverTitle'] }}</p>
                <p class="text-xs opacity-80 mt-0.5">{{ $s['coverMeta'] }}</p>
            </div>
            <div class="p-4 text-[12px] text-muted leading-relaxed">
                <p>{{ __('reports.export_blurb') }}</p>
            </div>
        </div>
    </section>

    <section class="px-5 mt-5 mb-10">
        <div class="tip-card rounded-2xl p-4 flex items-start gap-3">
            <div class="mascot shrink-0 size-11 text-xl">🦉</div>
            <div class="grow">
                <p class="font-extrabold text-sm text-ink">{{ __('reports.private_to_you') }}</p>
                <p class="text-xs text-muted">{{ __('reports.private_body') }}</p>
            </div>
        </div>
        <button type="button" wire:click="download" class="btn btn-primary w-full mt-3">
            <i class="ph-fill ph-download-simple"></i> {{ $format === 'json' ? __('reports.email_json_btn') : __('reports.download_pdf_btn') }}
        </button>
    </section>
</main>
