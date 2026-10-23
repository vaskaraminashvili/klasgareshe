<?php

namespace App\Repositories;

use App\Models\SupportMessage;

class SupportMessageRepository
{
    /**
     * @param  array{user_id: int|null, name: string, email: string, topic: string, body: string}  $attributes
     */
    public function create(array $attributes): SupportMessage
    {
        return SupportMessage::query()->create($attributes);
    }
}
