<?php

namespace App\Filament\Resources\UserBadges\Pages;

use App\Filament\Resources\UserBadges\UserBadgeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUserBadge extends CreateRecord
{
    protected static string $resource = UserBadgeResource::class;
}
