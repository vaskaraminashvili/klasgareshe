<?php

namespace App\Filament\Resources\UserPlanProgress\Pages;

use App\Filament\Resources\UserPlanProgress\UserPlanProgressResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUserPlanProgress extends ListRecords
{
    protected static string $resource = UserPlanProgressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
