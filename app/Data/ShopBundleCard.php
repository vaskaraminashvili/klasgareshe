<?php

namespace App\Data;

final readonly class ShopBundleCard
{
    public function __construct(
        public string $slug,
        public string $emoji,
        public string $name,
        public string $kicker,
        public string $body,
        public int $price,
        public bool $owned,
    ) {}
}
