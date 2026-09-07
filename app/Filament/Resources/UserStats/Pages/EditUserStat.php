<?php

namespace App\Filament\Resources\UserStats\Pages;

use App\Filament\Resources\UserStats\UserStatResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUserStat extends EditRecord
{
    protected static string $resource = UserStatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
