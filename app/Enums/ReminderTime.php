<?php

namespace App\Enums;

enum ReminderTime: string
{
    case Morning = 'morning';
    case Afternoon = 'afternoon';
    case Evening = 'evening';
    case Bedtime = 'bedtime';

    public function label(): string
    {
        return (string) __('onboarding.notifications.'.$this->value);
    }

    public function clockLabel(): string
    {
        return match ($this) {
            self::Morning => '8:00',
            self::Afternoon => '16:00',
            self::Evening => '18:00',
            self::Bedtime => '19:30',
        };
    }
}
