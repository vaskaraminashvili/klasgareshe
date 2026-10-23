<?php

namespace App\Enums;

enum ShopCategory: string
{
    case Avatar = 'avatar';
    case Hat = 'hat';
    case Background = 'background';
    case Boost = 'boost';
    case Sound = 'sound';
    case Shield = 'shield';
    case Bundle = 'bundle';

    public function stacks(): bool
    {
        return $this === self::Shield || $this === self::Boost;
    }

    public function equippable(): bool
    {
        return match ($this) {
            self::Avatar, self::Hat, self::Background, self::Sound => true,
            default => false,
        };
    }
}
