<?php

namespace App\Filament\Resources\UserStats\Pages;

use App\Filament\Resources\UserStats\UserStatResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUserStats extends ListRecords
{
    protected static string $resource = UserStatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
