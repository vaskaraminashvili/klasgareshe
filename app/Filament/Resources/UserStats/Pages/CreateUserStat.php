<?php

namespace App\Filament\Resources\UserStats\Pages;

use App\Filament\Resources\UserStats\UserStatResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUserStat extends CreateRecord
{
    protected static string $resource = UserStatResource::class;
}
