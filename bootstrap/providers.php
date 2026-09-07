<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\DirectorPanelProvider;
use App\Providers\RepositoryServiceProvider;

return [
    AppServiceProvider::class,
    DirectorPanelProvider::class,
    RepositoryServiceProvider::class,
];
