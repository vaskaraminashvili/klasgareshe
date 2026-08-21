<?php

namespace App\Services;

use App\Data\ChoiceGrade;
use App\Data\ChoiceQuestionView;
use App\Data\GameRound;
use App\Enums\GameType;
use App\Enums\QuestionFormat;
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
    ) {}

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
        $question = $this->choiceQuestion($questionId);

        return new ChoiceQuestionView(
            id: $question->id,
            prompt: (string) $question->prompt,
            emoji: $question->mediaEmoji(),
            tile: $question->mediaTile(),
            choices: $question->choices(),
        );
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

    public function award(User $user, GameType $type, int $correctCount): int
    {
        $game = $this->games->findBySlug($type);

        if ($game === null) {
            throw new RuntimeException('Game is not available.');
        }

        $xp = max(0, $correctCount) * $game->xp_per_correct;

        $this->stats->recordPlay($user, $xp);

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
}
