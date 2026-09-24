<?php

namespace App\Livewire\Concerns;

use App\Data\ChoiceGrade;
use App\Enums\GameType;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\BadgeService;
use App\Services\GamePlayService;
use App\Services\ScreenTimeService;
use App\Services\WeekPlanService;
use Livewire\Attributes\Locked;

trait PlaysWeekPlanPack
{
    /** @var list<int> */
    #[Locked]
    public array $questionIds = [];

    /**
     * @var list<array{id: int, prompt: string, emoji: string, tile: string, playMode: string, letters: list<array{char: string, blank: bool}>, countItems: list<string>, choices: list<array{key: string, label: string, emoji: string}>, hint: string}>
     */
    #[Locked]
    public array $deck = [];

    #[Locked]
    public int $lives = 3;

    #[Locked]
    public int $correctCount = 0;

    #[Locked]
    public bool $settled = false;

    #[Locked]
    public ?int $planItemId = null;

    #[Locked]
    public int $combo = 0;

    #[Locked]
    public int $maxCombo = 0;

    #[Locked]
    public string $startedAt = '';

    #[Locked]
    public string $gameSlug = 'multiple-choice';

    public ?int $item = null;

    public int $index = 0;

    public string $prompt = '';

    public string $emoji = '';

    public string $tile = 'tile-mint';

    public string $hint = '';

    /** @var list<array{key: string, label: string, emoji: string}> */
    public array $choices = [];

    public string $playMode = 'choice';

    /** @var list<array{char: string, blank: bool}> */
    public array $letters = [];

    /** @var list<string> */
    public array $countItems = [];

    public bool $answered = false;

    public ?string $pickedKey = null;

    public ?string $correctKey = null;

    public bool $showBreak = false;

    public bool $showWarn = false;

    public bool $showBedtimeSoon = false;

    abstract protected function expectedGameType(): GameType;

    public function mount(GamePlayService $play, UserRepository $users, WeekPlanService $week, ScreenTimeService $time, ?int $item = null): void
    {
        $this->bootPack($play, $users, $week, $time, $item);
    }

    public function heartbeat(ScreenTimeService $time, UserRepository $users): void
    {
        if ($this->settled) {
            return;
        }

        $user = $users->authenticated();
        $blocked = $time->tick($user);

        if ($blocked !== null) {
            $this->redirectRoute('play-paused', navigate: true);

            return;
        }

        $this->refreshPlayGates($time, $user);
    }

    public function dismissBreak(ScreenTimeService $time, UserRepository $users): void
    {
        $time->dismissBreak($users->authenticated());
        $this->showBreak = false;
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

        $this->applyGrade($play->gradeChoice($this->questionIds[$this->index], $key), $key);
    }

    public function next(GamePlayService $play, UserRepository $users, BadgeService $badges): void
    {
        if (! $this->answered || $this->settled) {
            return;
        }

        $last = $this->index >= count($this->questionIds) - 1;

        if ($last || $this->lives === 0) {
            $this->settled = true;
            $elapsed = max(0, (int) now()->diffInSeconds($this->startedAt));
            $play->award(
                $users->authenticated(),
                GameType::from($this->gameSlug),
                $this->correctCount,
                $this->planItemId,
                $this->maxCombo,
                $elapsed,
                count($this->questionIds),
                $last,
            );
            $slug = $badges->firstUnseenSlug($users->authenticated());

            if (is_string($slug)) {
                $this->redirectRoute('badge-unlock', ['slug' => $slug]);

                return;
            }

            $this->redirectRoute('home');

            return;
        }

        $this->index++;
        $this->answered = false;
        $this->pickedKey = null;
        $this->correctKey = null;
        $this->showCurrent();
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

    protected function bootPack(GamePlayService $play, UserRepository $users, WeekPlanService $week, ScreenTimeService $time, ?int $item = null): void
    {
        $user = $users->authenticated();

        if ($time->blockReason($user) !== null) {
            $this->redirectRoute('play-paused', navigate: true);

            return;
        }

        $itemId = $item ?? $this->item;
        $expected = $this->expectedGameType();

        if ($itemId === null) {
            $next = $week->firstIncompleteOfType($user, $expected)
                ?? $week->firstIncomplete($user);

            if ($next === null) {
                $this->redirectRoute('home', navigate: true);

                return;
            }

            $this->redirectRoute($next->game_slug->playerRoute(), ['item' => $next->id], navigate: true);

            return;
        }

        try {
            $round = $play->startPlanItem($user, $itemId);
        } catch (\RuntimeException) {
            $this->redirectRoute('home', navigate: true);

            return;
        }

        if ($round->game !== $expected) {
            $this->redirectRoute($round->game->playerRoute(), ['item' => $itemId], navigate: true);

            return;
        }

        $this->planItemId = $round->weekPlanItemId;
        $this->gameSlug = $round->game->value;
        $this->questionIds = $round->questionIds;
        $this->lives = $round->lives;
        $this->combo = 0;
        $this->maxCombo = 0;
        $this->startedAt = now()->toIso8601String();
        $this->deck = [];

        foreach ($play->presentChoices($round->questionIds) as $view) {
            $this->deck[] = $view->toArray();
        }

        $this->showCurrent();
        $time->tick($user);
        $this->refreshPlayGates($time, $user);
    }

    protected function applyGrade(ChoiceGrade $grade, ?string $pickedKey): void
    {
        $this->answered = true;
        $this->pickedKey = $pickedKey;
        $this->correctKey = $grade->correctKey;

        if ($grade->correct) {
            $this->correctCount++;
            $this->combo++;
            $this->maxCombo = max($this->maxCombo, $this->combo);

            return;
        }

        $this->lives = max(0, $this->lives - 1);
        $this->combo = 0;
    }

    protected function showCurrent(): void
    {
        $view = $this->deck[$this->index] ?? null;

        if ($view === null) {
            return;
        }

        $this->prompt = $view['prompt'];
        $this->emoji = $view['emoji'];
        $this->tile = $view['tile'];
        $this->hint = $view['hint'];
        $this->choices = $view['choices'];
        $this->playMode = $view['playMode'];
        $this->letters = $view['letters'];
        $this->countItems = $view['countItems'];
        $this->afterShowCurrent();
    }

    protected function afterShowCurrent(): void
    {
        //
    }

    protected function refreshPlayGates(ScreenTimeService $time, User $user): void
    {
        $this->showWarn = $time->shouldWarn($user);
        $this->showBreak = $time->shouldBreak($user);
        $this->showBedtimeSoon = $time->bedtimeSoon($user);
    }
}
