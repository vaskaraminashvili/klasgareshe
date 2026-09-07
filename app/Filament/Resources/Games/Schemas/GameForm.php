<?php

namespace App\Filament\Resources\Games\Schemas;

use App\Enums\GameType;
use App\Enums\GameVisibility;
use App\Enums\QuestionFormat;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class GameForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->numeric(),
                TextInput::make('title'),
                Select::make('slug')
                    ->options(GameType::class)
                    ->required(),
                Select::make('format')
                    ->options(QuestionFormat::class)
                    ->required(),
                TextInput::make('lives')
                    ->required()
                    ->numeric()
                    ->default(3),
                TextInput::make('questions_per_round')
                    ->required()
                    ->numeric()
                    ->default(10),
                TextInput::make('xp_per_correct')
                    ->required()
                    ->numeric()
                    ->default(8),
                Toggle::make('is_active')
                    ->required(),
                Select::make('visibility')
                    ->options(GameVisibility::class)
                    ->default('public')
                    ->required(),
            ]);
    }
}
