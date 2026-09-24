<?php

namespace App\Models;

use App\Enums\League;
use Database\Factories\UserStatFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $xp
 * @property int $coins
 * @property int $current_streak
 * @property int $longest_streak
 * @property int $streak_freezes
 * @property Carbon|null $last_played_on
 * @property League $league
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'xp',
    'coins',
    'current_streak',
    'longest_streak',
    'streak_freezes',
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
            'coins' => 0,
            'current_streak' => 0,
            'longest_streak' => 0,
            'streak_freezes' => 0,
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
            'coins' => 'integer',
            'current_streak' => 'integer',
            'longest_streak' => 'integer',
            'streak_freezes' => 'integer',
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

    /**
     * Public ranking rows — one place so no leaderboard query can skip the privacy flag.
     *
     * @param  Builder<UserStat>  $query
     * @return Builder<UserStat>
     */
    public function scopeVisibleOnLeaderboard(Builder $query): Builder
    {
        return $query->whereIn(
            $query->qualifyColumn('user_id'),
            User::query()->visibleOnLeaderboard()->select('id'),
        );
    }
}
