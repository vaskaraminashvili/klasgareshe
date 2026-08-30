<?php

namespace App\Enums;

enum SchoolSubject: string
{
    case Georgian = 'georgian';
    case Math = 'math';
    case History = 'history';

    public function label(): string
    {
        return (string) __('subjects.'.$this->value);
    }

    public function emoji(): string
    {
        return match ($this) {
            self::Georgian => '🔤',
            self::Math => '➗',
            self::History => '🏛️',
        };
    }

    public function tile(): string
    {
        return match ($this) {
            self::Georgian => 'tile-sun',
            self::Math => 'tile-violet',
            self::History => 'tile-sky',
        };
    }

    public function inkClass(): string
    {
        return match ($this) {
            self::Georgian => 'text-sun-ink',
            self::Math => 'text-violet-ink',
            self::History => 'text-sky-ink',
        };
    }

    public function progressClass(): string
    {
        return match ($this) {
            self::Georgian => 'progress-sun',
            self::Math => '',
            self::History => 'progress-mint',
        };
    }

    public function favourite(): FavouriteSubject
    {
        return FavouriteSubject::from($this->value);
    }

    /**
     * @return list<self>
     */
    public static function ordered(): array
    {
        return [
            self::Georgian,
            self::Math,
            self::History,
        ];
    }
}
