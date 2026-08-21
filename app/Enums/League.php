<?php

namespace App\Enums;

enum League: string
{
    case Bronze = 'bronze';
    case Silver = 'silver';
    case Gold = 'gold';
    case Emerald = 'emerald';
    case Sapphire = 'sapphire';
    case Diamond = 'diamond';

    public function label(): string
    {
        return (string) __('leagues.'.$this->value);
    }
}
