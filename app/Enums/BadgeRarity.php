<?php

namespace App\Enums;

enum BadgeRarity: string
{
    case Common = 'common';
    case Rare = 'rare';
    case Epic = 'epic';
    case Legend = 'legend';

    public function label(): string
    {
        return (string) __('badges.rarity.'.$this->value);
    }

    public function cssClass(): string
    {
        return 'rarity rarity-'.$this->value;
    }
}
