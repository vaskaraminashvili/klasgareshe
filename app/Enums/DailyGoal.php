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
        return (string) __('onboarding.goals.'.$this->value);
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
        return (string) __('onboarding.goals.minutes_short', ['minutes' => $this->minutes()]);
    }
}
