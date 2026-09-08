<?php

namespace App\Filament\Resources\LeagueWeeks;

use App\Filament\Resources\LeagueWeeks\Pages\CreateLeagueWeek;
use App\Filament\Resources\LeagueWeeks\Pages\EditLeagueWeek;
use App\Filament\Resources\LeagueWeeks\Pages\ListLeagueWeeks;
use App\Filament\Resources\LeagueWeeks\Schemas\LeagueWeekForm;
use App\Filament\Resources\LeagueWeeks\Tables\LeagueWeeksTable;
use App\Models\LeagueWeek;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LeagueWeekResource extends Resource
{
    protected static ?string $model = LeagueWeek::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return LeagueWeekForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeagueWeeksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeagueWeeks::route('/'),
            'create' => CreateLeagueWeek::route('/create'),
            'edit' => EditLeagueWeek::route('/{record}/edit'),
        ];
    }
}
