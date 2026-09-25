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
    public array $picOrder = [];

    public ?int $activeWord = null;

    /** @var array<int, int> */
    public array $links = [];

    public function title(): string
    {
        return __('quiz.page_title_match');
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
        $this->picOrder = array_reverse(array_keys($this->deck));
    }

    public function selectWord(int $index): void
    {
        if ($this->showResult || $this->settled || ! isset($this->deck[$index])) {
            return;
        }

        $this->activeWord = $index;
    }

    public function selectPic(int $index): void
    {
        if ($this->activeWord === null || $this->showResult || $this->settled || ! isset($this->deck[$index])) {
            return;
        }

        $next = [];

        foreach ($this->links as $word => $pic) {
            if ($pic !== $index && $word !== $this->activeWord) {
                $next[$word] = $pic;
            }
        }

        $next[$this->activeWord] = $index;
        $this->links = $next;
        $this->activeWord = null;
    }

    public function checkBoard(GamePlayService $play, UserRepository $users, BadgeService $badges): void
    {
        if ($this->showResult || $this->deck === [] || count($this->links) < count($this->deck)) {
            return;
        }

        $correct = 0;

        foreach ($this->links as $wordIndex => $picIndex) {
            $word = $this->deck[$wordIndex] ?? null;
            $pic = $this->deck[$picIndex] ?? null;

            if ($word === null || $pic === null) {
                continue;
            }

            if ($play->gradeInput($word['id'], $pic['emoji'])->correct) {
                $correct++;
            }
        }

        $this->correctCount = $correct;
        $this->combo = $correct;
        $this->maxCombo = $correct;
        $this->answered = true;
        $this->finishPack($play, $users, $badges, true);
    }

    public function linkedPic(int $wordIndex): ?int
    {
        return $this->links[$wordIndex] ?? null;
    }

    protected function expectedGameType(): GameType
    {
        return GameType::MatchWord;
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top" wire:poll.15s="heartbeat">
    <header class="appbar">
        <a href="{{ route('daily-mission') }}" wire:navigate class="icon-btn" data-back aria-label="{{ __('quiz.close') }}"><i class="ph ph-x"></i></a>
        <div class="grow"><div class="progress"><span id="progressFill" style="width:{{ count($deck) === 0 ? 0 : (int) round((count($links) / count($deck)) * 100) }}%"></span></div></div>
        <span class="chip chip-primary" id="scoreChip">{{ count($links) }} / {{ count($deck) }}</span>
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
        <p class="text-xs font-extrabold text-muted tracking-wider">{{ __('quiz.match_words') }}</p>
        <h1 class="h-display text-xl mt-1">{{ __('quiz.match_help') }}</h1>
    </section>

    <section class="px-5 mt-5 grid grid-cols-2 gap-3">
        <div class="space-y-3" id="words">
            @foreach ($deck as $wordIndex => $card)
                <button type="button" class="k-card p-3 text-center font-extrabold text-ink w-full {{ $activeWord === $wordIndex || $this->linkedPic($wordIndex) !== null ? 'ring-2 ring-primary' : '' }}" data-word="{{ $wordIndex }}" wire:click="selectWord({{ $wordIndex }})">{{ $card['prompt'] }}</button>
            @endforeach
        </div>
        <div class="space-y-3" id="pics">
            @foreach ($picOrder as $deckIndex)
                <button type="button" class="k-card aspect-[16/9] grid place-items-center text-5xl w-full {{ in_array($deckIndex, $links, true) ? 'ring-2 ring-primary' : '' }}" data-pic="{{ $deckIndex }}" wire:click="selectPic({{ $deckIndex }})">{{ $deck[$deckIndex]['emoji'] }}</button>
            @endforeach
        </div>
    </section>

    <section class="px-6 mt-4">
        <div class="k-card flex items-center gap-3">
            <div class="mascot size-11 text-xl shrink-0">🦉</div>
            <p id="tip" class="text-sm text-ink"><span class="font-extrabold">{{ __('quiz.tip') }}:</span> {{ __('quiz.match_help') }}</p>
        </div>
    </section>

    <div class="mt-auto px-6 pb-6 pt-4 safe-bottom">
        <button id="checkBtn" type="button" class="btn btn-primary w-full" wire:click="checkBoard" @disabled(count($links) < count($deck) || $deck === [])>
            <i class="ph-fill ph-check"></i> {{ __('quiz.check_answers') }}
        </button>
    </div>

    @include('partials.pack-result')
    @include('partials.play-break')
</main>
