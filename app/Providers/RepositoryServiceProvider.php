<?php

namespace App\Providers;

use App\Repositories\UserRepository;
use App\Services\UserRegistrationService;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(UserRepository::class);
        $this->app->singleton(UserRegistrationService::class);
    }
}
