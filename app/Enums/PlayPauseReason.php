<?php

namespace App\Enums;

enum PlayPauseReason: string
{
    case Limit = 'limit';
    case Bedtime = 'bedtime';
}
