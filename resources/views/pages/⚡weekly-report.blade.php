<?php

use App\Repositories\UserRepository;
use App\Services\ProgressReportService;
use Livewire\Component;

new class extends Component
{
    public string $weekStart = '';

    public ?string $selectedDate = null;

    public bool $weekSheet = false;

    public bool $daySheet = false;

    public bool $hlSheet = false;

    public bool $shareSheet = false;

    public bool $emailSheet = false;

    public bool $emailWeekly = true;

    public string $copiedHint = '';

    public string $emailHint = '';

    public int $openHighlight = -1;

    public function title(): string
    {
        return __('reports.weekly_title');
    }

    public function rendering(\Illuminate\View\View $view): void
    {
        $view->title($this->title());
    }

    public function mount(ProgressReportService $reports, UserRepository $users): void
    {
        $snap = $reports->weekSnapshot($users->authenticated());
        $this->weekStart = $reports->currentWeekStart($users->authenticated())->toDateString();
        $this->emailWeekly = $snap->emailWeekly;
    }

    public function pickWeek(string $start): void
    {
        $this->weekStart = $start;
        $this->selectedDate = null;
        $this->weekSheet = false;
        $this->daySheet = false;
    }

    public function pickDay(string $date): void
    {
        $this->selectedDate = $date;
        $this->daySheet = true;
    }

    public function showHighlight(int $index): void
    {
        $this->openHighlight = $index;
        $this->hlSheet = true;
    }

    public function updatedEmailWeekly(bool $value): void
    {
        app(ProgressReportService::class)->setWeeklyEmail(
            app(UserRepository::class)->authenticated(),
            $value,
        );
        $this->emailHint = (string) __('screen-time.saved');
    }

    public function sendEmail(): void
    {
        app(ProgressReportService::class)->emailWeek(
            app(UserRepository::class)->authenticated(),
            $this->weekStart,
        );
        $this->shareSheet = false;
        $this->emailHint = (string) __('reports.emailed');
    }

    /**
     * @return array<string, mixed>
     */
    public function snap(): array
    {
        $user = app(UserRepository::class)->authenticated();
        $snap = app(ProgressReportService::class)->weekSnapshot($user, $this->weekStart, $this->selectedDate);

        return [
            'kidName' => $snap->kidName,
            'parentEmail' => $snap->parentEmail,
            'headline' => $snap->headline,
            'subline' => $snap->subline,
            'weekChip' => $snap->weekChip,
            'rangeLabel' => $snap->figures->rangeLabel,
            'xp' => $snap->figures->xp,
            'packs' => $snap->figures->packs,
            'activeDays' => $snap->figures->activeDays,
            'goalXp' => $snap->figures->goalXp,
            'goalPercent' => $snap->figures->goalPercent,
            'barPercent' => min(100, $snap->figures->goalPercent),
            'days' => array_map(fn ($d) => [
                'date' => $d->date,
                'letter' => $d->letter,
                'name' => $d->name,
                'xp' => $d->xp,
                'minutes' => $d->minutes,
                'packs' => $d->packs,
                'topSubject' => $d->topSubject,
                'today' => $d->today,
                'selected' => $d->selected,
            ], $snap->days),
            'chart' => array_map(fn ($d) => ['label' => $d->letter, 'value' => $d->xp], $snap->days),
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
            ], $snap->subjects),
            'highlights' => array_map(fn ($h) => [
                'title' => $h->title,
                'subtitle' => $h->subtitle,
                'body' => $h->body,
                'emoji' => $h->emoji,
                'tile' => $h->tile,
                'chip' => $h->chip,
                'ctaHref' => $h->ctaHref,
                'ctaLabel' => $h->ctaLabel,
            ], $snap->highlights),
            'concerns' => array_map(fn ($h) => [
                'title' => $h->title,
                'body' => $h->body,
                'emoji' => $h->emoji,
                'chip' => $h->chip,
                'ctaHref' => $h->ctaHref,
            ], $snap->concerns),
            'weekOptions' => array_map(fn ($w) => [
                'start' => $w->start,
                'label' => $w->label,
                'rangeLabel' => $w->rangeLabel,
                'xp' => $w->xp,
                'activeDays' => $w->activeDays,
                'current' => $w->current,
            ], $snap->weekOptions),
            'selectedDay' => $snap->selectedDay === null ? null : [
                'name' => $snap->selectedDay->name,
                'xp' => $snap->selectedDay->xp,
                'minutes' => $snap->selectedDay->minutes,
                'packs' => $snap->selectedDay->packs,
                'topSubject' => $snap->selectedDay->topSubject,
            ],
            'selectedActivities' => $snap->selectedActivities,
            'totalMinutesLabel' => $snap->totalMinutesLabel,
            'minutesHint' => $snap->minutesHint,
            'shareText' => $snap->headline.' · '.$snap->subline,
        ];
    }
};
?>

@php $s = $this->snap(); $hl = $s['highlights'][$openHighlight] ?? null; @endphp

<main class="device-frame min-h-screen flex flex-col safe-top">

    <header class="appbar">
        <a href="{{ route('parent-controls') }}" class="icon-btn" data-back aria-label="{{ __('reports.back') }}"><i class="ph ph-caret-left"></i></a>
        <div class="grow">
            <p class="text-xs text-muted">{{ $s['rangeLabel'] }} · {{ $s['parentEmail'] }}</p>
            <h1 class="h-display text-lg leading-tight">{{ __('reports.heading') }}</h1>
        </div>
        <button type="button" wire:click="$set('weekSheet', true)" class="chip chip-primary"><i class="ph-fill ph-calendar"></i> <span>{{ $s['weekChip'] }}</span></button>
        <button type="button" wire:click="$set('shareSheet', true)" class="icon-btn" aria-label="{{ __('reports.share') }}"><i class="ph ph-share-fat text-xl"></i></button>
    </header>

    <section class="px-5">
        <div class="k-card-lg hero-friends text-center">
            <div class="relative inline-grid place-items-center">
                <div class="size-24 rounded-3xl bg-white/20 backdrop-blur-sm grid place-items-center text-5xl">📊</div>
            </div>
            <p class="relative chip bg-white/20 border-0 text-white mt-4">
                <i class="ph-fill ph-sparkle"></i> {{ __('reports.this_week') }}
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
                    <p class="h-display text-xl leading-none text-white">{{ $s['activeDays'] }}/7</p>
                    <p class="text-[10px] text-white/85 mt-1">{{ __('reports.active_days') }}</p>
                </div>
            </div>

            <div class="relative mt-4 flex items-center gap-3">
                <div class="progress on-gradient grow"><span class="transition-all duration-700" style="width: {{ $s['barPercent'] }}%"></span></div>
                <span class="text-sm font-extrabold shrink-0 text-white">{{ $s['goalPercent'] }}%</span>
            </div>
            <p class="relative text-[11px] text-white/85 mt-1">{{ __('reports.weekly_goal', ['xp' => number_format($s['goalXp'])]) }}</p>
        </div>
    </section>

    <section class="px-5 mt-5">
        <div class="section-head">
            <p class="section-label">{{ __('reports.daily_xp') }}</p>
            <span class="link cursor-default">{{ __('reports.tap_day') }}</span>
        </div>
        <div class="k-card text-ink mt-3 p-3">
            <div id="line" wire:ignore>
                <div class="flex items-end gap-1 h-28">
                    @foreach ($s['chart'] as $point)
                        <div class="grow flex flex-col items-center justify-end h-full gap-1">
                            <div class="w-full rounded-t-md bg-[linear-gradient(180deg,#8E72FF,#49B8FF)]" style="height: {{ max(8, $s['chart'] === [] ? 8 : (int) round(($point['value'] / max(1, max(array_column($s['chart'], 'value')))) * 100)) }}%"></div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="mt-3 grid grid-cols-7 gap-1">
                @foreach ($s['days'] as $day)
                    <button type="button" wire:click="pickDay('{{ $day['date'] }}')" class="chip text-[11px] !py-1 text-center {{ $day['selected'] || $day['today'] ? 'chip-primary' : '' }}">{{ $day['letter'] }}</button>
                @endforeach
            </div>
        </div>
    </section>

    <section class="px-5 mt-5">
        <p class="section-label">{{ __('reports.highlights') }}</p>
        <div class="mt-3 space-y-2">
            @forelse ($s['highlights'] as $i => $item)
                <button type="button" wire:click="showHighlight({{ $i }})" class="setting-row w-full text-left">
                    <div class="setting-ico {{ $item['tile'] }}"><span class="text-lg">{{ $item['emoji'] }}</span></div>
                    <div class="grow min-w-0">
                        <p class="setting-text font-extrabold text-sm text-ink">{{ $item['title'] }}</p>
                        <p class="text-[11px] text-muted">{{ $item['subtitle'] }}</p>
                    </div>
                    <span class="chip chip-mint">{{ $item['chip'] }}</span>
                </button>
            @empty
                <p class="text-xs text-muted px-1">{{ __('reports.no_highlights') }}</p>
            @endforelse
        </div>
    </section>

    <section class="px-5 mt-5">
        <div class="section-head">
            <p class="section-label">{{ __('reports.where_time') }}</p>
            <span class="link cursor-default">{{ __('reports.minutes_total', ['minutes' => $s['totalMinutesLabel']]) }}</span>
        </div>
        <p class="text-[11px] text-muted mt-1">{{ $s['minutesHint'] }}</p>
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
        <p class="section-label">{{ __('reports.things_to_watch') }}</p>
        <div class="mt-3 space-y-2">
            @foreach ($s['concerns'] as $item)
                <div class="tip-card rounded-2xl p-3 flex items-start gap-3">
                    <div class="mascot shrink-0 size-10 text-base">{{ $item['emoji'] }}</div>
                    <div class="grow">
                        <p class="font-extrabold text-sm text-ink">{{ $item['title'] }}</p>
                        <p class="text-[11px] text-muted">{{ $item['body'] }}</p>
                    </div>
                    <a href="{{ $item['ctaHref'] }}" wire:navigate class="chip chip-primary shrink-0">{{ $item['chip'] }}</a>
                </div>
            @endforeach
        </div>
    </section>

    <section class="px-5 mt-5">
        <p class="section-label">{{ __('reports.email_delivery') }}</p>
        <div class="mt-3 space-y-2">
            <label class="setting-row cursor-pointer">
                <div class="setting-ico tile-violet"><i class="ph-fill ph-envelope-simple"></i></div>
                <div class="grow min-w-0">
                    <p class="setting-text font-extrabold text-sm text-ink">{{ __('reports.email_weekly') }}</p>
                    <p class="text-[11px] text-muted">{{ __('reports.email_weekly_hint') }}</p>
                </div>
                <span class="ks-switch">
                    <input type="checkbox" wire:model.live="emailWeekly"/>
                    <span class="track"></span>
                    <span class="thumb"></span>
                </span>
            </label>
            <button type="button" wire:click="$set('emailSheet', true)" class="setting-row w-full text-left">
                <div class="setting-ico tile-sky"><i class="ph-fill ph-user"></i></div>
                <div class="grow min-w-0">
                    <p class="setting-text font-extrabold text-sm text-ink">{{ __('reports.email_address') }}</p>
                    <p class="text-[11px] text-muted">{{ $s['parentEmail'] }}</p>
                </div>
                <i class="ph ph-caret-right text-muted"></i>
            </button>
            @if ($emailHint !== '')
                <p class="text-[11px] text-mint-ink px-1">{{ $emailHint }}</p>
            @endif
        </div>
    </section>

    <div class="mt-auto px-5 pb-8 pt-6 safe-bottom space-y-2">
        <a href="{{ route('export-progress', ['range' => 'week']) }}" wire:navigate class="btn btn-primary w-full">
            <i class="ph-fill ph-download-simple"></i> {{ __('reports.download_pdf') }}
        </a>
        <p class="text-[11px] text-center text-muted">{{ __('reports.reports_privacy') }}</p>
    </div>

<div class="{{ $weekSheet ? '' : 'hidden' }} fixed inset-0 z-50" role="dialog" aria-modal="true">
    <button type="button" wire:click="$set('weekSheet', false)" class="absolute inset-0 size-full bg-black/50 backdrop-blur-sm" aria-label="{{ __('reports.close') }}"></button>
    <div class="absolute left-0 right-0 bottom-0 mx-auto max-w-[430px] bg-surface rounded-t-3xl border-t border-token shadow-2xl safe-bottom">
        <div class="flex justify-center pt-3"><span class="block w-10 h-1.5 rounded-full bg-[var(--color-k-border)]"></span></div>
        <div class="px-5 pt-4 pb-6">
            <div class="flex items-start gap-3">
                <div class="size-12 rounded-2xl tile-violet grid place-items-center text-2xl shrink-0">📅</div>
                <div class="grow min-w-0">
                    <p class="h-display text-xl leading-tight text-ink">{{ __('reports.pick_week') }}</p>
                    <p class="text-xs text-muted mt-1">{{ __('reports.pick_week_sub') }}</p>
                </div>
                <button type="button" wire:click="$set('weekSheet', false)" class="icon-btn shrink-0" aria-label="{{ __('reports.close') }}"><i class="ph ph-x"></i></button>
            </div>
            <div class="mt-4 space-y-2">
                @foreach ($s['weekOptions'] as $opt)
                    <button type="button" wire:click="pickWeek('{{ $opt['start'] }}')" class="setting-row w-full text-left">
                        <div class="setting-ico {{ $opt['current'] ? 'tile-sun' : 'tile-mint' }}"><i class="ph-fill ph-calendar-dots"></i></div>
                        <div class="grow min-w-0">
                            <p class="setting-text font-extrabold text-sm text-ink">{{ $opt['label'] }} · {{ $opt['rangeLabel'] }}</p>
                            <p class="text-[11px] text-muted">{{ __('reports.week_opt_meta', ['xp' => number_format($opt['xp']), 'days' => $opt['activeDays']]) }}</p>
                        </div>
                        <span class="chip {{ $opt['start'] === $weekStart ? 'chip-primary' : '' }}">{{ $opt['current'] ? __('reports.chip_active') : __('reports.chip_past') }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="{{ $daySheet && $s['selectedDay'] ? '' : 'hidden' }} fixed inset-0 z-50" role="dialog" aria-modal="true">
    <button type="button" wire:click="$set('daySheet', false)" class="absolute inset-0 size-full bg-black/50 backdrop-blur-sm" aria-label="{{ __('reports.close') }}"></button>
    <div class="absolute left-0 right-0 bottom-0 mx-auto max-w-[430px] bg-surface rounded-t-3xl border-t border-token shadow-2xl safe-bottom">
        <div class="flex justify-center pt-3"><span class="block w-10 h-1.5 rounded-full bg-[var(--color-k-border)]"></span></div>
        @if ($s['selectedDay'])
            <div class="px-5 pt-4 pb-6">
                <div class="flex items-start gap-3">
                    <div class="size-12 rounded-2xl tile-mint grid place-items-center text-2xl shrink-0">🗓️</div>
                    <div class="grow min-w-0">
                        <p class="h-display text-xl leading-tight text-ink">{{ $s['selectedDay']['name'] }}</p>
                        <p class="text-xs text-muted mt-0.5">{{ __('reports.day_meta', [
                            'xp' => $s['selectedDay']['xp'],
                            'top' => $s['selectedDay']['topSubject'] !== '' ? __('reports.day_top', ['subject' => $s['selectedDay']['topSubject']]) : '',
                        ]) }}</p>
                    </div>
                    <button type="button" wire:click="$set('daySheet', false)" class="icon-btn shrink-0" aria-label="{{ __('reports.close') }}"><i class="ph ph-x"></i></button>
                </div>
                <div class="mt-5 grid grid-cols-3 gap-2 text-center">
                    <div class="k-card p-3"><p class="h-display text-ink">{{ $s['selectedDay']['xp'] }}</p><p class="text-[11px] text-muted mt-1">{{ __('reports.day_xp') }}</p></div>
                    <div class="k-card p-3"><p class="h-display text-ink">{{ $s['selectedDay']['minutes'] ?? '—' }}</p><p class="text-[11px] text-muted mt-1">{{ __('reports.day_min') }}</p></div>
                    <div class="k-card p-3"><p class="h-display text-ink">{{ $s['selectedDay']['packs'] }}</p><p class="text-[11px] text-muted mt-1">{{ __('reports.day_lessons') }}</p></div>
                </div>
                <p class="section-label mt-5">{{ __('reports.top_activities') }}</p>
                <div class="mt-2 space-y-2">
                    @forelse ($s['selectedActivities'] as $act)
                        <div class="setting-row">
                            <div class="setting-ico tile-sky"><i class="ph-fill ph-book-open"></i></div>
                            <div class="grow min-w-0">
                                <p class="setting-text font-extrabold text-sm text-ink">{{ $act['title'] }}</p>
                                <p class="text-[11px] text-muted">{{ $act['subtitle'] }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-muted">{{ __('reports.no_packs_day') }}</p>
                    @endforelse
                </div>
            </div>
        @endif
    </div>
</div>

<div class="{{ $hlSheet && $hl ? '' : 'hidden' }} fixed inset-0 z-50" role="dialog" aria-modal="true">
    <button type="button" wire:click="$set('hlSheet', false)" class="absolute inset-0 size-full bg-black/50 backdrop-blur-sm" aria-label="{{ __('reports.close') }}"></button>
    <div class="absolute left-0 right-0 bottom-0 mx-auto max-w-[430px] bg-surface rounded-t-3xl border-t border-token shadow-2xl safe-bottom">
        <div class="flex justify-center pt-3"><span class="block w-10 h-1.5 rounded-full bg-[var(--color-k-border)]"></span></div>
        @if ($hl)
            <div class="px-5 pt-4 pb-6 text-center">
                <div class="mx-auto size-20 rounded-2xl {{ $hl['tile'] }} grid place-items-center text-4xl">{{ $hl['emoji'] }}</div>
                <p class="h-display text-2xl mt-3 text-ink">{{ $hl['title'] }}</p>
                <p class="text-sm text-muted mt-2 max-w-[300px] mx-auto">{{ $hl['body'] }}</p>
                <div class="mt-5 grid grid-cols-2 gap-2">
                    <button type="button" wire:click="$set('hlSheet', false)" class="btn btn-ghost">{{ __('reports.close') }}</button>
                    @if ($hl['ctaHref'])
                        <a href="{{ $hl['ctaHref'] }}" wire:navigate class="btn btn-primary"><i class="ph-fill ph-arrow-right"></i> {{ $hl['ctaLabel'] }}</a>
                    @endif
                </div>
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
                    <p class="h-display text-xl leading-tight text-ink">{{ __('reports.share_week', ['name' => $s['kidName']]) }}</p>
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
            <p class="text-[11px] text-center text-muted mt-4">{{ __('reports.share_anon') }}</p>
        </div>
    </div>
</div>

<div class="{{ $emailSheet ? '' : 'hidden' }} fixed inset-0 z-50" role="dialog" aria-modal="true">
    <button type="button" wire:click="$set('emailSheet', false)" class="absolute inset-0 size-full bg-black/50 backdrop-blur-sm" aria-label="{{ __('reports.close') }}"></button>
    <div class="absolute left-0 right-0 bottom-0 mx-auto max-w-[430px] bg-surface rounded-t-3xl border-t border-token shadow-2xl safe-bottom">
        <div class="flex justify-center pt-3"><span class="block w-10 h-1.5 rounded-full bg-[var(--color-k-border)]"></span></div>
        <div class="px-5 pt-4 pb-6">
            <div class="flex items-start gap-3">
                <div class="size-12 rounded-2xl tile-sky grid place-items-center text-2xl shrink-0">✉️</div>
                <div class="grow min-w-0">
                    <p class="h-display text-xl leading-tight text-ink">{{ __('reports.edit_email') }}</p>
                    <p class="text-xs text-muted mt-1">{{ __('reports.edit_email_sub') }}</p>
                </div>
                <button type="button" wire:click="$set('emailSheet', false)" class="icon-btn shrink-0" aria-label="{{ __('reports.close') }}"><i class="ph ph-x"></i></button>
            </div>
            <div class="mt-4">
                <label class="text-[11px] font-extrabold text-muted uppercase tracking-wide">{{ __('reports.parent_email') }}</label>
                <div class="input-wrap mt-1">
                    <i class="ph ph-envelope-simple i-left"></i>
                    <input type="email" class="input has-left" value="{{ $s['parentEmail'] }}" readonly/>
                </div>
                <p class="text-[10px] text-muted mt-1">{{ __('reports.email_locked') }}</p>
            </div>
            <button type="button" wire:click="$set('emailSheet', false)" class="btn btn-primary w-full mt-5">{{ __('reports.got_it') }}</button>
        </div>
    </div>
</div>
</main>
