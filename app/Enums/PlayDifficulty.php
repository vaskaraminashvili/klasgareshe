<?php

namespace App\Enums;

enum PlayDifficulty: string
{
    case Easy = 'easy';
    case Medium = 'medium';
    case Hard = 'hard';

    public function label(): string
    {
        return (string) __('settings.difficulty_'.$this->value);
    }

    /**
     * Scales the pack's base XP (correct answers × xp per correct).
     * Combo, speed, and daily-mission bonuses stay fixed.
     */
    public function xpMultiplierNumerator(): int
    {
        return match ($this) {
            self::Easy => 3,
            self::Medium => 4,
            self::Hard => 6,
        };
    }

    public function xpMultiplierDenominator(): int
    {
        return 4;
    }
}
