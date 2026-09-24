<?php

namespace App\Data;

final readonly class RewardClaimResult
{
    public function __construct(
        public bool $paid,
        public int $xp = 0,
        public ?string $redirectSlug = null,
    ) {}

    public static function ignored(): self
    {
        return new self(paid: false);
    }
}
