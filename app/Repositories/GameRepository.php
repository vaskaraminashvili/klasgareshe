<?php

namespace App\Repositories;

use App\Enums\GameType;
use App\Enums\GameVisibility;
use App\Models\Game;
use App\Models\User;
use Illuminate\Support\Collection;

class GameRepository
{
    /**
     * Catalog / program games: no owner, public, identified by GameType slug.
     */
    public function findBySlug(GameType $type): ?Game
    {
        return Game::query()
            ->where('slug', $type)
            ->whereNull('user_id')
            ->where('visibility', GameVisibility::Public)
            ->first();
    }

    public function find(int $id): ?Game
    {
        return Game::query()->find($id);
    }

    public function findAccessible(int $id, ?User $user): ?Game
    {
        return Game::query()
            ->accessibleTo($user)
            ->whereKey($id)
            ->first();
    }

    /**
     * Public catalog games plus the parent's own games.
     *
     * @return Collection<int, Game>
     */
    public function listForUser(?User $user): Collection
    {
        return Game::query()
            ->accessibleTo($user)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createSystem(array $attributes): Game
    {
        $attributes['user_id'] = null;
        $attributes['visibility'] = $attributes['visibility'] ?? GameVisibility::Public;

        return Game::query()->create($attributes);
    }

    /**
     * Parent-created games are private by default.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createForParent(User $owner, array $attributes): Game
    {
        $attributes['user_id'] = $owner->id;
        $attributes['visibility'] = $attributes['visibility'] ?? GameVisibility::Private;

        return Game::query()->create($attributes);
    }

    /**
     * @param  list<int>  $questionIds
     */
    public function syncQuestions(Game $game, array $questionIds): void
    {
        $game->questions()->sync($questionIds);
    }
}
