<?php

namespace App\Providers;

use App\Repositories\UserRepository;
use App\Repositories\UserStatRepository;
use App\Services\KidSetupService;
use App\Services\ParentVerificationService;
use App\Services\UserRegistrationService;
use App\Services\UserStatService;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(UserRepository::class);
        $this->app->singleton(UserStatRepository::class);
        $this->app->singleton(UserRegistrationService::class);
        $this->app->singleton(UserStatService::class);
        $this->app->singleton(KidSetupService::class);
        $this->app->singleton(ParentVerificationService::class);
    }
}
