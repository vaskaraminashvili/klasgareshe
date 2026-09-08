<?php

namespace App\Filament\Resources\Friendships\Schemas;

use App\Enums\FriendshipStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class FriendshipForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('friend_id')
                    ->relationship('friend', 'name')
                    ->required(),
                Select::make('status')
                    ->options(FriendshipStatus::class)
                    ->default('pending')
                    ->required(),
                DateTimePicker::make('accepted_at'),
            ]);
    }
}
