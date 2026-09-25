<?php

use App\Enums\GameType;
use App\Livewire\PlaysWeekPlanPackComponent;
use App\Services\GamePlayService;
use Illuminate\View\View;

new class extends PlaysWeekPlanPackComponent
{
    /** @var list<string> */
    public array $typed = [];

    public function title(): string
    {
        return __('quiz.page_title_spell');
    }

    public function rendering(View $view): void
    {
        $view->title($this->title());
    }

    public function typeLetter(string $letter): void
    {
        if ($this->answered || $this->showResult || $this->settled) {
            return;
        }

        if (! in_array($letter, $this->keyboard, true)) {
            return;
        }

        if ($this->letterSlots > 0 && count($this->typed) >= $this->letterSlots) {
            return;
        }

        $this->typed[] = $letter;
    }

    public function eraseLetter(): void
    {
        if ($this->answered || $this->typed === []) {
            return;
        }

        array_pop($this->typed);
    }

    public function checkSpelling(GamePlayService $play): void
    {
        if ($this->answered || $this->showResult || $this->questionIds === []) {
            return;
        }

        if ($this->letterSlots > 0 && count($this->typed) !== $this->letterSlots) {
            return;
        }

        $this->applyGrade($play->gradeInput($this->questionIds[$this->index], implode('', $this->typed)), null);
    }

    protected function expectedGameType(): GameType
    {
        return GameType::SpellWord;
    }

    protected function afterShowCurrent(): void
    {
        $this->typed = [];
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top" wire:poll.15s="heartbeat">
    <header class="appbar">
        <a href="{{ route('daily-mission') }}" wire:navigate class="icon-btn" data-back aria-label="{{ __('quiz.close') }}"><i class="ph ph-x"></i></a>
        <div class="grow"><div class="progress"><span id="progressFill" style="width:{{ $this->progressPercent() }}%"></span></div></div>
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
        <p class="text-xs font-extrabold text-muted tracking-wider">{{ __('quiz.spell_word') }}</p>
        <div class="mt-3 mx-auto size-44 rounded-[32px] {{ $tile }} grid place-items-center text-7xl">{{ $emoji !== '' ? $emoji : '🔤' }}</div>
    </section>

    <section class="px-6 mt-5">
        <div class="flex justify-center gap-2 flex-wrap" id="slots">
            @for ($slot = 0; $slot < max(1, $letterSlots); $slot++)
                <div class="w-12 h-14 rounded-xl border-2 border-token grid place-items-center text-2xl h-display bg-surface text-ink" data-slot="{{ $slot }}">{{ $typed[$slot] ?? '' }}</div>
            @endfor
        </div>
        <p id="feedback" class="text-center text-xs mt-3 text-muted">
            {{ $prompt !== '' ? $prompt : __('quiz.spell_help') }}
        </p>
    </section>

    <section class="px-6 mt-8">
        <p class="text-xs font-extrabold text-center mb-3 text-muted tracking-wider">{{ __('quiz.choose_letters') }}</p>
        <div class="flex flex-wrap justify-center gap-2" id="kbd">
            @foreach ($keyboard as $letter)
                <button type="button" class="kbd" data-k="{{ $letter }}" wire:click="typeLetter('{{ $letter }}')" @disabled($answered)>{{ $letter }}</button>
            @endforeach
        </div>
    </section>

    <div class="mt-auto px-6 pb-6 pt-6 safe-bottom grid grid-cols-2 gap-3">
        <button id="eraseBtn" type="button" class="btn btn-secondary" wire:click="eraseLetter" @disabled($answered || $typed === [])><i class="ph ph-backspace"></i> {{ __('quiz.erase') }}</button>
        @if ($answered)
            <button type="button" class="btn btn-primary" wire:click="next"><i class="ph-fill ph-arrow-right"></i> {{ __('quiz.continue') }}</button>
        @else
            <button id="checkBtn" type="button" class="btn btn-primary" wire:click="checkSpelling" @disabled(count($typed) !== $letterSlots || $letterSlots === 0)>{{ __('quiz.check') }} <i class="ph ph-check"></i></button>
        @endif
    </div>

    @include('partials.pack-result')
    @include('partials.play-break')
</main>
