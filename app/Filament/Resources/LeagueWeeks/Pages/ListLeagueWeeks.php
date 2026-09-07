<?php

namespace App\Filament\Resources\LeagueWeeks\Pages;

use App\Filament\Resources\LeagueWeeks\LeagueWeekResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLeagueWeeks extends ListRecords
{
    protected static string $resource = LeagueWeekResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
