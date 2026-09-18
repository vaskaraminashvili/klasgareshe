<?php

use App\Repositories\UserRepository;
use App\Services\ScreenTimeService;
use Livewire\Component;

new class extends Component
{
    public bool $enabled = false;

    public string $bedtimeStart = '21:00';

    public string $bedtimeEnd = '07:00';

    /** @var list<int> */
    public array $days = [1, 2, 3, 4, 5, 6, 7];

    public string $windowLabel = '';

    public string $startDisplay = '';

    public string $endDisplay = '';

    public string $startPeriod = '';

    public string $endPeriod = '';

    public string $savedHint = '';

    public function title(): string
    {
        return __('screen-time.bedtime_title');
    }

    public function rendering(\Illuminate\View\View $view): void
    {
        $view->title($this->title());
    }

    public function mount(ScreenTimeService $time, UserRepository $users): void
    {
        $this->fillFrom($time, $users->authenticated());
    }

    public function updatedEnabled(bool $value): void
    {
        $this->persist();
    }

    public function updatedBedtimeStart(string $value): void
    {
        $this->persist();
    }

    public function updatedBedtimeEnd(string $value): void
    {
        $this->persist();
    }

    public function toggleDay(int $day): void
    {
        if (! in_array($day, [1, 2, 3, 4, 5, 6, 7], true)) {
            return;
        }

        if (in_array($day, $this->days, true)) {
            if (count($this->days) === 1) {
                return;
            }

            $this->days = array_values(array_filter($this->days, fn (int $item): bool => $item !== $day));
        } else {
            $this->days[] = $day;
            sort($this->days);
        }

        $this->persist();
    }

    public function save(): void
    {
        $this->persist();
        $this->savedHint = (string) __('screen-time.saved');
    }

    public function isDayOn(int $day): bool
    {
        return in_array($day, $this->days, true);
    }

    private function persist(): void
    {
        $time = app(ScreenTimeService::class);
        $user = app(UserRepository::class)->authenticated();

        try {
            $time->saveBedtime($user, $this->enabled, $this->bedtimeStart, $this->bedtimeEnd, $this->days);
        } catch (\InvalidArgumentException) {
            return;
        }

        $this->fillFrom($time, $user);
    }

    private function fillFrom(ScreenTimeService $time, \App\Models\User $user): void
    {
        $snap = $time->snapshot($user);
        $this->enabled = $snap->bedtimeEnabled;
        $this->bedtimeStart = $snap->bedtimeStart;
        $this->bedtimeEnd = $snap->bedtimeEnd;
        $this->days = $snap->bedtimeDays;
        $this->startDisplay = $this->clockFace($snap->bedtimeStart);
        $this->endDisplay = $this->clockFace($snap->bedtimeEnd);
        $this->startPeriod = $this->clockPeriod($snap->bedtimeStart);
        $this->endPeriod = $this->clockPeriod($snap->bedtimeEnd);
        $this->windowLabel = (string) __('screen-time.sleep_window', [
            'start' => $time->formatClock($snap->bedtimeStart, $snap->timezone),
            'end' => $time->formatClock($snap->bedtimeEnd, $snap->timezone),
        ]);
    }

    private function clockFace(string $clock): string
    {
        [$hour, $minute] = array_map('intval', explode(':', $clock));
        $hour12 = $hour % 12;

        if ($hour12 === 0) {
            $hour12 = 12;
        }

        return $hour12.':'.sprintf('%02d', $minute);
    }

    private function clockPeriod(string $clock): string
    {
        $hour = (int) explode(':', $clock)[0];

        return $hour >= 12 ? 'PM' : 'AM';
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top">

    <header class="appbar">
        <a href="{{ route('parent-controls') }}" class="icon-btn" data-back aria-label="{{ __('screen-time.back') }}"><i class="ph ph-caret-left"></i></a>
        <div class="grow">
            <p class="text-xs text-muted">{{ __('screen-time.parent_zone') }}</p>
            <h1 class="h-display text-lg leading-tight">{{ __('screen-time.bedtime_heading') }}</h1>
        </div>
        <button type="button" class="icon-btn" data-theme-toggle aria-label="{{ __('screen-time.toggle_theme') }}"><i class="ph ph-moon text-xl"></i></button>
    </header>

    <section class="px-5">
        <div class="k-card-lg hero-xp text-center">
            <div class="relative inline-grid place-items-center">
                <div class="size-28 rounded-full bg-white/20 backdrop-blur-sm grid place-items-center text-6xl">🌙</div>
            </div>
            <p class="relative chip bg-white/20 border-0 text-white mt-4">
                <i class="ph-fill ph-bed"></i> {{ __('screen-time.sleep_hours') }}
            </p>
            <p class="relative h-display text-4xl mt-2 leading-none">{{ $windowLabel }}</p>
            <p class="relative text-xs text-white/90 mt-1">{{ __('screen-time.app_hidden') }}</p>
        </div>
    </section>

    <section class="px-5 mt-5">
        <label class="setting-row cursor-pointer">
            <div class="setting-ico tile-violet"><i class="ph-fill ph-moon"></i></div>
            <div class="grow min-w-0">
                <p class="setting-text font-extrabold text-sm text-ink">{{ __('screen-time.enable_bedtime') }}</p>
                <p class="text-[11px] text-muted">{{ __('screen-time.enable_bedtime_hint') }}</p>
            </div>
            <span class="ks-switch">
                <input type="checkbox" wire:model.live="enabled"/>
                <span class="track"></span>
                <span class="thumb"></span>
            </span>
        </label>
    </section>

    <section class="px-5 mt-5">
        <p class="section-label">{{ __('screen-time.sleep_schedule') }}</p>
        <div class="mt-3 grid grid-cols-2 gap-3">
            <div class="k-card p-4 text-center">
                <p class="text-[10px] font-extrabold uppercase tracking-wide text-muted">{{ __('screen-time.bedtime') }}</p>
                <p class="h-display text-2xl mt-1 text-ink">{{ $startDisplay }}</p>
                <p class="text-xs text-muted">{{ $startPeriod }} · {{ __('screen-time.lock_starts_at') }}</p>
                <label class="btn btn-soft h-9 min-h-0 w-full mt-2 text-xs relative overflow-hidden">
                    {{ __('screen-time.change') }}
                    <input type="time" wire:model.live="bedtimeStart" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);border:0"/>
                </label>
            </div>
            <div class="k-card p-4 text-center">
                <p class="text-[10px] font-extrabold uppercase tracking-wide text-muted">{{ __('screen-time.wake') }}</p>
                <p class="h-display text-2xl mt-1 text-ink">{{ $endDisplay }}</p>
                <p class="text-xs text-muted">{{ $endPeriod }} · {{ __('screen-time.lock_ends_at') }}</p>
                <label class="btn btn-soft h-9 min-h-0 w-full mt-2 text-xs relative overflow-hidden">
                    {{ __('screen-time.change') }}
                    <input type="time" wire:model.live="bedtimeEnd" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);border:0"/>
                </label>
            </div>
        </div>
    </section>

    <section class="px-5 mt-5">
        <p class="section-label">{{ __('screen-time.active_days') }}</p>
        <div class="mt-3 grid grid-cols-7 gap-2">
            @foreach ([1, 2, 3, 4, 5, 6, 7] as $iso)
                <button type="button" class="pick-card !p-2 !gap-1 flex-col items-center{{ $this->isDayOn($iso) ? ' is-selected' : '' }}" wire:click="toggleDay({{ $iso }})">
                    <span class="text-[10px] text-muted font-extrabold">{{ __('screen-time.day_'.$iso) }}</span>
                    <span class="pc-check"></span>
                </button>
            @endforeach
        </div>
        <p class="text-[11px] text-muted text-center mt-2">{{ __('screen-time.days_hint') }}</p>
    </section>

    <section class="px-5 mt-5">
        <p class="section-label">{{ __('screen-time.options') }}</p>
        <div class="mt-3 space-y-2">
            <div class="setting-row">
                <div class="setting-ico tile-sun"><i class="ph-fill ph-hourglass"></i></div>
                <div class="grow min-w-0">
                    <p class="setting-text font-extrabold text-sm text-ink">{{ __('screen-time.bedtime_warning') }}</p>
                    <p class="text-[11px] text-muted">{{ __('screen-time.bedtime_warning_hint') }}</p>
                </div>
                <span class="ks-switch">
                    <input type="checkbox" checked disabled/>
                    <span class="track"></span>
                    <span class="thumb"></span>
                </span>
            </div>
            <div class="setting-row">
                <div class="setting-ico tile-coral"><i class="ph-fill ph-bell-slash"></i></div>
                <div class="grow min-w-0">
                    <p class="setting-text font-extrabold text-sm text-ink">{{ __('screen-time.silence_notifications') }}</p>
                    <p class="text-[11px] text-muted">{{ __('screen-time.silence_notifications_hint') }}</p>
                </div>
                <span class="ks-switch">
                    <input type="checkbox" checked disabled/>
                    <span class="track"></span>
                    <span class="thumb"></span>
                </span>
            </div>
            <div class="setting-row">
                <div class="setting-ico tile-violet"><i class="ph-fill ph-lock-key"></i></div>
                <div class="grow min-w-0">
                    <p class="setting-text font-extrabold text-sm text-ink">{{ __('screen-time.require_pin_override') }}</p>
                    <p class="text-[11px] text-muted">{{ __('screen-time.require_pin_override_hint') }}</p>
                </div>
                <span class="ks-switch">
                    <input type="checkbox" checked disabled/>
                    <span class="track"></span>
                    <span class="thumb"></span>
                </span>
            </div>
        </div>
    </section>

    <section class="px-5 mt-5 mb-10">
        <div class="tip-card rounded-2xl p-4 flex items-start gap-3">
            <div class="mascot shrink-0 size-11 text-xl">🦉</div>
            <div class="grow">
                <p class="font-extrabold text-sm text-ink">{{ __('screen-time.healthy_sleep') }}</p>
                <p class="text-xs text-muted">{{ __('screen-time.healthy_sleep_body') }}</p>
            </div>
        </div>
        @if ($savedHint !== '')
            <p class="text-sm mt-3 text-center" style="color:var(--color-k-mint)">{{ $savedHint }}</p>
        @endif
        <button type="button" class="btn btn-primary w-full mt-3" wire:click="save">{{ __('screen-time.save') }}</button>
    </section>
</main>
