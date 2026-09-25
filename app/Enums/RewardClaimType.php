<?php

namespace App\Enums;

enum RewardClaimType: string
{
    case DailyBox = 'daily_box';
    case DailyLogin = 'daily_login';
    case Badge = 'badge';
    case Freeze = 'freeze';
    case WeeklyPrize = 'weekly_prize';
}
