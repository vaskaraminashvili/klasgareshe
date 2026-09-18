<?php

use App\Enums\PlayPauseReason;
use App\Repositories\UserRepository;
use App\Services\ScreenTimeService;
use Livewire\Component;

new class extends Component
{
    public string $reason = 'limit';

    public string $wakeLabel = '';

    public function title(): string
    {
        return __('screen-time.paused_title');
    }

    public function rendering(\Illuminate\View\View $view): void
    {
        $view->title($this->title());
    }

    public function mount(ScreenTimeService $time, UserRepository $users): void
    {
        $user = $users->authenticated();
        $blocked = $time->blockReason($user);

        if ($blocked === null) {
            $this->redirectRoute('home', navigate: true);

            return;
        }

        $this->reason = $blocked->value;
        $snap = $time->snapshot($user);
        $this->wakeLabel = (string) __('screen-time.paused_wake', [
            'time' => $time->formatClock($snap->bedtimeEnd, $snap->timezone),
        ]);
    }

    public function isBedtime(): bool
    {
        return $this->reason === PlayPauseReason::Bedtime->value;
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top">
    <header class="appbar">
        <a href="{{ route('home') }}" class="icon-btn" data-back aria-label="{{ __('screen-time.back') }}"><i class="ph ph-caret-left"></i></a>
        <div class="grow">
            <p class="text-xs text-muted">{{ __('screen-time.parent_zone') }}</p>
            <h1 class="h-display text-lg leading-tight">{{ $this->isBedtime() ? __('screen-time.bedtime_heading') : __('screen-time.heading') }}</h1>
        </div>
        <button type="button" class="icon-btn" data-theme-toggle aria-label="{{ __('screen-time.toggle_theme') }}"><i class="ph ph-moon text-xl"></i></button>
    </header>

    <section class="px-5">
        <div class="k-card-lg {{ $this->isBedtime() ? 'hero-xp' : 'hero-profile' }} text-center">
            <div class="relative inline-grid place-items-center">
                @if ($this->isBedtime())
                    <div class="size-28 rounded-full bg-white/20 backdrop-blur-sm grid place-items-center text-6xl">🌙</div>
                @else
                    <div class="size-28 rounded-3xl bg-white/20 backdrop-blur-sm grid place-items-center">
                        <i class="ph-fill ph-timer text-6xl text-white"></i>
                    </div>
                @endif
            </div>
            <p class="relative chip bg-white/20 border-0 text-white mt-4">
                {{ $this->isBedtime() ? __('screen-time.paused_chip_bedtime') : __('screen-time.paused_chip_limit') }}
            </p>
            <p class="relative h-display text-3xl mt-2 leading-tight">{{ $this->isBedtime() ? __('screen-time.paused_title_bedtime') : __('screen-time.paused_title_limit') }}</p>
            <p class="relative text-xs text-white/90 mt-2">{{ $this->isBedtime() ? __('screen-time.paused_body_bedtime') : __('screen-time.paused_body_limit') }}</p>
            @if ($this->isBedtime())
                <p class="relative text-sm font-extrabold text-white mt-3">{{ $wakeLabel }}</p>
            @endif
        </div>
    </section>

    <section class="px-5 mt-5 mb-10 space-y-2">
        <a href="{{ route('home') }}" wire:navigate class="btn btn-primary w-full">{{ __('screen-time.paused_home') }}</a>
        <a href="{{ route('profile') }}" wire:navigate class="btn btn-soft w-full">{{ __('screen-time.paused_profile') }}</a>
    </section>
</main>
