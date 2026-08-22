<?php

namespace Database\Factories;

use App\Enums\GameType;
use App\Enums\SchoolGrade;
use App\Enums\SchoolSubject;
use App\Models\WeekPlanItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WeekPlanItem>
 */
class WeekPlanItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $weekday = fake()->numberBetween(1, 7);

        return [
            'grade' => SchoolGrade::First,
            'week_number' => 1,
            'weekday' => $weekday,
            'subject' => SchoolSubject::Math,
            'level' => $weekday,
            'title' => 'რიცხვები 1–5',
            'game_slug' => GameType::MultipleChoice,
            'questions_per_round' => 5,
        ];
    }
}
