<?php

namespace App\Models;

use App\Enums\GameType;
use App\Enums\GameVisibility;
use App\Enums\QuestionFormat;
use Database\Factories\GameFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string|null $title
 * @property GameType $slug
 * @property QuestionFormat $format
 * @property int $lives
 * @property int $questions_per_round
 * @property int $xp_per_correct
 * @property bool $is_active
 * @property GameVisibility $visibility
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'title',
    'slug',
    'format',
    'lives',
    'questions_per_round',
    'xp_per_correct',
    'is_active',
    'visibility',
])]
class Game extends Model
{
    /** @use HasFactory<GameFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'slug' => GameType::class,
            'format' => QuestionFormat::class,
            'lives' => 'integer',
            'questions_per_round' => 'integer',
            'xp_per_correct' => 'integer',
            'is_active' => 'boolean',
            'visibility' => GameVisibility::class,
        ];
    }

    public function isSystem(): bool
    {
        return $this->user_id === null;
    }

    public function isPublic(): bool
    {
        return $this->visibility === GameVisibility::Public;
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->user_id !== null && $this->user_id === $user->id;
    }

    public function isAccessibleTo(?User $user): bool
    {
        if ($this->isPublic()) {
            return true;
        }

        return $user !== null && $this->isOwnedBy($user);
    }

    /**
     * @param  Builder<Game>  $query
     * @return Builder<Game>
     */
    public function scopeAccessibleTo(Builder $query, ?User $user): Builder
    {
        return $query->where(function (Builder $inner) use ($user): void {
            $inner->where('visibility', GameVisibility::Public);

            if ($user !== null) {
                $inner->orWhere('user_id', $user->id);
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsToMany<Question, $this>
     */
    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class)->withTimestamps();
    }
}
