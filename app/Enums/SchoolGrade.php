<?php

namespace App\Enums;

enum SchoolGrade: int
{
    case Fourth = 4;

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
        return self::Fourth;
    }
}
