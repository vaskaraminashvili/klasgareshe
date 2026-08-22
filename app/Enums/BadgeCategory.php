<?php

namespace App\Enums;

enum BadgeCategory: string
{
    case Milestone = 'milestone';
    case Math = 'math';
    case Alphabet = 'alphabet';
    case Animals = 'animals';
    case Streak = 'streak';
    case League = 'league';

    public function label(): string
    {
        return (string) __('badges.categories.'.$this->value);
    }
}
