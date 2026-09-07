<?php

namespace App\Filament\Resources\LeagueWeeks\Pages;

use App\Filament\Resources\LeagueWeeks\LeagueWeekResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLeagueWeek extends EditRecord
{
    protected static string $resource = LeagueWeekResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
