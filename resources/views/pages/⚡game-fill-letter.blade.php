<?php

use App\Enums\GameType;
use App\Livewire\PlaysWeekPlanPackComponent;
use App\Services\GamePlayService;
use Illuminate\View\View;

new class extends PlaysWeekPlanPackComponent
{
    public ?string $pendingKey = null;

    public function title(): string
    {
        return __('quiz.page_title_fill');
    }

    public function rendering(View $view): void
    {
        $view->title($this->title());
    }

    public function chooseLetter(string $key): void
    {
        if ($this->answered || $this->showResult || $this->settled) {
            return;
        }

        $allowed = array_column($this->choices, 'key');

        if (! in_array($key, $allowed, true)) {
            return;
        }

        $this->pendingKey = $key;
    }

    public function checkLetter(GamePlayService $play): void
    {
        if ($this->pendingKey === null) {
            return;
        }

        $this->pick($this->pendingKey, $play);
    }

    public function pendingLabel(): string
    {
        foreach ($this->choices as $choice) {
            if ($choice['key'] === $this->pendingKey) {
                return $choice['label'];
            }
        }

        return '';
    }

    protected function expectedGameType(): GameType
    {
        return GameType::FillLetter;
    }

    protected function afterShowCurrent(): void
    {
        $this->pendingKey = null;
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top" wire:poll.15s="heartbeat">
    <header class="appbar">
        <a href="{{ route('daily-mission') }}" wire:navigate class="icon-btn" data-back aria-label="{{ __('quiz.close') }}"><i class="ph ph-x"></i></a>
        <div class="grow"><div class="progress"><span style="width:{{ $this->progressPercent() }}%"></span></div></div>
        <button type="button" class="icon-btn" aria-label="{{ __('quiz.hear') }}"><i class="ph-fill ph-speaker-high"></i></button>
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
        <p class="text-xs font-extrabold" style="color:var(--color-k-muted)">{{ __('quiz.fill_letter') }}</p>
        <div class="mt-3 mx-auto size-44 rounded-[32px] {{ $tile }} grid place-items-center text-7xl">{{ $emoji !== '' ? $emoji : '✏️' }}</div>
    </section>

    <section class="px-6 mt-5">
        <div class="flex justify-center gap-3">
            @forelse ($letters as $slot)
                <div class="w-14 h-16 rounded-xl border-2 grid place-items-center text-3xl h-display {{ $slot['blank'] ? 'border-dashed ring-primary' : '' }}" style="background:var(--color-k-surface);border-color:{{ $slot['blank'] ? 'var(--color-k-primary)' : 'var(--color-k-border)' }}">{{ $slot['blank'] ? ($this->pendingLabel() !== '' ? $this->pendingLabel() : '?') : $slot['char'] }}</div>
            @empty
                <div class="w-14 h-16 rounded-xl border-2 border-dashed grid place-items-center text-3xl h-display ring-primary" style="border-color:var(--color-k-primary);background:var(--color-k-surface)">?</div>
            @endforelse
        </div>
    </section>

    <section class="px-6 mt-8" data-ans-group wire:key="q-choices-{{ $index }}">
        <p class="text-xs font-extrabold text-center mb-3" style="color:var(--color-k-muted)">{{ __('quiz.pick_letter') }}</p>
        <div class="grid grid-cols-4 gap-3">
            @foreach ($choices as $choice)
                <button type="button" class="ans aspect-square justify-center h-display text-3xl{{ $this->choiceClass($choice['key']) }}{{ $pendingKey === $choice['key'] && ! $answered ? ' ring-2 ring-primary' : '' }}" wire:click="chooseLetter('{{ $choice['key'] }}')" @disabled($answered)>{{ $choice['label'] }}</button>
            @endforeach
        </div>
    </section>

    <div class="mt-auto px-6 pb-6 pt-4 safe-bottom">
        <button type="button" class="btn btn-primary w-full" wire:click="{{ $answered ? 'next' : 'checkLetter' }}" wire:loading.attr="disabled" @disabled(! $answered && $pendingKey === null)>
            @if ($answered)
                <i class="ph-fill ph-arrow-right"></i> {{ __('quiz.continue') }}
            @else
                {{ __('quiz.check') }}
            @endif
        </button>
    </div>

    @include('partials.pack-result')
    @include('partials.play-break')
</main>
