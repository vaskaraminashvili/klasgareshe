<?php

namespace App\Filament\Resources\WeekPlanItems;

use App\Filament\Resources\WeekPlanItems\Pages\CreateWeekPlanItem;
use App\Filament\Resources\WeekPlanItems\Pages\EditWeekPlanItem;
use App\Filament\Resources\WeekPlanItems\Pages\ListWeekPlanItems;
use App\Filament\Resources\WeekPlanItems\Schemas\WeekPlanItemForm;
use App\Filament\Resources\WeekPlanItems\Tables\WeekPlanItemsTable;
use App\Models\WeekPlanItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WeekPlanItemResource extends Resource
{
    protected static ?string $model = WeekPlanItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return WeekPlanItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WeekPlanItemsTable::configure($table);
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
            'index' => ListWeekPlanItems::route('/'),
            'create' => CreateWeekPlanItem::route('/create'),
            'edit' => EditWeekPlanItem::route('/{record}/edit'),
        ];
    }
}
