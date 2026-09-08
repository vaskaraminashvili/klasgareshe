<?php

namespace App\Filament\Resources\Friendships\Pages;

use App\Filament\Resources\Friendships\FriendshipResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFriendship extends EditRecord
{
    protected static string $resource = FriendshipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
