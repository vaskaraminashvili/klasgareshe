<?php

namespace App\Data;

final readonly class EquippedLook
{
    public function __construct(
        public string $hat,
        public string $background,
        public string $boostUntil,
    ) {}
}
