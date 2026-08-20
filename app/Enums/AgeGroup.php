<?php

namespace App\Enums;

enum AgeGroup: string
{
    case Preschool = 'preschool';
    case Kindergarten = 'kindergarten';
    case Elementary = 'elementary';
    case Explorer = 'explorer';

    public function label(): string
    {
        return (string) __('onboarding.age.groups.'.$this->value);
    }

    public function range(): string
    {
        return (string) __('onboarding.age.ranges.'.$this->value);
    }

    public static function fromAge(int $age): self
    {
        return match (true) {
            $age <= 5 => self::Preschool,
            $age <= 7 => self::Kindergarten,
            $age <= 9 => self::Elementary,
            default => self::Explorer,
        };
    }
}
