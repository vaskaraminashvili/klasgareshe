<?php

namespace App\Filament\Resources\WeekPlanItems\Schemas;

use App\Enums\GameType;
use App\Enums\SchoolGrade;
use App\Enums\SchoolSubject;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WeekPlanItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('grade')
                    ->options(SchoolGrade::class)
                    ->required(),
                TextInput::make('week_number')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('weekday')
                    ->required()
                    ->numeric(),
                Select::make('subject')
                    ->options(SchoolSubject::class)
                    ->required(),
                TextInput::make('level')
                    ->required()
                    ->numeric(),
                TextInput::make('title')
                    ->required(),
                Select::make('game_slug')
                    ->options(GameType::class)
                    ->required(),
                TextInput::make('questions_per_round')
                    ->required()
                    ->numeric()
                    ->default(5),
            ]);
    }
}
