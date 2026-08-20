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
            'locale' => 'en',
            'prompt' => 'What animal says "Moo"?',
            'hint' => null,
            'media' => [
                'emoji' => '🐄',
                'tile' => 'tile-mint',
            ],
            'payload' => [
                'choices' => [
                    ['key' => 'A', 'label' => 'Dog', 'emoji' => '🐶'],
                    ['key' => 'B', 'label' => 'Cow', 'emoji' => '🐄'],
                    ['key' => 'C', 'label' => 'Cat', 'emoji' => '🐱'],
                    ['key' => 'D', 'label' => 'Sheep', 'emoji' => '🐑'],
                ],
            ],
            'answer' => ['key' => 'B'],
            'is_active' => true,
        ];
    }
}
