<?php

namespace App\Enums;

enum ReminderTime: string
{
    case Morning = 'morning';
    case Afternoon = 'afternoon';
    case Evening = 'evening';
    case Bedtime = 'bedtime';
}
