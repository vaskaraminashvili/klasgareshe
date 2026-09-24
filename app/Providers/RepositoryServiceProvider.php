<?php

namespace App\Providers;

use App\Repositories\BadgeRepository;
use App\Repositories\FriendshipRepository;
use App\Repositories\GameRepository;
use App\Repositories\LeagueRepository;
use App\Repositories\PlaySessionRepository;
use App\Repositories\QuestionRepository;
use App\Repositories\RewardRepository;
use App\Repositories\UserRepository;
use App\Repositories\UserStatRepository;
use App\Repositories\WeekPlanRepository;
use App\Services\AccountService;
use App\Services\BadgeService;
use App\Services\FriendshipService;
use App\Services\GamePlayService;
use App\Services\KidSetupService;
use App\Services\LeagueSeasonService;
use App\Services\LevelCalculator;
use App\Services\MonthlyGoalService;
use App\Services\ParentVerificationService;
use App\Services\ParentZoneService;
use App\Services\PasswordResetService;
use App\Services\ProgressPdfService;
use App\Services\ProgressReportService;
use App\Services\QuestionPlayModeResolver;
use App\Services\RewardService;
use App\Services\ScreenTimeService;
use App\Services\SearchService;
use App\Services\UserProfileService;
use App\Services\UserRegistrationService;
use App\Services\UserStatService;
use App\Services\VerificationCodeService;
use App\Services\WeekPlanService;
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
        $this->app->singleton(FriendshipRepository::class);
        $this->app->singleton(LeagueRepository::class);
        $this->app->singleton(GameRepository::class);
        $this->app->singleton(QuestionRepository::class);
        $this->app->singleton(LevelCalculator::class);
        $this->app->singleton(UserRegistrationService::class);
        $this->app->singleton(UserProfileService::class);
        $this->app->singleton(FriendshipService::class);
        $this->app->singleton(LeagueSeasonService::class);
        $this->app->singleton(UserStatService::class);
        $this->app->singleton(QuestionPlayModeResolver::class);
        $this->app->singleton(GamePlayService::class);
        $this->app->singleton(KidSetupService::class);
        $this->app->singleton(ParentVerificationService::class);
        $this->app->singleton(VerificationCodeService::class);
        $this->app->singleton(PasswordResetService::class);
        $this->app->singleton(WeekPlanRepository::class);
        $this->app->singleton(WeekPlanService::class);
        $this->app->singleton(BadgeRepository::class);
        $this->app->singleton(BadgeService::class);
        $this->app->singleton(RewardRepository::class);
        $this->app->singleton(RewardService::class);
        $this->app->singleton(MonthlyGoalService::class);
        $this->app->singleton(SearchService::class);
        $this->app->singleton(ParentZoneService::class);
        $this->app->singleton(PlaySessionRepository::class);
        $this->app->singleton(ScreenTimeService::class);
        $this->app->singleton(ProgressReportService::class);
        $this->app->singleton(ProgressPdfService::class);
        $this->app->singleton(AccountService::class);
    }
}
