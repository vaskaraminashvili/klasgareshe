<?php

namespace App\Filament\Resources\UserPlanProgress;

use App\Filament\Resources\UserPlanProgress\Pages\CreateUserPlanProgress;
use App\Filament\Resources\UserPlanProgress\Pages\EditUserPlanProgress;
use App\Filament\Resources\UserPlanProgress\Pages\ListUserPlanProgress;
use App\Filament\Resources\UserPlanProgress\Schemas\UserPlanProgressForm;
use App\Filament\Resources\UserPlanProgress\Tables\UserPlanProgressTable;
use App\Models\UserPlanProgress;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserPlanProgressResource extends Resource
{
    protected static ?string $model = UserPlanProgress::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return UserPlanProgressForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserPlanProgressTable::configure($table);
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
            'index' => ListUserPlanProgress::route('/'),
            'create' => CreateUserPlanProgress::route('/create'),
            'edit' => EditUserPlanProgress::route('/{record}/edit'),
        ];
    }
}
