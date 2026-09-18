<?php

use App\Repositories\UserRepository;
use App\Services\ScreenTimeService;
use Livewire\Component;

new class extends Component
{
    public ?int $limitMinutes = null;

    public int $usedMinutes = 0;

    public ?int $remainingMinutes = null;

    public int $barPercent = 0;

    public bool $warnBeforeLimit = true;

    public bool $extraGrantedToday = false;

    public string $savedHint = '';

    public function title(): string
    {
        return __('screen-time.page_title');
    }

    public function rendering(\Illuminate\View\View $view): void
    {
        $view->title($this->title());
    }

    public function mount(ScreenTimeService $time, UserRepository $users): void
    {
        $this->fillFrom($time->snapshot($users->authenticated()));
    }

    public function pickLimit(int $minutes, ScreenTimeService $time, UserRepository $users): void
    {
        $next = $this->limitMinutes === $minutes ? null : $minutes;
        $time->setDailyLimit($users->authenticated(), $next);
        $this->fillFrom($time->snapshot($users->authenticated()));
        $this->savedHint = (string) __('screen-time.saved');
    }

    public function updatedWarnBeforeLimit(bool $value): void
    {
        $time = app(ScreenTimeService::class);
        $users = app(UserRepository::class);
        $time->setWarnBeforeLimit($users->authenticated(), $value);
        $this->fillFrom($time->snapshot($users->authenticated()));
    }

    public function grantExtra(ScreenTimeService $time, UserRepository $users): void
    {
        $time->grantExtension($users->authenticated());
        $this->fillFrom($time->snapshot($users->authenticated()));
        $this->savedHint = (string) __('screen-time.saved');
    }

    public function save(ScreenTimeService $time, UserRepository $users): void
    {
        $time->setDailyLimit($users->authenticated(), $this->limitMinutes);
        $time->setWarnBeforeLimit($users->authenticated(), $this->warnBeforeLimit);
        $this->fillFrom($time->snapshot($users->authenticated()));
        $this->savedHint = (string) __('screen-time.saved');
    }

    /**
     * @return list<int>
     */
    public function presets(): array
    {
        return ScreenTimeService::LIMIT_PRESETS;
    }

    public function usedLabel(): string
    {
        if ($this->limitMinutes === null) {
            return (string) $this->usedMinutes;
        }

        return (string) __('screen-time.used_of', [
            'used' => $this->usedMinutes,
            'limit' => $this->limitMinutes,
        ]);
    }

    public function leftoverLabel(): string
    {
        if ($this->limitMinutes === null) {
            return (string) __('screen-time.used_unlimited');
        }

        return (string) __('screen-time.used_left', [
            'minutes' => $this->remainingMinutes ?? 0,
        ]);
    }

    public function dayLimitLabel(): string
    {
        return $this->limitMinutes === null
            ? (string) __('screen-time.off')
            : $this->limitMinutes.' '.__('screen-time.min');
    }

    private function fillFrom(\App\Data\ScreenTimeSnapshot $snap): void
    {
        $this->limitMinutes = $snap->limitMinutes;
        $this->usedMinutes = $snap->usedTodayMinutes();
        $this->remainingMinutes = $snap->remainingMinutes();
        $this->barPercent = $snap->barPercent;
        $this->warnBeforeLimit = $snap->warnBeforeLimit;
        $this->extraGrantedToday = $snap->extraGrantedToday;
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top">

    <header class="appbar">
        <a href="{{ route('parent-controls') }}" class="icon-btn" data-back aria-label="{{ __('screen-time.back') }}"><i class="ph ph-caret-left"></i></a>
        <div class="grow">
            <p class="text-xs text-muted">{{ __('screen-time.parent_zone') }}</p>
            <h1 class="h-display text-lg leading-tight">{{ __('screen-time.heading') }}</h1>
        </div>
        <button type="button" class="icon-btn" data-theme-toggle aria-label="{{ __('screen-time.toggle_theme') }}"><i class="ph ph-moon text-xl"></i></button>
    </header>

    <section class="px-5">
        <div class="k-card-lg hero-profile text-center">
            <div class="relative inline-grid place-items-center">
                <div class="size-28 rounded-3xl bg-white/20 backdrop-blur-sm grid place-items-center">
                    <i class="ph-fill ph-timer text-6xl text-white"></i>
                </div>
            </div>
            <p class="relative chip bg-white/20 border-0 text-white mt-4">
                {{ __('screen-time.daily_limit_chip') }}
            </p>
            <p class="relative h-display text-5xl mt-2 leading-none" id="limitVal">{{ $limitMinutes ?? __('screen-time.off_value') }}</p>
            <p class="relative text-xs text-white/90 mt-1">{{ __('screen-time.minutes_per_day') }}</p>

            <div class="relative mt-4 flex items-center gap-3">
                <div class="progress on-gradient grow"><span id="limitBar" style="width: {{ $barPercent }}%"></span></div>
                <span class="text-sm font-extrabold shrink-0" id="usedText">{{ $this->usedLabel() }}</span>
            </div>
            <p class="relative text-[11px] text-white/85 mt-1">{{ $this->leftoverLabel() }}</p>
        </div>
    </section>

    <section class="px-5 mt-5">
        <p class="section-label">{{ __('screen-time.daily_time_limit') }}</p>
        <div class="mt-3 grid grid-cols-4 gap-2">
            @foreach ($this->presets() as $preset)
                <button type="button" class="pick-card !p-2 !gap-1 flex-col text-center{{ $limitMinutes === $preset ? ' is-selected' : '' }}" wire:click="pickLimit({{ $preset }})">
                    <span class="h-display text-base text-ink">{{ $preset }}</span>
                    <span class="text-[10px] text-muted font-extrabold">{{ __('screen-time.min') }}</span>
                </button>
            @endforeach
        </div>
    </section>

    <section class="px-5 mt-5">
        <p class="section-label">{{ __('screen-time.how_to_pause') }}</p>
        <div class="mt-3 space-y-2">
            <div class="setting-row">
                <div class="setting-ico tile-mint"><i class="ph-fill ph-hand-palm"></i></div>
                <div class="grow min-w-0">
                    <p class="setting-text font-extrabold text-sm text-ink">{{ __('screen-time.gentle_pause') }}</p>
                    <p class="text-[11px] text-muted">{{ __('screen-time.gentle_pause_hint') }}</p>
                </div>
                <span class="ks-switch">
                    <input type="checkbox" checked disabled/>
                    <span class="track"></span>
                    <span class="thumb"></span>
                </span>
            </div>
            <label class="setting-row cursor-pointer">
                <div class="setting-ico tile-sun"><i class="ph-fill ph-hourglass"></i></div>
                <div class="grow min-w-0">
                    <p class="setting-text font-extrabold text-sm text-ink">{{ __('screen-time.five_min_warning') }}</p>
                    <p class="text-[11px] text-muted">{{ __('screen-time.five_min_warning_hint') }}</p>
                </div>
                <span class="ks-switch">
                    <input type="checkbox" wire:model.live="warnBeforeLimit"/>
                    <span class="track"></span>
                    <span class="thumb"></span>
                </span>
            </label>
            <div class="setting-row">
                <div class="setting-ico tile-violet"><i class="ph-fill ph-lock-key"></i></div>
                <div class="grow min-w-0">
                    <p class="setting-text font-extrabold text-sm text-ink">{{ __('screen-time.require_pin') }}</p>
                    <p class="text-[11px] text-muted">{{ __('screen-time.require_pin_hint') }}</p>
                </div>
                <span class="ks-switch">
                    <input type="checkbox" checked disabled/>
                    <span class="track"></span>
                    <span class="thumb"></span>
                </span>
            </div>
        </div>
    </section>

    <section class="px-5 mt-5">
        <p class="section-label">{{ __('screen-time.per_day') }}</p>
        <div class="k-card p-0 overflow-hidden mt-3">
            @foreach ([1, 2, 3, 4, 5, 6, 7] as $iso)
                <div class="flex items-center gap-3 p-3{{ $iso === 1 ? '' : ' border-t border-token' }}">
                    <span class="w-9 text-center text-[11px] font-extrabold text-muted">{{ __('screen-time.dow_'.$iso) }}</span>
                    <p class="font-extrabold text-sm grow text-ink">{{ $iso >= 6 ? __('screen-time.weekend') : __('screen-time.weekday') }}</p>
                    <span class="chip chip-primary">{{ $this->dayLimitLabel() }}</span>
                </div>
            @endforeach
        </div>
        <p class="text-[11px] text-muted text-center mt-2">{{ __('screen-time.same_limit_every_day') }}</p>
    </section>

    <section class="px-5 mt-5 mb-10">
        <div class="tip-card rounded-2xl p-4 flex items-start gap-3">
            <div class="mascot shrink-0 size-11 text-xl">🦉</div>
            <div class="grow">
                <p class="font-extrabold text-sm text-ink">{{ __('screen-time.recommended') }}</p>
                <p class="text-xs text-muted">{{ __('screen-time.recommended_body') }}</p>
            </div>
        </div>
        @if ($savedHint !== '')
            <p class="text-sm mt-3 text-center" style="color:var(--color-k-mint)">{{ $savedHint }}</p>
        @endif
        <button type="button" class="btn btn-primary w-full mt-3" wire:click="save">{{ __('screen-time.save') }}</button>
        @if ($limitMinutes !== null)
            <button type="button" class="btn btn-soft w-full mt-2" wire:click="grantExtra" @disabled($extraGrantedToday)>
                {{ $extraGrantedToday ? __('screen-time.extended') : __('screen-time.extend') }}
            </button>
        @endif
    </section>
</main>
