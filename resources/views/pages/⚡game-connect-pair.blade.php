<?php

use App\Enums\GameType;
use App\Livewire\PlaysWeekPlanPackComponent;
use App\Repositories\UserRepository;
use App\Services\BadgeService;
use App\Services\GamePlayService;
use App\Services\ScreenTimeService;
use App\Services\WeekPlanService;
use Illuminate\View\View;

new class extends PlaysWeekPlanPackComponent
{
    /** @var list<int> */
    public array $rightOrder = [];

    public ?int $activeLeft = null;

    /** @var array<int, int> */
    public array $links = [];

    public function title(): string
    {
        return __('quiz.page_title_connect');
    }

    public function rendering(View $view): void
    {
        $view->title($this->title());
    }

    public function mount(
        GamePlayService $play,
        UserRepository $users,
        WeekPlanService $week,
        ScreenTimeService $time,
        ?int $item = null,
    ): void {
        $this->bootPack($play, $users, $week, $time, $item);
        $this->rightOrder = array_reverse(array_keys($this->deck));
    }

    public function selectLeft(int $index): void
    {
        if ($this->showResult || $this->settled || ! isset($this->deck[$index])) {
            return;
        }

        $this->activeLeft = $index;
    }

    public function selectRight(int $index): void
    {
        if ($this->activeLeft === null || $this->showResult || $this->settled || ! isset($this->deck[$index])) {
            return;
        }

        $next = [];

        foreach ($this->links as $left => $right) {
            if ($right !== $index && $left !== $this->activeLeft) {
                $next[$left] = $right;
            }
        }

        $next[$this->activeLeft] = $index;
        $this->links = $next;
        $this->activeLeft = null;
    }

    public function resetLinks(): void
    {
        if ($this->showResult) {
            return;
        }

        $this->links = [];
        $this->activeLeft = null;
    }

    public function checkBoard(GamePlayService $play, UserRepository $users, BadgeService $badges): void
    {
        if ($this->showResult || $this->deck === [] || count($this->links) < count($this->deck)) {
            return;
        }

        $correct = 0;

        foreach ($this->links as $leftIndex => $rightIndex) {
            $left = $this->deck[$leftIndex] ?? null;
            $right = $this->deck[$rightIndex] ?? null;

            if ($left === null || $right === null) {
                continue;
            }

            if ($play->gradeInput($left['id'], $right['pairLabel'])->correct) {
                $correct++;
            }
        }

        $this->correctCount = $correct;
        $this->combo = $correct;
        $this->maxCombo = $correct;
        $this->answered = true;
        $this->finishPack($play, $users, $badges, true);
    }

    protected function expectedGameType(): GameType
    {
        return GameType::ConnectPair;
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top" wire:poll.15s="heartbeat">
    <header class="appbar">
        <a href="{{ route('daily-mission') }}" wire:navigate class="icon-btn" data-back aria-label="{{ __('quiz.close') }}"><i class="ph ph-x"></i></a>
        <div class="grow">
            <p class="text-xs text-muted">{{ __('quiz.connect_help') }}</p>
            <h1 class="h-display text-lg leading-tight">{{ __('quiz.connect_title') }}</h1>
        </div>
        <span class="chip chip-sun" id="scoreChip">⭐ {{ count($links) }} / {{ count($deck) }}</span>
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

    <section class="px-6 mt-1 text-center">
        <p class="text-xs font-extrabold text-muted tracking-wider">{{ __('quiz.connect_pairs') }}</p>
    </section>

    <section class="px-5 mt-4">
        <div class="relative k-card-lg p-2 overflow-hidden">
            <svg id="lineLayer" viewBox="0 0 340 380" class="w-full h-[380px] pointer-events-none" aria-hidden="true"></svg>
            <div class="absolute inset-0 p-4 flex justify-between">
                <div class="flex flex-col justify-around" id="colLeft">
                    @foreach ($deck as $index => $card)
                        <button type="button" class="k-card w-28 text-center pair-card {{ $activeLeft === $index ? 'ring-primary is-selected' : '' }}" data-side="L" data-key="{{ $index }}" data-pos="{{ $index }}" wire:click="selectLeft({{ $index }})">
                            <span class="text-3xl">{{ $card['emoji'] }}</span><p class="text-xs font-extrabold text-ink">{{ $card['prompt'] }}</p>
                        </button>
                    @endforeach
                </div>
                <div class="flex flex-col justify-around" id="colRight">
                    @foreach ($rightOrder as $position => $index)
                        <button type="button" class="k-card w-28 text-center pair-card {{ in_array($index, $links, true) ? 'ring-2 ring-primary' : '' }}" data-side="R" data-key="{{ $index }}" data-pos="{{ $position }}" wire:click="selectRight({{ $index }})">
                            <span class="text-3xl">{{ $deck[$index]['pairEmoji'] !== '' ? $deck[$index]['pairEmoji'] : $deck[$index]['emoji'] }}</span><p class="text-xs font-extrabold text-ink">{{ $deck[$index]['pairLabel'] }}</p>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="px-6 mt-3">
        <p id="tip" class="flex items-center gap-2 text-xs text-muted">
            <i class="ph-fill ph-info text-primary-ink"></i>
            {{ __('quiz.connect_tip') }}
        </p>
    </section>

    <div class="mt-auto px-6 pb-6 pt-4 safe-bottom grid grid-cols-2 gap-3">
        <button id="resetBtn" type="button" class="btn btn-secondary" wire:click="resetLinks"><i class="ph ph-arrow-counter-clockwise"></i> {{ __('quiz.reset') }}</button>
        <button id="checkBtn" type="button" class="btn btn-primary" wire:click="checkBoard" @disabled(count($links) < count($deck) || $deck === [])><i class="ph-fill ph-check"></i> {{ __('quiz.check') }}</button>
    </div>

    @include('partials.pack-result')
    @include('partials.play-break')
</main>
