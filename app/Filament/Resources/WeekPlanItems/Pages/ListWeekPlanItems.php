<?php

namespace App\Filament\Resources\WeekPlanItems\Pages;

use App\Filament\Resources\WeekPlanItems\WeekPlanItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWeekPlanItems extends ListRecords
{
    protected static string $resource = WeekPlanItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
