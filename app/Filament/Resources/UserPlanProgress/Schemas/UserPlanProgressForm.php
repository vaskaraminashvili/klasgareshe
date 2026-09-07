<?php

namespace App\Filament\Resources\UserPlanProgress\Schemas;

use App\Enums\PlanProgressStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserPlanProgressForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('week_plan_item_id')
                    ->required()
                    ->numeric(),
                Select::make('status')
                    ->options(PlanProgressStatus::class)
                    ->default('completed')
                    ->required(),
                TextInput::make('correct_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('completed_at'),
            ]);
    }
}
