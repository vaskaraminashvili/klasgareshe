<?php

namespace App\Data;

final readonly class ShopPurchaseResult
{
    private function __construct(
        public string $status,
        public int $spent,
    ) {}

    public static function purchased(int $spent): self
    {
        return new self('purchased', $spent);
    }

    public static function owned(): self
    {
        return new self('owned', 0);
    }

    public static function insufficient(): self
    {
        return new self('insufficient', 0);
    }

    public static function capped(): self
    {
        return new self('capped', 0);
    }

    public static function missing(): self
    {
        return new self('missing', 0);
    }
}
