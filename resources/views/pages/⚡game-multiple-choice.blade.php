<?php

use App\Enums\GameType;
use App\Livewire\PlaysWeekPlanPackComponent;
use Illuminate\View\View;

new class extends PlaysWeekPlanPackComponent
{
    public function title(): string
    {
        return __('quiz.page_title');
    }

    public function rendering(View $view): void
    {
        $view->title($this->title());
    }

    protected function expectedGameType(): GameType
    {
        return GameType::MultipleChoice;
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
        <p class="text-xs font-extrabold" style="color:var(--color-k-muted)">
            @if ($playMode === 'fill')
                {{ __('quiz.fill_letter') }}
            @elseif ($playMode === 'count')
                {{ __('quiz.counting') }}
            @elseif ($playMode === 'tap')
                {{ __('quiz.tap_correct') }}
            @else
                {{ __('quiz.question_n_of', ['current' => $index + 1, 'total' => count($questionIds)]) }}
            @endif
        </p>
        <h1 class="h-display text-2xl mt-2">{{ $prompt }}</h1>
    </section>

    @if ($playMode === 'fill' && $emoji !== '')
        <section class="px-6 mt-3" wire:key="q-emoji-{{ $index }}">
            <div class="mt-3 mx-auto size-44 rounded-[32px] {{ $tile }} grid place-items-center text-7xl">{{ $emoji }}
            </div>
        </section>
    @elseif ($playMode === 'count')
        <section class="px-5 mt-4" wire:key="q-count-{{ $index }}">
            <div class="k-card-lg {{ $tile }} aspect-[4/3] grid place-items-center">
                <div class="grid grid-cols-4 gap-3 p-3 text-5xl">
                    @foreach ($countItems as $item)
                        <span>{{ $item }}</span>
                    @endforeach
                </div>
            </div>
        </section>
    @elseif ($playMode === 'choice' && $emoji !== '')
        <section class="px-6 mt-4" wire:key="q-emoji-{{ $index }}">
            <div class="k-card-lg {{ $tile }} grid place-items-center aspect-[16/10]">
                <div class="text-8xl">{{ $emoji }}</div>
            </div>
        </section>
    @endif

    @if ($playMode === 'fill')
        <section class="px-6 mt-5" wire:key="q-letters-{{ $index }}">
            <div class="flex justify-center gap-3">
                @foreach ($letters as $letter)
                    @if ($letter['blank'])
                        <div class="w-14 h-16 rounded-xl border-2 border-dashed grid place-items-center text-3xl h-display ring-primary"
                            style="border-color:var(--color-k-primary);background:var(--color-k-surface)">
                            {{ $letter['char'] }}</div>
                    @else
                        <div class="w-14 h-16 rounded-xl border-2 grid place-items-center text-3xl h-display"
                            style="background:var(--color-k-surface);border-color:var(--color-k-border)">
                            {{ $letter['char'] }}</div>
                    @endif
                @endforeach
            </div>
        </section>
        <section class="px-6 mt-8" data-ans-group wire:key="q-choices-{{ $index }}">
            <p class="text-xs font-extrabold text-center mb-3" style="color:var(--color-k-muted)">
                {{ __('quiz.pick_letter') }}</p>
            <div class="grid grid-cols-4 gap-3">
                @foreach ($choices as $choice)
                    <button type="button"
                        class="ans aspect-square justify-center h-display text-3xl{{ $this->choiceClass($choice['key']) }}"
                        wire:click="pick('{{ $choice['key'] }}')" @disabled($answered)>{{ $choice['label'] }}</button>
                @endforeach
            </div>
        </section>
    @elseif ($playMode === 'count')
        <section class="px-6 mt-5 grid grid-cols-4 gap-2" data-ans-group wire:key="q-choices-{{ $index }}">
            @foreach ($choices as $choice)
                <button type="button"
                    class="ans aspect-square justify-center h-display text-2xl{{ $this->choiceClass($choice['key']) }}"
                    wire:click="pick('{{ $choice['key'] }}')" @disabled($answered)>{{ $choice['label'] }}</button>
            @endforeach
        </section>
    @elseif ($playMode === 'tap')
        <section class="px-6 mt-6 grid grid-cols-2 gap-3" data-ans-group wire:key="q-choices-{{ $index }}">
            @foreach ($choices as $choice)
                <button type="button"
                    class="ans aspect-square flex-col justify-center text-4xl{{ $this->choiceClass($choice['key']) }}"
                    wire:click="pick('{{ $choice['key'] }}')" @disabled($answered)>
                    <span class="h-display">{{ $choice['label'] }}</span>
                </button>
            @endforeach
        </section>
    @else
        <section class="px-6 mt-5 space-y-3" data-ans-group wire:key="q-choices-{{ $index }}">
            @foreach ($choices as $choice)
                <button type="button" class="ans{{ $this->choiceClass($choice['key']) }}"
                    wire:click="pick('{{ $choice['key'] }}')" @disabled($answered)>
                    <span class="ans-key">{{ $choice['key'] }}</span>
                    <span class="grow">{{ $choice['label'] }}</span>
                    @if ($choice['emoji'] !== '')
                        <span class="text-2xl">{{ $choice['emoji'] }}</span>
                    @endif
                </button>
            @endforeach
        </section>
    @endif

    <div class="mt-auto px-6 pb-6 pt-4 safe-bottom">
        <button type="button" class="btn btn-primary w-full" wire:click="next" wire:loading.attr="disabled"
            @disabled(!$answered)>
            {{ __('quiz.next_question') }} <i class="ph ph-arrow-right"></i>
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
