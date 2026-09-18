<?php

namespace App\Enums;

enum ParentPinAttempt
{
    case Unlocked;
    case Created;
    case Invalid;
    case LockedOut;
    case Mismatch;
}
