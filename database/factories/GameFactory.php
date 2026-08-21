<?php

namespace Database\Factories;

use App\Enums\GameType;
use App\Enums\GameVisibility;
use App\Models\Game;
use App\Models\User;
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
            'user_id' => null,
            'slug' => $type,
            'format' => $type->format(),
            'lives' => $defaults['lives'],
            'questions_per_round' => $defaults['questions_per_round'],
            'xp_per_correct' => $defaults['xp_per_correct'],
            'is_active' => true,
            'visibility' => GameVisibility::Public,
        ];
    }

    public function private(): static
    {
        return $this->state(fn (): array => [
            'visibility' => GameVisibility::Private,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (): array => [
            'user_id' => $user->id,
            'visibility' => GameVisibility::Private,
        ]);
    }
}
