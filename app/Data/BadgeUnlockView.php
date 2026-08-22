<?php

namespace App\Data;

final readonly class BadgeUnlockView
{
    public function __construct(
        public string $slug,
        public string $name,
        public string $blurb,
        public string $emoji,
        public string $medalClass,
        public string $rarityLabel,
        public int $xpBonus,
        public int $holderPercent,
    ) {}
}
