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
        return match ($this) {
            self::Preschool => 'Preschool',
            self::Kindergarten => 'Kindergarten',
            self::Elementary => 'Elementary',
            self::Explorer => 'Explorer',
        };
    }

    public function range(): string
    {
        return match ($this) {
            self::Preschool => '4–5',
            self::Kindergarten => '6–7',
            self::Elementary => '8–9',
            self::Explorer => '10+',
        };
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
