<?php

namespace App\Models;

use App\Enums\SchoolSubject;
use App\Enums\XpSource;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property XpSource $source
 * @property SchoolSubject|null $subject
 * @property int $amount
 * @property string|null $context
 * @property Carbon|null $created_at
 */
#[Fillable([
    'user_id',
    'source',
    'subject',
    'amount',
    'context',
    'created_at',
])]
class XpEvent extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => XpSource::class,
            'subject' => SchoolSubject::class,
            'amount' => 'integer',
            'created_at' => 'datetime',
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
