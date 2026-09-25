<?php

namespace App\Repositories;

use App\Models\PackPlay;
use App\Models\User;

class PackPlayRepository
{
    public function record(User $user, ?int $itemId, string $gameSlug, int $correctCount): void
    {
        PackPlay::query()->create([
            'user_id' => $user->id,
            'week_plan_item_id' => $itemId,
            'game_slug' => $gameSlug,
            'correct_count' => max(0, $correctCount),
            'played_on' => now()->toDateString(),
        ]);
    }

    public function correctOn(User $user, int $itemId, string $date): ?int
    {
        $best = PackPlay::query()
            ->where('user_id', $user->id)
            ->where('week_plan_item_id', $itemId)
            ->whereDate('played_on', $date)
            ->max('correct_count');

        if ($best === null) {
            return null;
        }

        return (int) $best;
    }
}
