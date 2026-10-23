<?php

namespace App\Data;

use App\Models\User;

final readonly class PhoneVerifyResult
{
    private function __construct(
        public bool $valid,
        public ?User $user,
        public bool $needsRegistration,
    ) {}

    public static function invalid(): self
    {
        return new self(false, null, false);
    }

    public static function authenticated(User $user): self
    {
        return new self(true, $user, false);
    }

    public static function needsRegistration(): self
    {
        return new self(true, null, true);
    }
}
