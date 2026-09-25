<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $week_plan_item_id
 * @property string $game_slug
 * @property int $correct_count
 * @property Carbon $played_on
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'week_plan_item_id',
    'game_slug',
    'correct_count',
    'played_on',
])]
class PackPlay extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'correct_count' => 'integer',
            'played_on' => 'date',
        ];
    }
}
