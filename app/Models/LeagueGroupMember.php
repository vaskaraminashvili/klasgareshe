<?php

namespace App\Models;

use App\Enums\LeagueOutcome;
use Database\Factories\LeagueGroupMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $league_group_id
 * @property int $user_id
 * @property int $week_xp
 * @property int|null $finish_rank
 * @property LeagueOutcome|null $outcome
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'league_group_id',
    'user_id',
    'week_xp',
    'finish_rank',
    'outcome',
])]
class LeagueGroupMember extends Model
{
    /** @use HasFactory<LeagueGroupMemberFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'week_xp' => 'integer',
            'finish_rank' => 'integer',
            'outcome' => LeagueOutcome::class,
        ];
    }

    /**
     * @return BelongsTo<LeagueGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(LeagueGroup::class, 'league_group_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
