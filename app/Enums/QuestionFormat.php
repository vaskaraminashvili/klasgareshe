<?php

namespace App\Enums;

enum QuestionFormat: string
{
    case Choice = 'choice';
    case Count = 'count';
    case Spell = 'spell';
    case Pairs = 'pairs';
    case Grid = 'grid';
    case Trace = 'trace';
    case Hotspot = 'hotspot';
}
