<?php

namespace Database\Factories;

use App\Enums\FavouriteSubject;
use App\Enums\GameType;
use App\Enums\QuestionFormat;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'format' => QuestionFormat::Choice,
            'source' => GameType::MultipleChoice,
            'subject' => FavouriteSubject::Animals,
            'age_group' => null,
            'locale' => 'ka',
            'prompt' => 'რა ცხოველი ამბობს „მუ“?',
            'hint' => null,
            'media' => [
                'emoji' => '🐄',
                'tile' => 'tile-mint',
            ],
            'payload' => [
                'choices' => [
                    ['key' => 'A', 'label' => 'ძაღლი', 'emoji' => '🐶'],
                    ['key' => 'B', 'label' => 'ძროხა', 'emoji' => '🐄'],
                    ['key' => 'C', 'label' => 'კატა', 'emoji' => '🐱'],
                    ['key' => 'D', 'label' => 'ცხვარი', 'emoji' => '🐑'],
                ],
            ],
            'answer' => ['key' => 'B'],
            'is_active' => true,
        ];
    }
}
