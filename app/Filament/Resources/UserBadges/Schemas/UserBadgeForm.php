<?php

namespace App\Filament\Resources\UserBadges\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class UserBadgeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('badge_id')
                    ->relationship('badge', 'id')
                    ->required(),
                DateTimePicker::make('unlocked_at'),
                DateTimePicker::make('seen_at'),
            ]);
    }
}
