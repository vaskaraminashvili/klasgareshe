<?php

namespace App\Repositories;

use App\Models\Question;

class QuestionRepository
{
    public function find(int $id): ?Question
    {
        return Question::query()->find($id);
    }

    /**
     * @param  list<int>  $ids
     * @return list<Question>
     */
    public function findMany(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $questions = Question::query()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $ordered = [];

        foreach ($ids as $id) {
            $question = $questions->get($id);

            if ($question !== null) {
                $ordered[] = $question;
            }
        }

        return $ordered;
    }

    /**
     * @return list<Question>
     */
    public function randomForGame(int $gameId, string $locale, int $limit): array
    {
        return array_values(
            Question::query()
                ->where('locale', $locale)
                ->where('is_active', true)
                ->whereHas('games', fn ($query) => $query->where('games.id', $gameId))
                ->inRandomOrder()
                ->limit($limit)
                ->get()
                ->all(),
        );
    }

    public function attachToGame(Question $question, int $gameId): void
    {
        $question->games()->syncWithoutDetaching([$gameId]);
    }

    /**
     * @param  list<int>  $gameIds
     */
    public function syncGames(Question $question, array $gameIds): void
    {
        $question->games()->sync($gameIds);
    }
}
