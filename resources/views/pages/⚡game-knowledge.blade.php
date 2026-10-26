<?php

use App\Enums\GameType;
use App\Livewire\PlaysWeekPlanPackComponent;
use App\Services\GamePlayService;
use Illuminate\View\View;

new class extends PlaysWeekPlanPackComponent
{
    public int $secondsLeft = 20;

    public ?string $pendingKey = null;

    /** @var list<string> */
    public array $hiddenKeys = [];

    public bool $usedFifty = false;

    public string $lifelineNote = '';

    public function title(): string
    {
        return __('quiz.page_title_knowledge');
    }

    public function rendering(View $view): void
    {
        $view->title($this->title());
    }

    public function choose(string $key): void
    {
        if ($this->answered || $this->showResult || in_array($key, $this->hiddenKeys, true)) {
            return;
        }

        $allowed = array_column($this->choices, 'key');

        if (! in_array($key, $allowed, true)) {
            return;
        }

        $this->pendingKey = $key;
    }

    public function submitAnswer(GamePlayService $play): void
    {
        if ($this->answered || $this->pendingKey === null) {
            return;
        }

        $this->pick($this->pendingKey, $play);
    }

    public function tickTimer(GamePlayService $play): void
    {
        if ($this->answered || $this->showResult || $this->settled || $this->questionIds === []) {
            return;
        }

        $this->secondsLeft = max(0, $this->secondsLeft - 1);

        if ($this->secondsLeft === 0) {
            $this->applyGrade($play->gradeInput($this->questionIds[$this->index], ''), null);
        }
    }

    public function fiftyFifty(GamePlayService $play): void
    {
        if ($this->usedFifty || $this->answered || $this->questionIds === []) {
            return;
        }

        $this->usedFifty = true;
        $this->hiddenKeys = $play->wrongKeys($this->questionIds[$this->index], 2);
    }

    public function poll(): void
    {
        if ($this->answered) {
            return;
        }

        $this->lifelineNote = (string) __('quiz.no_poll');
    }

    public function showHint(): void
    {
        $this->lifelineNote = $this->hint !== '' ? $this->hint : (string) __('quiz.no_hint');
    }

    public function knowledgeClass(string $key): string
    {
        if (! $this->answered && $this->pendingKey === $key) {
            return ' ring-2 ring-primary';
        }

        return $this->choiceClass($key);
    }

    protected function expectedGameType(): GameType
    {
        return GameType::Knowledge;
    }

    protected function afterShowCurrent(): void
    {
        $this->secondsLeft = 20;
        $this->pendingKey = null;
        $this->hiddenKeys = [];
        $this->usedFifty = false;
        $this->lifelineNote = '';
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top" wire:poll.15s="heartbeat">
    <header class="appbar">
        <a href="{{ route('daily-mission') }}" wire:navigate class="icon-btn" data-back aria-label="{{ __('quiz.close') }}"><i class="ph ph-x"></i></a>
        <div class="grow"><div class="progress"><span id="progressFill" style="width:{{ $this->progressPercent() }}%"></span></div></div>
        <span class="chip {{ $secondsLeft === 0 ? 'chip-coral' : 'chip-sky' }}" id="timerChip" wire:poll.1s="tickTimer">⏱️ 00:{{ sprintf('%02d', $secondsLeft) }}</span>
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
        <p class="text-xs font-extrabold text-muted tracking-wider">{{ __('quiz.knowledge') }}</p>
        <h1 class="h-display text-2xl mt-2">{{ $prompt }}</h1>
    </section>

    <section class="px-6 mt-4">
        <div class="k-card-lg {{ $tile }} aspect-[16/9] grid place-items-center">
            <div class="text-7xl">{{ $emoji !== '' ? $emoji : '🌍' }}</div>
        </div>
    </section>

    <section class="px-6 mt-5 space-y-3" data-ans-group id="ansGroup" wire:key="q-choices-{{ $index }}">
        @foreach ($choices as $choice)
            @if (! in_array($choice['key'], $hiddenKeys, true))
                <button type="button" class="ans{{ $this->knowledgeClass($choice['key']) }}" wire:click="choose('{{ $choice['key'] }}')" @disabled($answered)>
                    <span class="ans-key">{{ $choice['key'] }}</span>
                    <span class="grow">{{ $choice['label'] }}</span>
                    @if ($choice['emoji'] !== '')
                        <span class="text-2xl">{{ $choice['emoji'] }}</span>
                    @endif
                </button>
            @endif
        @endforeach
    </section>

    <section class="px-6 mt-4">
        <div class="grid grid-cols-3 gap-2">
            <button type="button" id="lifeFifty" class="btn btn-soft py-2 min-h-0" wire:click="fiftyFifty" @disabled($usedFifty || $answered)><i class="ph ph-lightbulb"></i> {{ __('quiz.fifty') }}</button>
            <button type="button" id="lifePoll" class="btn btn-soft py-2 min-h-0" wire:click="poll" @disabled($answered)><i class="ph ph-chart-pie-slice"></i> {{ __('quiz.poll') }}</button>
            <button type="button" id="lifeHint" class="btn btn-soft py-2 min-h-0" wire:click="showHint"><i class="ph ph-lifebuoy"></i> {{ __('quiz.hint') }}</button>
        </div>
        @if ($lifelineNote !== '')
            <p class="text-xs text-muted mt-2">{{ $lifelineNote }}</p>
        @endif
    </section>

    <div class="mt-auto px-6 pb-6 pt-4 safe-bottom">
        @if ($answered)
            <button type="button" class="btn btn-primary w-full" wire:click="next"><i class="ph-fill ph-arrow-right"></i> {{ __('quiz.continue') }}</button>
        @else
            <button id="submitBtn" type="button" class="btn btn-primary w-full" wire:click="submitAnswer" @disabled($pendingKey === null)><i class="ph-fill ph-check"></i> {{ __('quiz.submit') }}</button>
        @endif
    </div>

    @include('partials.pack-result')
    @include('partials.play-break')
</main>
