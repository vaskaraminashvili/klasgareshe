<?php

use App\Enums\GameType;
use App\Livewire\PlaysWeekPlanPackComponent;
use Illuminate\View\View;

new class extends PlaysWeekPlanPackComponent
{
    public function title(): string
    {
        return __('quiz.page_title_tap');
    }

    public function rendering(View $view): void
    {
        $view->title($this->title());
    }

    protected function expectedGameType(): GameType
    {
        return GameType::TapCorrect;
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top" wire:poll.15s="heartbeat">
    <header class="appbar">
        <a href="{{ route('daily-mission') }}" wire:navigate class="icon-btn" aria-label="{{ __('quiz.close') }}"><i
                class="ph ph-x"></i></a>
        <div class="grow">
            <div class="progress"><span style="width:{{ $this->progressPercent() }}%"></span></div>
        </div>
        <span class="chip chip-coral">❤️ {{ $lives }}</span>
    </header>

    @if ($showWarn || $showBedtimeSoon)
        <section class="px-6 mt-2">
            @if ($showWarn)
                <p class="chip chip-sun w-full justify-center">{{ __('screen-time.warn_title') }}</p>
            @endif
            @if ($showBedtimeSoon)
                <p class="chip chip-primary w-full justify-center mt-1">{{ __('screen-time.bedtime_soon') }}</p>
            @endif
        </section>
    @endif

    <section class="px-6 mt-2 text-center" wire:key="q-meta-{{ $index }}">
        <p class="text-xs font-extrabold text-muted tracking-wider">{{ __('quiz.tap_correct') }}</p>
        <h1 class="h-display text-2xl mt-2">{{ $prompt }}</h1>
    </section>

    <section class="px-6 mt-6 grid grid-cols-2 gap-3" data-ans-group wire:key="q-choices-{{ $index }}">
        @foreach ($choices as $choice)
            <button type="button"
                class="ans aspect-square flex-col justify-center text-4xl{{ $this->choiceClass($choice['key']) }}"
                wire:click="pick('{{ $choice['key'] }}')" @disabled($answered)>
                @if ($choice['emoji'] !== '')
                    <span class="text-4xl">{{ $choice['emoji'] }}</span>
                @else
                    <span class="h-display">{{ $choice['label'] }}</span>
                @endif
            </button>
        @endforeach
    </section>

    <section class="px-6 mt-6">
        <div class="k-card flex items-center gap-3">
            <div class="mascot size-12 text-2xl shrink-0">🦉</div>
            <p class="text-sm text-ink"><span class="font-extrabold">{{ __('quiz.tip') }}:</span>
                {{ $hint !== '' ? $hint : __('quiz.tap_tip') }}</p>
        </div>
    </section>

    <div class="mt-auto px-6 pb-6 pt-4 safe-bottom">
        <button type="button" class="btn btn-primary w-full" wire:click="next" wire:loading.attr="disabled"
            @disabled(!$answered)>
            <i class="ph-fill ph-arrow-right"></i> {{ __('quiz.continue') }}
        </button>
    </div>

    @if ($showBreak)
        <div class="fixed inset-0 z-50" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
            <div class="relative h-full flex flex-col justify-end">
                <div class="mx-auto w-full max-w-[430px] bg-surface rounded-t-3xl border-t border-token shadow-2xl safe-bottom px-5 pt-4 pb-6 text-center">
                    <div class="flex justify-center pt-1">
                        <span class="block w-10 h-1.5 rounded-full bg-[var(--color-k-border)]"></span>
                    </div>
                    <div class="mx-auto size-20 rounded-full tile-mint grid place-items-center text-4xl mt-3">🦉</div>
                    <p class="h-display text-2xl mt-3 text-ink">{{ __('screen-time.break_title') }}</p>
                    <p class="text-sm text-muted mt-1">{{ __('screen-time.break_body') }}</p>
                    <button type="button" class="btn btn-primary w-full mt-5" wire:click="dismissBreak">{{ __('screen-time.break_continue') }}</button>
                </div>
            </div>
        </div>
    @endif
</main>
