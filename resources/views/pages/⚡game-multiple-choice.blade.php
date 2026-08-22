<?php

use App\Enums\GameType;
use App\Repositories\UserRepository;
use App\Services\BadgeService;
use App\Services\GamePlayService;
use App\Services\WeekPlanService;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    /** @var list<int> */
    #[Locked]
    public array $questionIds = [];

    #[Locked]
    public int $lives = 3;

    #[Locked]
    public int $correctCount = 0;

    #[Locked]
    public bool $settled = false;

    #[Locked]
    public ?int $planItemId = null;

    public ?int $item = null;

    public int $index = 0;

    public string $prompt = '';

    public string $emoji = '';

    public string $tile = 'tile-mint';

    /** @var list<array{key: string, label: string, emoji: string}> */
    public array $choices = [];

    public bool $answered = false;

    public ?string $pickedKey = null;

    public ?string $correctKey = null;

    public function title(): string
    {
        return __('quiz.page_title');
    }

    public function rendering(View $view): void
    {
        $view->title($this->title());
    }

    public function mount(GamePlayService $play, UserRepository $users, WeekPlanService $week, ?int $item = null): void
    {
        $user = $users->authenticated();

        $itemId = $item ?? $this->item;

        if ($itemId === null) {
            $next = $week->firstIncomplete($user);

            if ($next === null) {
                $this->redirectRoute('home', navigate: true);

                return;
            }

            $this->redirectRoute('game-multiple-choice', ['item' => $next->id], navigate: true);

            return;
        }

        try {
            $round = $play->startPlanItem($user, $itemId);
        } catch (\RuntimeException) {
            $this->redirectRoute('home', navigate: true);

            return;
        }

        $this->planItemId = $round->weekPlanItemId;
        $this->questionIds = $round->questionIds;
        $this->lives = $round->lives;
        $this->showCurrent($play);
    }

    public function pick(string $key, GamePlayService $play): void
    {
        if ($this->answered || $this->settled || $this->questionIds === []) {
            return;
        }

        $allowed = array_column($this->choices, 'key');

        if (! in_array($key, $allowed, true)) {
            return;
        }

        $grade = $play->gradeChoice($this->questionIds[$this->index], $key);

        $this->answered = true;
        $this->pickedKey = $key;
        $this->correctKey = $grade->correctKey;

        if ($grade->correct) {
            $this->correctCount++;
        } else {
            $this->lives = max(0, $this->lives - 1);
        }
    }

    public function next(GamePlayService $play, UserRepository $users, BadgeService $badges): void
    {
        if (! $this->answered || $this->settled) {
            return;
        }

        $last = $this->index >= count($this->questionIds) - 1;

        if ($last || $this->lives === 0) {
            $this->settled = true;
            $play->award(
                $users->authenticated(),
                GameType::MultipleChoice,
                $this->correctCount,
                $this->planItemId,
            );
            $slug = $badges->firstUnseenSlug($users->authenticated());

            if (is_string($slug)) {
                $this->redirectRoute('badge-unlock', ['slug' => $slug], navigate: true);

                return;
            }

            $this->redirectRoute('home', navigate: true);

            return;
        }

        $this->index++;
        $this->answered = false;
        $this->pickedKey = null;
        $this->correctKey = null;
        $this->showCurrent($play);
    }

    public function progressPercent(): int
    {
        $total = count($this->questionIds);

        if ($total === 0) {
            return 0;
        }

        return (int) round((($this->index + 1) / $total) * 100);
    }

    public function choiceClass(string $key): string
    {
        if (! $this->answered) {
            return '';
        }

        if ($key === $this->correctKey) {
            return ' correct';
        }

        if ($key === $this->pickedKey) {
            return ' wrong';
        }

        return '';
    }

    private function showCurrent(GamePlayService $play): void
    {
        $view = $play->presentChoice($this->questionIds[$this->index]);

        $this->prompt = $view->prompt;
        $this->emoji = $view->emoji;
        $this->tile = $view->tile;
        $this->choices = $view->choices;
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top">
    <header class="appbar">
        <a href="{{ route('home') }}" class="icon-btn" data-back aria-label="{{ __('quiz.close') }}"><i
                class="ph ph-x"></i></a>
        <div class="grow">
            <div class="progress"><span style="width:{{ $this->progressPercent() }}%"></span></div>
        </div>
        <span class="chip chip-coral">❤️ {{ $lives }}</span>
    </header>

    <section class="px-6 mt-2 text-center" wire:key="prompt-{{ $questionIds[$index] ?? 0 }}">
        <p class="text-xs font-extrabold" style="color:var(--color-k-muted)">
            {{ __('quiz.question_n_of', ['current' => $index + 1, 'total' => count($questionIds)]) }}</p>
        <h1 class="h-display text-2xl mt-2">{{ $prompt }}</h1>
    </section>

    @if ($emoji !== '')
        <section class="px-6 mt-4">
            <div class="k-card-lg {{ $tile }} grid place-items-center aspect-[16/10]">
                <div class="text-8xl">{{ $emoji }}</div>
            </div>
        </section>
    @endif

    <section class="px-6 mt-5 space-y-3" data-ans-group wire:key="choices-{{ $questionIds[$index] ?? 0 }}">
        @foreach ($choices as $choice)
            <button type="button" class="ans{{ $this->choiceClass($choice['key']) }}" wire:click="pick('{{ $choice['key'] }}')"
                @disabled($answered)>
                <span class="ans-key">{{ $choice['key'] }}</span>
                <span class="grow">{{ $choice['label'] }}</span>
                @if ($choice['emoji'] !== '')
                    <span class="text-2xl">{{ $choice['emoji'] }}</span>
                @endif
            </button>
        @endforeach
    </section>

    <div class="mt-auto px-6 pb-6 pt-4 safe-bottom">
        <button type="button" class="btn btn-primary w-full" wire:click="next" @disabled(! $answered)>
            {{ __('quiz.next_question') }} <i class="ph ph-arrow-right"></i>
        </button>
    </div>
</main>
