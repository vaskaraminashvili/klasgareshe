<?php

namespace App\Models;

use Database\Factories\UserPlaySessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property Carbon $started_at
 * @property Carbon $last_heartbeat_at
 * @property Carbon|null $ended_at
 * @property int $seconds
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'started_at',
    'last_heartbeat_at',
    'ended_at',
    'seconds',
])]
class UserPlaySession extends Model
{
    /** @use HasFactory<UserPlaySessionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'last_heartbeat_at' => 'datetime',
            'ended_at' => 'datetime',
            'seconds' => 'integer',
        ];
    }
}
