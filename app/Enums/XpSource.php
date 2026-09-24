<?php

namespace App\Enums;

enum XpSource: string
{
    case Pack = 'pack';
    case DailyMission = 'daily_mission';
    case StreakMilestone = 'streak_milestone';
    case DailyLogin = 'daily_login';
    case Combo = 'combo';
    case Speed = 'speed';
    case Badge = 'badge';

    public function label(): string
    {
        return (string) __('xp.sources.'.$this->value);
    }

    public function emoji(): string
    {
        return match ($this) {
            self::Pack => '📖',
            self::DailyMission => '🎯',
            self::StreakMilestone => '🔥',
            self::DailyLogin => '📅',
            self::Combo => '💥',
            self::Speed => '⚡',
            self::Badge => '🏅',
        };
    }

    public function tile(): string
    {
        return match ($this) {
            self::Pack => 'tile-violet',
            self::DailyMission => 'tile-sun',
            self::StreakMilestone => 'tile-coral',
            self::DailyLogin => 'tile-mint',
            self::Combo => 'tile-sky',
            self::Speed => 'tile-sun',
            self::Badge => 'tile-mint',
        };
    }
}
