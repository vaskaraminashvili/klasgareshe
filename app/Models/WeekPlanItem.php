<?php

namespace App\Models;

use App\Enums\GameType;
use App\Enums\SchoolGrade;
use App\Enums\SchoolSubject;
use Database\Factories\WeekPlanItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property SchoolGrade $grade
 * @property int $week_number
 * @property int $weekday
 * @property SchoolSubject $subject
 * @property int $level
 * @property string $title
 * @property GameType $game_slug
 * @property int $questions_per_round
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'grade',
    'week_number',
    'weekday',
    'subject',
    'level',
    'title',
    'game_slug',
    'questions_per_round',
])]
class WeekPlanItem extends Model
{
    /** @use HasFactory<WeekPlanItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'grade' => SchoolGrade::class,
            'week_number' => 'integer',
            'weekday' => 'integer',
            'subject' => SchoolSubject::class,
            'level' => 'integer',
            'game_slug' => GameType::class,
            'questions_per_round' => 'integer',
        ];
    }

    /**
     * @return BelongsToMany<Question, $this>
     */
    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'week_plan_item_question')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * @return HasMany<UserPlanProgress, $this>
     */
    public function progress(): HasMany
    {
        return $this->hasMany(UserPlanProgress::class);
    }
}
