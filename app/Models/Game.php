<?php

namespace App\Models;

use App\Enums\GameType;
use App\Enums\QuestionFormat;
use Database\Factories\GameFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property GameType $slug
 * @property QuestionFormat $format
 * @property int $lives
 * @property int $questions_per_round
 * @property int $xp_per_correct
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'slug',
    'format',
    'lives',
    'questions_per_round',
    'xp_per_correct',
    'is_active',
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
        ];
    }
}
