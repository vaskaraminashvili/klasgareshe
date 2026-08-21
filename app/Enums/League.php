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

    public function rank(): int
    {
        return match ($this) {
            self::Bronze => 1,
            self::Silver => 2,
            self::Gold => 3,
            self::Emerald => 4,
            self::Sapphire => 5,
            self::Diamond => 6,
        };
    }

    public function promote(): self
    {
        return match ($this) {
            self::Bronze => self::Silver,
            self::Silver => self::Gold,
            self::Gold => self::Emerald,
            self::Emerald => self::Sapphire,
            self::Sapphire, self::Diamond => self::Diamond,
        };
    }

    public function relegate(): self
    {
        return match ($this) {
            self::Diamond => self::Sapphire,
            self::Sapphire => self::Emerald,
            self::Emerald => self::Gold,
            self::Gold => self::Silver,
            self::Silver, self::Bronze => self::Bronze,
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::Bronze => '🥉',
            self::Silver => '🥈',
            self::Gold => '🥇',
            self::Emerald => '💚',
            self::Sapphire => '💙',
            self::Diamond => '💎',
        };
    }

    /**
     * @return list<self>
     */
    public static function ordered(): array
    {
        return [
            self::Bronze,
            self::Silver,
            self::Gold,
            self::Emerald,
            self::Sapphire,
            self::Diamond,
        ];
    }
}
