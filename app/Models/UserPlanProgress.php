<?php

namespace App\Models;

use App\Enums\PlanProgressStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $week_plan_item_id
 * @property PlanProgressStatus $status
 * @property int $correct_count
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'week_plan_item_id',
    'status',
    'correct_count',
    'completed_at',
])]
class UserPlanProgress extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PlanProgressStatus::class,
            'correct_count' => 'integer',
            'completed_at' => 'datetime',
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
     * @return BelongsTo<WeekPlanItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(WeekPlanItem::class, 'week_plan_item_id');
    }
}
