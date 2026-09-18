<?php

namespace App\Enums;

enum ReportScope: string
{
    case Week = 'week';
    case Month = 'month';
    case Season = 'season';
    case All = 'all';
}
