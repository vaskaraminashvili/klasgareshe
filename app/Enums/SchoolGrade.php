<?php

namespace App\Enums;

enum SchoolGrade: int
{
    case First = 1;
    case Second = 2;
    case Third = 3;

    public function label(): string
    {
        return (string) __('onboarding.age.grades.'.$this->value);
    }

    public function range(): string
    {
        return (string) __('onboarding.age.grade_ranges.'.$this->value);
    }

    public static function fromAge(int $age): self
    {
        return match (true) {
            $age <= 6 => self::First,
            $age === 7 => self::Second,
            default => self::Third,
        };
    }
}
