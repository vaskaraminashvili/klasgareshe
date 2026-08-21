<?php

namespace App\Repositories;

use App\Enums\GameType;
use App\Models\Game;

class GameRepository
{
    public function findBySlug(GameType $type): ?Game
    {
        return Game::query()->where('slug', $type)->first();
    }

    /**
     * @param  list<int>  $questionIds
     */
    public function syncQuestions(Game $game, array $questionIds): void
    {
        $game->questions()->sync($questionIds);
    }
}
