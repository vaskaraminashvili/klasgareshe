<?php

namespace Database\Factories;

use App\Enums\GameType;
use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Game>
 */
class GameFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = GameType::MultipleChoice;
        $defaults = $type->playDefaults();

        return [
            'slug' => $type,
            'format' => $type->format(),
            'lives' => $defaults['lives'],
            'questions_per_round' => $defaults['questions_per_round'],
            'xp_per_correct' => $defaults['xp_per_correct'],
            'is_active' => true,
        ];
    }
}
