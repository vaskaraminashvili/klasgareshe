<?php

use App\Enums\GameType;
use App\Livewire\PlaysWeekPlanPackComponent;
use Illuminate\View\View;

new class extends PlaysWeekPlanPackComponent
{
    public string $peekLabel = '';

    public function title(): string
    {
        return __('quiz.page_title_opposites');
    }

    public function rendering(View $view): void
    {
        $view->title($this->title());
    }

    public function peek(int $deckIndex): void
    {
        $card = $this->deck[$deckIndex] ?? null;

        if ($card === null || $deckIndex === $this->index) {
            return;
        }

        $this->peekLabel = $card['prompt'];
    }

    protected function expectedGameType(): GameType
    {
        return GameType::Opposites;
    }

    protected function afterShowCurrent(): void
    {
        $this->peekLabel = '';
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top" wire:poll.15s="heartbeat">
    <header class="appbar">
        <a href="{{ route('daily-mission') }}" wire:navigate class="icon-btn" data-back aria-label="{{ __('quiz.close') }}"><i class="ph ph-x"></i></a>
        <div class="grow"><div class="progress progress-coral"><span id="progressFill" style="width:{{ $this->progressPercent() }}%"></span></div></div>
        <span class="chip chip-coral" id="heartsChip">❤️ {{ $lives }}</span>
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

    <section class="px-6 mt-2 text-center">
        <p class="text-xs font-extrabold text-muted tracking-wider">{{ __('quiz.opposites') }}</p>
        <h1 class="h-display text-xl mt-1">{{ __('quiz.find_opposite') }}</h1>
    </section>

    <section class="px-6 mt-3">
        <div class="k-card-lg tile-coral grid place-items-center py-8 relative">
            <span class="h-display text-5xl text-coral-ink" id="promptWord">{{ $prompt }}</span>
            <span class="text-6xl absolute left-6 top-6" id="promptEmoji">{{ $emoji !== '' ? $emoji : '🔁' }}</span>
            <span class="absolute right-6 bottom-6 text-xs font-extrabold chip bg-white/70 border-0 text-coral-ink">⇄ {{ __('quiz.pick_opposite') }}</span>
        </div>
    </section>

    <section class="px-6 mt-5 grid grid-cols-2 gap-3" data-ans-group id="ansGroup" wire:key="q-choices-{{ $index }}">
        @foreach ($choices as $choice)
            <button type="button" class="ans py-6 h-display text-xl{{ $this->choiceClass($choice['key']) }}" wire:click="pick('{{ $choice['key'] }}')" @disabled($answered)><span class="grow text-center">{{ $choice['label'] }}</span></button>
        @endforeach
    </section>

    <section class="px-6 mt-4">
        <p class="text-xs font-extrabold text-muted tracking-wider">{{ __('quiz.more_pairs') }}</p>
        <div data-swiper-rail class="swiper rail-swiper mt-2">
            <div class="swiper-wrapper">
                @foreach ($deck as $deckIndex => $card)
                    @if ($deckIndex !== $index)
                        <button type="button" class="swiper-slide k-card min-w-[140px] text-center" data-peek wire:click="peek({{ $deckIndex }})"><p class="text-sm font-extrabold text-ink">{{ $card['prompt'] }}</p></button>
                    @endif
                @endforeach
            </div>
        </div>
        @if ($peekLabel !== '')
            <p class="text-xs text-muted mt-2">{{ $peekLabel }}</p>
        @endif
    </section>

    <div class="mt-auto px-6 pb-6 pt-4 safe-bottom">
        <button id="nextBtn" type="button" class="btn btn-primary w-full" wire:click="next" @disabled(! $answered)>
            <i class="ph-fill ph-arrow-right"></i> {{ __('quiz.next') }}
        </button>
    </div>

    @include('partials.pack-result')
    @include('partials.play-break')
</main>
