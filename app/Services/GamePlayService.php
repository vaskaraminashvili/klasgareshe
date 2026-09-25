<?php

namespace App\Services;

use App\Data\ChoiceGrade;
use App\Data\ChoiceQuestionView;
use App\Data\GameRound;
use App\Enums\GameType;
use App\Enums\PlayDifficulty;
use App\Enums\QuestionFormat;
use App\Enums\XpSource;
use App\Models\Question;
use App\Models\User;
use App\Repositories\GameRepository;
use App\Repositories\PackPlayRepository;
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
        private PackPlayRepository $plays,
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

        $ids = $this->weekPlan->questionIds($item, $user);

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

            if ($question === null || ! $this->isPlayableFormat($question->format)) {
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

    public function gradeInput(int $questionId, string $input): ChoiceGrade
    {
        $question = $this->choiceQuestion($questionId);
        $key = $this->resolveChoiceKey($question, $input);

        if ($key === null) {
            return new ChoiceGrade(
                correct: false,
                correctKey: $question->correctKey(),
            );
        }

        return $this->gradeChoice($questionId, $key);
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

        $xp = $this->scaledPackXp($user, max(0, $correctCount) * $game->xp_per_correct);
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
        $this->plays->record($user, $weekPlanItemId, $type->value, max(0, $correctCount));

        return $xp;
    }

    public function yesterdayCorrect(User $user, ?int $itemId): ?int
    {
        if ($itemId === null) {
            return null;
        }

        return $this->plays->correctOn($user, $itemId, now()->subDay()->toDateString());
    }

    /**
     * Keys of wrong choices, for the knowledge 50/50 lifeline.
     *
     * @return list<string>
     */
    public function wrongKeys(int $questionId, int $limit = 2): array
    {
        $question = $this->choiceQuestion($questionId);
        $correct = $question->correctKey();
        $wrong = [];

        foreach ($question->choices() as $choice) {
            if ($choice['key'] !== $correct) {
                $wrong[] = $choice['key'];
            }
        }

        return array_slice($wrong, 0, max(0, $limit));
    }

    private function choiceQuestion(int $questionId): Question
    {
        $question = $this->questions->find($questionId);

        if ($question === null || ! $this->isPlayableFormat($question->format)) {
            throw new InvalidArgumentException('Choice question not found.');
        }

        return $question;
    }

    private function isPlayableFormat(QuestionFormat $format): bool
    {
        return match ($format) {
            QuestionFormat::Choice,
            QuestionFormat::Count,
            QuestionFormat::Spell,
            QuestionFormat::Pairs,
            QuestionFormat::Grid,
            QuestionFormat::Trace => true,
            QuestionFormat::Hotspot => false,
        };
    }

    private function scaledPackXp(User $user, int $base): int
    {
        $difficulty = $user->play_difficulty ?? PlayDifficulty::Medium;
        $scaled = intdiv($base * $difficulty->xpMultiplierNumerator(), $difficulty->xpMultiplierDenominator());

        return max(0, $scaled);
    }

    private function viewFrom(Question $question): ChoiceQuestionView
    {
        $choices = $question->choices();
        $shape = $this->playModes->forQuestion($question);
        $hint = $question->hint;
        $pair = $this->pairFace($question, $choices);
        $payload = $question->payload;
        $slots = isset($payload['slots']) && is_numeric($payload['slots']) ? (int) $payload['slots'] : 0;

        return new ChoiceQuestionView(
            id: $question->id,
            prompt: (string) $question->prompt,
            emoji: $question->mediaEmoji(),
            tile: $question->mediaTile(),
            choices: $choices,
            playMode: $shape->mode,
            letters: $shape->letters,
            countItems: $shape->countItems,
            hint: is_string($hint) ? $hint : '',
            keyboard: $this->stringList($payload['keyboard'] ?? null),
            strokes: $this->strokes($payload['strokes'] ?? null),
            pairLabel: $pair['label'],
            pairEmoji: $pair['emoji'],
            slots: $slots,
        );
    }

    /**
     * @param  list<array{key: string, label: string, emoji: string}>  $choices
     * @return array{label: string, emoji: string}
     */
    private function pairFace(Question $question, array $choices): array
    {
        if ($question->format !== QuestionFormat::Pairs) {
            return ['label' => '', 'emoji' => ''];
        }

        try {
            $key = $question->correctKey();
        } catch (InvalidArgumentException) {
            return ['label' => '', 'emoji' => ''];
        }

        foreach ($choices as $choice) {
            if ($choice['key'] === $key) {
                return ['label' => $choice['label'], 'emoji' => $choice['emoji']];
            }
        }

        return ['label' => '', 'emoji' => ''];
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];

        foreach ($value as $item) {
            if (is_string($item) && $item !== '') {
                $out[] = $item;
            }
        }

        return $out;
    }

    /**
     * @return list<list<array{0: float, 1: float}>>
     */
    private function strokes(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $strokes = [];

        foreach ($value as $stroke) {
            if (! is_array($stroke)) {
                continue;
            }

            $points = [];

            foreach ($stroke as $point) {
                if (! is_array($point) || ! isset($point[0], $point[1])) {
                    continue;
                }

                if (! is_numeric($point[0]) || ! is_numeric($point[1])) {
                    continue;
                }

                $points[] = [(float) $point[0], (float) $point[1]];
            }

            if ($points !== []) {
                $strokes[] = $points;
            }
        }

        return $strokes;
    }

    private function resolveChoiceKey(Question $question, string $input): ?string
    {
        foreach ($question->choices() as $choice) {
            if ($choice['key'] === $input) {
                return $choice['key'];
            }
        }

        foreach ($question->choices() as $choice) {
            if ($choice['label'] === $input) {
                return $choice['key'];
            }
        }

        return null;
    }
}
