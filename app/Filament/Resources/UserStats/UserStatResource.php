<?php

namespace App\Filament\Resources\UserStats;

use App\Filament\Resources\UserStats\Pages\CreateUserStat;
use App\Filament\Resources\UserStats\Pages\EditUserStat;
use App\Filament\Resources\UserStats\Pages\ListUserStats;
use App\Filament\Resources\UserStats\Schemas\UserStatForm;
use App\Filament\Resources\UserStats\Tables\UserStatsTable;
use App\Models\UserStat;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserStatResource extends Resource
{
    protected static ?string $model = UserStat::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return UserStatForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserStatsTable::configure($table);
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
            'index' => ListUserStats::route('/'),
            'create' => CreateUserStat::route('/create'),
            'edit' => EditUserStat::route('/{record}/edit'),
        ];
    }
}
