<?php

namespace App\Models;

use App\Enums\League;
use Database\Factories\LeagueGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $league_week_id
 * @property League $tier
 * @property int $capacity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'league_week_id',
    'tier',
    'capacity',
])]
class LeagueGroup extends Model
{
    /** @use HasFactory<LeagueGroupFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tier' => League::class,
            'capacity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<LeagueWeek, $this>
     */
    public function week(): BelongsTo
    {
        return $this->belongsTo(LeagueWeek::class, 'league_week_id');
    }

    /**
     * @return HasMany<LeagueGroupMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(LeagueGroupMember::class);
    }
}
