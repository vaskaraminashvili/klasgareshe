<?php

namespace App\Filament\Resources\UserStats\Schemas;

use App\Enums\League;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserStatForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('xp')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('current_streak')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('longest_streak')
                    ->required()
                    ->numeric()
                    ->default(0),
                DatePicker::make('last_played_on'),
                Select::make('league')
                    ->options(League::class)
                    ->default('bronze')
                    ->required(),
            ]);
    }
}
