<?php

namespace App\Models;

use App\Enums\League;
use App\Enums\LeagueOutcome;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $league_week_id
 * @property League $tier
 * @property int $finish_rank
 * @property LeagueOutcome $outcome
 * @property int $stay_bonus_xp
 * @property int|null $prize_xp
 * @property Carbon|null $prize_claimed_at
 * @property Carbon|null $paid_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'league_week_id',
    'tier',
    'finish_rank',
    'outcome',
    'stay_bonus_xp',
    'prize_xp',
    'prize_claimed_at',
    'paid_at',
])]
class LeagueSeasonPayout extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tier' => League::class,
            'finish_rank' => 'integer',
            'outcome' => LeagueOutcome::class,
            'stay_bonus_xp' => 'integer',
            'prize_xp' => 'integer',
            'prize_claimed_at' => 'datetime',
            'paid_at' => 'datetime',
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
     * @return BelongsTo<LeagueWeek, $this>
     */
    public function week(): BelongsTo
    {
        return $this->belongsTo(LeagueWeek::class, 'league_week_id');
    }

    public function hasPrize(): bool
    {
        return $this->prize_xp !== null;
    }

    public function prizeClaimed(): bool
    {
        return $this->prize_claimed_at !== null;
    }
}
