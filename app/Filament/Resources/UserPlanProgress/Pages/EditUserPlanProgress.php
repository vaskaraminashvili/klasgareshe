<?php

namespace App\Filament\Resources\UserPlanProgress\Pages;

use App\Filament\Resources\UserPlanProgress\UserPlanProgressResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUserPlanProgress extends EditRecord
{
    protected static string $resource = UserPlanProgressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
