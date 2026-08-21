<?php

namespace App\Models;

use App\Enums\LeagueWeekStatus;
use Database\Factories\LeagueWeekFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon $starts_on
 * @property Carbon $ends_on
 * @property LeagueWeekStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'starts_on',
    'ends_on',
    'status',
])]
class LeagueWeek extends Model
{
    /** @use HasFactory<LeagueWeekFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'status' => LeagueWeekStatus::class,
        ];
    }

    /**
     * @return HasMany<LeagueGroup, $this>
     */
    public function groups(): HasMany
    {
        return $this->hasMany(LeagueGroup::class);
    }
}
