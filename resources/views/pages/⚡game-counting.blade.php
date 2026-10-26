<?php

use App\Enums\GameType;
use App\Livewire\PlaysWeekPlanPackComponent;
use App\Services\GamePlayService;
use Illuminate\View\View;

new class extends PlaysWeekPlanPackComponent
{
    public int $stepper = 0;

    public bool $hasCountInput = false;

    public function title(): string
    {
        return __('quiz.page_title_count');
    }

    public function rendering(View $view): void
    {
        $view->title($this->title());
    }

    public function bump(int $delta): void
    {
        if ($this->answered || $this->settled) {
            return;
        }

        $this->stepper = max(0, min(12, $this->stepper + $delta));
        $this->hasCountInput = true;
        $this->pickedKey = $this->keyForStepper();
    }

    public function selectCount(string $key): void
    {
        if ($this->answered || $this->settled) {
            return;
        }

        foreach ($this->choices as $choice) {
            if ($choice['key'] !== $key) {
                continue;
            }

            $this->pickedKey = $key;
            $this->stepper = (int) $choice['label'];
            $this->hasCountInput = true;

            return;
        }
    }

    public function check(GamePlayService $play): void
    {
        if ($this->answered || $this->settled || ! $this->hasCountInput || $this->questionIds === []) {
            return;
        }

        $grade = $play->gradeInput($this->questionIds[$this->index], (string) $this->stepper);
        $this->applyGrade($grade, $this->keyForStepper());
    }

    protected function expectedGameType(): GameType
    {
        return GameType::Counting;
    }

    protected function afterShowCurrent(): void
    {
        $this->stepper = 0;
        $this->hasCountInput = false;
    }

    private function keyForStepper(): ?string
    {
        $want = (string) $this->stepper;

        foreach ($this->choices as $choice) {
            if ($choice['label'] === $want) {
                return $choice['key'];
            }
        }

        return null;
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
        <p class="text-xs font-extrabold text-muted tracking-wider">{{ __('quiz.math_exercise') }}</p>
        <h1 class="h-display text-2xl mt-2">{{ $prompt }}</h1>
    </section>

    <section class="px-5 mt-4" wire:key="q-count-{{ $index }}">
        <div class="k-card-lg {{ $tile }} aspect-[4/3] grid place-items-center">
            <div class="grid grid-cols-4 gap-3 p-3 text-5xl">
                @foreach ($countItems as $glyph)
                    <span>{{ $glyph }}</span>
                @endforeach
            </div>
        </div>
    </section>

    <section class="px-6 mt-5 flex items-center justify-center gap-4" aria-label="{{ __('quiz.your_answer') }}">
        <button type="button" class="icon-btn" wire:click="bump(-1)" @disabled($answered)
            aria-label="{{ __('quiz.lower') }}"><i class="ph ph-minus text-xl"></i></button>
        <div class="h-display text-5xl w-24 text-center text-ink" aria-live="polite">{{ $stepper }}</div>
        <button type="button" class="icon-btn" wire:click="bump(1)" @disabled($answered)
            aria-label="{{ __('quiz.higher') }}"><i class="ph ph-plus text-xl"></i></button>
    </section>

    <section class="px-6 mt-5 grid grid-cols-4 gap-2" data-ans-group wire:key="q-choices-{{ $index }}">
        @foreach ($choices as $choice)
            <button type="button"
                class="ans aspect-square justify-center h-display text-2xl{{ $this->choiceClass($choice['key']) }}"
                wire:click="selectCount('{{ $choice['key'] }}')" @disabled($answered)>{{ $choice['label'] }}</button>
        @endforeach
    </section>

    <p class="px-6 mt-4 text-center text-xs text-muted">{{ __('quiz.count_help') }}</p>

    <div class="mt-auto px-6 pb-6 pt-4 safe-bottom">
        @if ($answered)
            <button type="button" class="btn btn-primary w-full" wire:click="next" wire:loading.attr="disabled">
                <i class="ph-fill ph-arrow-right"></i> {{ __('quiz.continue') }}
            </button>
        @else
            <button type="button" class="btn btn-primary w-full" wire:click="check" wire:loading.attr="disabled"
                @disabled(!$hasCountInput)>
                <i class="ph-fill ph-check"></i> {{ __('quiz.check') }}
            </button>
        @endif
    </div>

    @include('partials.pack-result')

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
