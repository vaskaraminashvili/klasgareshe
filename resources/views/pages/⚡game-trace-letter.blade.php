<?php

use App\Enums\GameType;
use App\Livewire\PlaysWeekPlanPackComponent;
use App\Services\GamePlayService;
use App\Services\TraceStrokeService;
use Illuminate\View\View;

new class extends PlaysWeekPlanPackComponent
{
    public string $guidePath = '';

    /** @var list<array{0: float, 1: float}> */
    public array $guideDots = [];

    public int $accuracy = 0;

    public int $traceTries = 0;

    public function title(): string
    {
        return __('quiz.page_title_trace');
    }

    public function rendering(View $view): void
    {
        $view->title($this->title());
    }

    /**
     * @param  list<mixed>  $points
     */
    public function submitTrace(array $points, GamePlayService $play, TraceStrokeService $strokes): int
    {
        if ($this->answered || $this->showResult || $this->settled || $this->questionIds === []) {
            return $this->accuracy;
        }

        $clean = [];

        foreach (array_slice($points, 0, 400) as $point) {
            if (! is_array($point) || ! isset($point[0], $point[1])) {
                continue;
            }

            if (! is_numeric($point[0]) || ! is_numeric($point[1])) {
                continue;
            }

            $clean[] = [(float) $point[0], (float) $point[1]];
        }

        $this->accuracy = $strokes->accuracy($this->strokes, $clean);
        $this->traceTries++;

        if ($this->accuracy >= TraceStrokeService::PASS) {
            $this->applyGrade($play->gradeInput($this->questionIds[$this->index], $this->prompt), null);

            return $this->accuracy;
        }

        if ($this->traceTries >= 3) {
            $this->applyGrade($play->gradeInput($this->questionIds[$this->index], ''), null);
        }

        return $this->accuracy;
    }

    public function stars(): string
    {
        return match (true) {
            $this->accuracy >= 90 => '⭐⭐⭐',
            $this->accuracy >= 75 => '⭐⭐☆',
            $this->accuracy >= 60 => '⭐☆☆',
            default => '☆☆☆',
        };
    }

    protected function expectedGameType(): GameType
    {
        return GameType::TraceLetter;
    }

    protected function afterShowCurrent(): void
    {
        $strokes = app(TraceStrokeService::class);
        $this->guidePath = $strokes->path($this->strokes);
        $this->guideDots = $strokes->dots($this->strokes);
        $this->accuracy = 0;
        $this->traceTries = 0;
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

    <section class="px-6 mt-2 text-center">
        <p class="text-xs font-extrabold text-muted tracking-wider">{{ __('quiz.trace_letter') }}</p>
        <h1 class="h-display text-2xl mt-2">{{ __('quiz.trace_help') }} <span class="text-primary-ink">{{ $prompt }}</span></h1>
    </section>

    <section class="px-5 mt-5">
        <div class="k-card-lg aspect-square grid place-items-center relative overflow-hidden hero-alphabet">
            <svg id="traceSvg" viewBox="0 0 200 220" class="w-3/4 h-3/4 touch-none" aria-label="{{ __('quiz.trace_label') }}">
                <path id="targetPath" d="{{ $guidePath }}" stroke="rgba(255,255,255,.55)" stroke-width="20" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-dasharray="6 12"/>
                <path id="userPath" d="" stroke="#7C5CFF" stroke-width="14" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                @foreach ($guideDots as $dot)
                    <circle cx="{{ $dot[0] }}" cy="{{ $dot[1] }}" r="6" fill="#FFE27A"/>
                @endforeach
                <text id="pencil" x="-50" y="-50" font-size="24">✏️</text>
            </svg>
        </div>
    </section>

    <section class="px-6 mt-4 grid grid-cols-3 gap-3 text-center">
        <div class="k-card p-3">
            <p class="text-xs text-muted">{{ __('quiz.stars') }}</p>
            <p class="h-display" id="starsVal">{{ $this->stars() }}</p>
        </div>
        <div class="k-card p-3">
            <p class="text-xs text-muted">{{ __('quiz.tries') }}</p>
            <p class="h-display" id="triesVal">{{ min($traceTries, 3) }}/3</p>
        </div>
        <div class="k-card p-3">
            <p class="text-xs text-muted">{{ __('quiz.accuracy') }}</p>
            <p class="h-display" id="accVal">{{ $accuracy }}%</p>
        </div>
    </section>

    <div class="mt-auto px-6 pb-6 pt-4 safe-bottom grid grid-cols-2 gap-3">
        <button id="restartBtn" type="button" class="btn btn-secondary"><i class="ph ph-arrow-counter-clockwise"></i> {{ __('quiz.restart') }}</button>
        <button id="nextBtn" type="button" class="btn btn-primary" wire:click="next" @disabled(! $answered)><i class="ph-fill ph-arrow-right"></i> {{ __('quiz.next') }}</button>
    </div>

    @include('partials.pack-result')
    @include('partials.play-break')
</main>

@assets
    <script src="{{ asset('assets/js/trace-pack.js') }}"></script>
@endassets
