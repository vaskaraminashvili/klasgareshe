<?php

namespace App\Filament\Resources\LeagueWeeks\Schemas;

use App\Enums\LeagueWeekStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class LeagueWeekForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('starts_on')
                    ->required(),
                DatePicker::make('ends_on')
                    ->required(),
                Select::make('status')
                    ->options(LeagueWeekStatus::class)
                    ->default('open')
                    ->required(),
            ]);
    }
}
