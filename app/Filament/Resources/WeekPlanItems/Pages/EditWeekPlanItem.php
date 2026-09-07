<?php

namespace App\Filament\Resources\WeekPlanItems\Pages;

use App\Filament\Resources\WeekPlanItems\WeekPlanItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWeekPlanItem extends EditRecord
{
    protected static string $resource = WeekPlanItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
