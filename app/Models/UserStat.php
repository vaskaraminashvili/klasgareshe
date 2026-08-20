<?php

namespace App\Models;

use App\Enums\League;
use Database\Factories\UserStatFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $xp
 * @property int $current_streak
 * @property int $longest_streak
 * @property Carbon|null $last_played_on
 * @property League $league
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'xp',
    'current_streak',
    'longest_streak',
    'last_played_on',
    'league',
])]
class UserStat extends Model
{
    /** @use HasFactory<UserStatFactory> */
    use HasFactory;

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'xp' => 0,
            'current_streak' => 0,
            'longest_streak' => 0,
            'last_played_on' => null,
            'league' => League::Bronze,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'xp' => 'integer',
            'current_streak' => 'integer',
            'longest_streak' => 'integer',
            'last_played_on' => 'date',
            'league' => League::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
