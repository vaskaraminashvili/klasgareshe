<?php

namespace App\Data;

final readonly class LevelProgress
{
    public function __construct(
        public int $level,
        public string $title,
        public string $titleKey,
        public int $xp,
        public int $xpIntoLevel,
        public int $xpToNext,
        public int $percent,
        public int $nextLevel,
        public string $nextTitle,
    ) {}
}
