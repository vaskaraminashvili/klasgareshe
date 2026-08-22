<?php

namespace App\Enums;

enum PlanProgressStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
}
