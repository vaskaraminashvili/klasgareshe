<?php

namespace App\Data;

final readonly class ChoiceGrade
{
    public function __construct(
        public bool $correct,
        public string $correctKey,
    ) {}
}
