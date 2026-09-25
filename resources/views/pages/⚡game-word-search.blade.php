<?php

use App\Enums\GameType;
use App\Livewire\PlaysWeekPlanPackComponent;
use App\Repositories\UserRepository;
use App\Services\BadgeService;
use App\Services\GamePlayService;
use App\Services\WordSearchBoard;
use Illuminate\View\View;

new class extends PlaysWeekPlanPackComponent
{
    /** @var list<list<string>> */
    public array $grid = [];

    /** @var list<array{word: string, cells: list<array{0: int, 1: int}>}> */
    public array $placements = [];

    /** @var list<string> */
    public array $foundWords = [];

    /** @var list<string> */
    public array $hintCells = [];

    public ?int $anchorRow = null;

    public ?int $anchorCol = null;

    public int $hintsLeft = 3;

    public function title(): string
    {
        return __('quiz.page_title_search');
    }

    public function rendering(View $view): void
    {
        $view->title($this->title());
    }

    protected function afterPackReady(): void
    {
        if ($this->deck === []) {
            return;
        }

        $words = [];

        foreach ($this->deck as $card) {
            if ($card['prompt'] !== '') {
                $words[] = $card['prompt'];
            }
        }

        $built = app(WordSearchBoard::class)->build($words);
        $this->grid = $built['rows'];
        $this->placements = $built['placements'];
    }

    public function selectCell(int $row, int $column, GamePlayService $play, WordSearchBoard $board): void
    {
        if ($this->showResult || $this->settled || $this->grid === []) {
            return;
        }

        if ($row < 0 || $column < 0 || $row >= WordSearchBoard::SIZE || $column >= WordSearchBoard::SIZE) {
            return;
        }

        if ($this->anchorRow === null || $this->anchorCol === null) {
            $this->anchorRow = $row;
            $this->anchorCol = $column;

            return;
        }

        $letters = $board->lettersBetween($this->grid, $this->anchorRow, $this->anchorCol, $row, $column);
        $this->anchorRow = null;
        $this->anchorCol = null;

        if (! is_string($letters)) {
            return;
        }

        $word = $this->matchWord($letters);

        if ($word === null || in_array($word, $this->foundWords, true)) {
            return;
        }

        foreach ($this->deck as $card) {
            if ($card['prompt'] !== $word) {
                continue;
            }

            if (! $play->gradeInput($card['id'], $word)->correct) {
                continue;
            }

            $this->foundWords[] = $word;
            $this->correctCount++;
            $this->combo++;
            $this->maxCombo = max($this->maxCombo, $this->combo);

            return;
        }
    }

    public function useHint(): void
    {
        if ($this->hintsLeft <= 0 || $this->showResult) {
            return;
        }

        foreach ($this->placements as $place) {
            if (in_array($place['word'], $this->foundWords, true)) {
                continue;
            }

            $cell = $place['cells'][0] ?? null;

            if ($cell === null) {
                continue;
            }

            $this->hintCells[] = $cell[0].':'.$cell[1];
            $this->hintsLeft--;

            return;
        }
    }

    public function finishSearch(GamePlayService $play, UserRepository $users, BadgeService $badges): void
    {
        if ($this->placements === [] || count($this->foundWords) < count($this->placements)) {
            return;
        }

        $this->finishPack($play, $users, $badges, true);
    }

    public function cellClass(int $row, int $column): string
    {
        if ($this->cellFound($row, $column)) {
            return 'bg-[rgba(78,214,168,.3)] text-mint-ink';
        }

        if ($this->anchorRow === $row && $this->anchorCol === $column) {
            return 'ring-2 ring-primary bg-[var(--color-k-bg)] text-ink';
        }

        if (in_array($row.':'.$column, $this->hintCells, true)) {
            return 'ring-2 ring-primary bg-[var(--color-k-bg)] text-ink';
        }

        return 'bg-[var(--color-k-bg)] text-ink';
    }

    public function wordFound(string $word): bool
    {
        return in_array($word, $this->foundWords, true);
    }

    public function elapsedLabel(): string
    {
        if ($this->startedAt === '') {
            return '0:00';
        }

        $seconds = max(0, (int) now()->diffInSeconds($this->startedAt));

        return sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    }

    public function refreshClock(): void {}

    protected function expectedGameType(): GameType
    {
        return GameType::WordSearch;
    }

    private function matchWord(string $letters): ?string
    {
        $reverse = implode('', array_reverse(mb_str_split($letters)));

        foreach ($this->placements as $place) {
            if ($place['word'] === $letters || $place['word'] === $reverse) {
                return $place['word'];
            }
        }

        return null;
    }

    private function cellFound(int $row, int $column): bool
    {
        foreach ($this->placements as $place) {
            if (! in_array($place['word'], $this->foundWords, true)) {
                continue;
            }

            foreach ($place['cells'] as $cell) {
                if ($cell[0] === $row && $cell[1] === $column) {
                    return true;
                }
            }
        }

        return false;
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top" wire:poll.15s="heartbeat">
    <header class="appbar">
        <a href="{{ route('daily-mission') }}" wire:navigate class="icon-btn" data-back aria-label="{{ __('quiz.close') }}"><i class="ph ph-x"></i></a>
        <div class="grow">
            <p class="text-xs text-muted">{{ __('quiz.search_help') }}</p>
            <h1 class="h-display text-lg leading-tight">{{ __('games.word_search') }}</h1>
        </div>
        <span class="chip chip-primary" id="progressChip">{{ count($foundWords) }} / {{ count($placements) }}</span>
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

    <section class="px-5">
        <div class="flex flex-wrap gap-2" id="wordChips">
            @foreach ($placements as $place)
                <span class="chip {{ $this->wordFound($place['word']) ? 'chip-mint line-through' : '' }}" data-word-chip="{{ $place['word'] }}">{{ $place['word'] }}</span>
            @endforeach
        </div>
    </section>

    <section class="px-5 mt-4">
        <div class="k-card p-3">
            <div class="grid grid-cols-8 gap-1 text-center font-extrabold select-none" id="grid">
                @foreach ($grid as $rowIndex => $row)
                    @foreach ($row as $columnIndex => $letter)
                        <button type="button" data-r="{{ $rowIndex }}" data-c="{{ $columnIndex }}" class="aspect-square rounded-lg grid place-items-center cell {{ $this->cellClass($rowIndex, $columnIndex) }}" wire:click="selectCell({{ $rowIndex }}, {{ $columnIndex }})">{{ $letter }}</button>
                    @endforeach
                @endforeach
            </div>
        </div>
    </section>

    <section class="px-5 mt-4 grid grid-cols-3 gap-3 text-center">
        <div class="k-card p-3">
            <p class="text-xs text-muted">{{ __('quiz.time') }}</p>
            <p class="h-display" id="timeVal" wire:poll.5s="refreshClock">{{ $this->elapsedLabel() }}</p>
        </div>
        <div class="k-card p-3">
            <p class="text-xs text-muted">{{ __('quiz.hints') }}</p>
            <p class="h-display" id="hintsVal">{{ $hintsLeft }}</p>
        </div>
        <div class="k-card p-3">
            <p class="text-xs text-muted">{{ __('quiz.score') }}</p>
            <p class="h-display" id="scoreVal">{{ $correctCount }}</p>
        </div>
    </section>

    <p id="tip" class="px-6 mt-3 text-xs flex items-center gap-2 {{ count($foundWords) === count($placements) && $placements !== [] ? 'text-mint-ink' : 'text-muted' }}">
        <i class="ph-fill {{ count($foundWords) === count($placements) && $placements !== [] ? 'ph-confetti text-mint-ink' : 'ph-info text-primary-ink' }}"></i>
        {{ count($foundWords) === count($placements) && $placements !== [] ? __('quiz.search_done') : __('quiz.search_tip') }}
    </p>

    <div class="mt-auto px-6 pb-6 pt-4 safe-bottom grid grid-cols-2 gap-3">
        <button id="hintBtn" type="button" class="btn btn-secondary" wire:click="useHint" @disabled($hintsLeft <= 0)><i class="ph ph-lightbulb"></i> {{ __('quiz.hint') }}</button>
        <button id="continueBtn" type="button" class="btn btn-primary" wire:click="finishSearch" @disabled(count($foundWords) < count($placements) || $placements === [])><i class="ph-fill ph-arrow-right"></i> {{ __('quiz.continue') }}</button>
    </div>

    @include('partials.pack-result')
    @include('partials.play-break')
</main>
