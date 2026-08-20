<?php

namespace App\Repositories;

use App\Enums\QuestionFormat;
use App\Models\Question;

class QuestionRepository
{
    public function find(int $id): ?Question
    {
        return Question::query()->find($id);
    }

    /**
     * @return list<Question>
     */
    public function randomForFormat(QuestionFormat $format, string $locale, int $limit): array
    {
        return array_values(
            Question::query()
                ->where('format', $format)
                ->where('locale', $locale)
                ->where('is_active', true)
                ->inRandomOrder()
                ->limit($limit)
                ->get()
                ->all(),
        );
    }
}
