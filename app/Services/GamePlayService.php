<?php

namespace App\Services;

use App\Data\ChoiceGrade;
use App\Data\ChoiceQuestionView;
use App\Data\GameRound;
use App\Enums\GameType;
use App\Enums\QuestionFormat;
use App\Enums\XpSource;
use App\Models\Question;
use App\Models\User;
use App\Repositories\GameRepository;
use App\Repositories\QuestionRepository;
use InvalidArgumentException;
use RuntimeException;

class GamePlayService
{
    public function __construct(
        private GameRepository $games,
        private QuestionRepository $questions,
        private UserStatService $stats,
        private WeekPlanService $weekPlan,
        private BadgeService $badges,
        private QuestionPlayModeResolver $playModes,
    ) {}

    public function startPlanItem(User $user, int $itemId): GameRound
    {
        try {
            $item = $this->weekPlan->findPlayable($user, $itemId);
        } catch (InvalidArgumentException $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        }

        $game = $this->games->findBySlug($item->game_slug);

        if ($game === null || ! $game->is_active) {
            throw new RuntimeException('Game is not available.');
        }

        $ids = $this->weekPlan->questionIds($item);

        if ($ids === []) {
            throw new RuntimeException('No questions are available for this game.');
        }

        return new GameRound(
            game: $item->game_slug,
            lives: $game->lives,
            xpPerCorrect: $game->xp_per_correct,
            questionIds: $ids,
            weekPlanItemId: $item->id,
        );
    }

    public function startRound(GameType $type, ?string $locale = null): GameRound
    {
        $locale ??= app()->getLocale();

        $game = $this->games->findBySlug($type);

        if ($game === null || ! $game->is_active) {
            throw new RuntimeException('Game is not available.');
        }

        $picked = $this->questions->randomForGame(
            $game->id,
            $locale,
            $game->questions_per_round,
        );

        if ($picked === [] && $locale !== 'ka') {
            $picked = $this->questions->randomForGame(
                $game->id,
                'ka',
                $game->questions_per_round,
            );
        }

        if ($picked === []) {
            throw new RuntimeException('No questions are available for this game.');
        }

        $ids = [];

        foreach ($picked as $question) {
            $ids[] = $question->id;
        }

        return new GameRound(
            game: $type,
            lives: $game->lives,
            xpPerCorrect: $game->xp_per_correct,
            questionIds: $ids,
        );
    }

    public function presentChoice(int $questionId): ChoiceQuestionView
    {
        $views = $this->presentChoices([$questionId]);

        if ($views === []) {
            throw new InvalidArgumentException('Choice question not found.');
        }

        return $views[0];
    }

    /**
     * @param  list<int>  $ids
     * @return list<ChoiceQuestionView>
     */
    public function presentChoices(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $found = [];

        foreach ($this->questions->findMany($ids) as $question) {
            $found[$question->id] = $question;
        }

        $views = [];

        foreach ($ids as $id) {
            $question = $found[$id] ?? null;

            if ($question === null || $question->format !== QuestionFormat::Choice) {
                throw new InvalidArgumentException('Choice question not found.');
            }

            $views[] = $this->viewFrom($question);
        }

        return $views;
    }

    public function gradeChoice(int $questionId, string $key): ChoiceGrade
    {
        $question = $this->choiceQuestion($questionId);
        $correctKey = $question->correctKey();

        return new ChoiceGrade(
            correct: $key === $correctKey,
            correctKey: $correctKey,
        );
    }

    public function award(
        User $user,
        GameType $type,
        int $correctCount,
        ?int $weekPlanItemId = null,
        int $maxCombo = 0,
        ?int $elapsedSeconds = null,
        int $questionTotal = 0,
        bool $completedAll = false,
    ): int {
        $game = $this->games->findBySlug($type);

        if ($game === null) {
            throw new RuntimeException('Game is not available.');
        }

        $xp = max(0, $correctCount) * $game->xp_per_correct;
        $item = $weekPlanItemId !== null ? $this->weekPlan->findItem($weekPlanItemId) : null;
        $subject = $item?->subject;
        $date = now()->toDateString();
        $packKey = (string) ($weekPlanItemId ?? 'open');

        $this->stats->awardXp($user, XpSource::Pack, $xp, subject: $subject, context: $weekPlanItemId !== null ? 'pack-'.$weekPlanItemId : null);

        if ($weekPlanItemId !== null) {
            $this->weekPlan->completeItem($user, $weekPlanItemId, $correctCount);
        }

        if ($questionTotal >= 5 && $maxCombo >= 5) {
            $this->stats->awardXp(
                $user,
                XpSource::Combo,
                20,
                subject: $subject,
                context: 'combo-'.$packKey.'-'.$date,
            );
            $xp += 20;
        }

        if ($questionTotal >= 5 && $completedAll && $elapsedSeconds !== null && $elapsedSeconds < 120) {
            $this->stats->awardXp(
                $user,
                XpSource::Speed,
                20,
                subject: $subject,
                context: 'speed-'.$packKey.'-'.$date,
            );
            $xp += 20;
        }

        if ($weekPlanItemId !== null && $this->weekPlan->dailyMissionJustCompleted($user)) {
            $this->stats->awardXp(
                $user,
                XpSource::DailyMission,
                120,
                context: $date,
            );
            $xp += 120;
        }

        $this->badges->evaluate($user);

        return $xp;
    }

    private function choiceQuestion(int $questionId): Question
    {
        $question = $this->questions->find($questionId);

        if ($question === null || $question->format !== QuestionFormat::Choice) {
            throw new InvalidArgumentException('Choice question not found.');
        }

        return $question;
    }

    private function viewFrom(Question $question): ChoiceQuestionView
    {
        $choices = $question->choices();
        $labels = [];

        foreach ($choices as $choice) {
            $labels[] = $choice['label'];
        }

        $shape = $this->playModes->forPrompt(
            (string) $question->prompt,
            $this->correctLabel($question, $choices),
            $labels,
        );

        return new ChoiceQuestionView(
            id: $question->id,
            prompt: (string) $question->prompt,
            emoji: $question->mediaEmoji(),
            tile: $question->mediaTile(),
            choices: $choices,
            playMode: $shape->mode,
            letters: $shape->letters,
            countItems: $shape->countItems,
        );
    }

    /**
     * @param  list<array{key: string, label: string, emoji: string}>  $choices
     */
    private function correctLabel(Question $question, array $choices): string
    {
        $key = $question->correctKey();

        foreach ($choices as $choice) {
            if ($choice['key'] === $key) {
                return $choice['label'];
            }
        }

        return '';
    }
}
