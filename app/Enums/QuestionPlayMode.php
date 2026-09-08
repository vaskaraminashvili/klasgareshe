<?php

namespace App\Enums;

enum QuestionPlayMode: string
{
    case Choice = 'choice';
    case TapCorrect = 'tap';
    case FillLetter = 'fill';
    case Count = 'count';
}
