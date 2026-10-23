<?php

namespace App\Data;

use App\Enums\SocialProvider;

final readonly class PendingSocialSignup
{
    public function __construct(
        public SocialProvider $provider,
        public string $providerId,
        public string $email,
        public bool $emailVerified,
    ) {}
}
