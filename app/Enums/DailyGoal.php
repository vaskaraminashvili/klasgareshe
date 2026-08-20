<?php

namespace App\Enums;

enum DailyGoal: string
{
    case Casual = 'casual';
    case Regular = 'regular';
    case Serious = 'serious';
    case Intense = 'intense';

    public function label(): string
    {
        return match ($this) {
            self::Casual => 'Casual',
            self::Regular => 'Regular',
            self::Serious => 'Serious',
            self::Intense => 'Intense',
        };
    }

    public function minutes(): int
    {
        return match ($this) {
            self::Casual => 5,
            self::Regular => 10,
            self::Serious => 15,
            self::Intense => 20,
        };
    }

    public function timeLabel(): string
    {
        return $this->minutes().' min';
    }
}
