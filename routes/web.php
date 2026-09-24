<?php

use App\Http\Controllers\ParentEmailConfirmController;
use App\Http\Controllers\ParentVerificationController;
use App\Http\Middleware\RedirectToKidSetup;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::livewire('/login', 'pages::user-login')->name('user-login');
    Route::livewire('/register', 'pages::user-register')->name('user-register');
});

Route::livewire('/forgot-password', 'pages::forgot-password')->name('forgot-password');
Route::livewire('/otp', 'pages::otp')->name('otp');
Route::livewire('/reset-password', 'pages::reset-password')->name('reset-password');
Route::livewire('/terms', 'pages::terms-privacy')->name('terms-privacy');
Route::livewire('/privacy', 'pages::privacy-policy')->name('privacy-policy');

Route::get('/parent-verify/confirm/{user}', [ParentVerificationController::class, 'confirm'])
    ->middleware('signed')
    ->name('parent-verify.confirm');

Route::get('/parent-email/confirm/{user}', ParentEmailConfirmController::class)
    ->middleware('signed')
    ->name('parent-email.confirm');

Route::livewire('/weekly-report/opt-out/{user}', 'pages::weekly-report-opt-out')
    ->middleware('signed')
    ->name('weekly-report.opt-out');

Route::middleware(['auth:web', RedirectToKidSetup::class, 'parent.zone.lock-on-exit'])->group(function () {
    Route::livewire('/', 'pages::home')->name('home');
    Route::livewire('/learn-categories', 'pages::learn-categories')->name('learn-categories');
    Route::livewire('/profile', 'pages::profile')->name('profile');
    Route::livewire('/edit-profile', 'pages::edit-profile')->name('edit-profile');
    Route::livewire('/settings', 'pages::settings')->name('settings');
    Route::livewire('/daily-mission', 'pages::daily-mission')->name('daily-mission');
    Route::livewire('/streak', 'pages::streak')->name('streak');
    Route::livewire('/xp-progress', 'pages::xp-progress')->name('xp-progress');
    Route::livewire('/rewards-dashboard', 'pages::rewards-dashboard')->name('rewards-dashboard');
    Route::livewire('/leaderboard', 'pages::leaderboard')->name('leaderboard');
    Route::livewire('/ranking-weekly', 'pages::ranking-weekly')->name('ranking-weekly');
    Route::livewire('/ranking-friends', 'pages::ranking-friends')->name('ranking-friends');
    Route::livewire('/league', 'pages::league')->name('league');
    Route::livewire('/badges', 'pages::badges')->name('badges');
    Route::livewire('/badge-unlock/{slug}', 'pages::badge-unlock')->name('badge-unlock');
    Route::livewire('/monthly-goals', 'pages::monthly-goals')->name('monthly-goals');
    Route::livewire('/play-paused', 'pages::play-paused')->name('play-paused');
    Route::livewire('/onboarding-age', 'pages::onboarding-age')->name('onboarding-age');
    Route::livewire('/onboarding-categories', 'pages::onboarding-categories')->name('onboarding-categories');
    Route::livewire('/onboarding-goals', 'pages::onboarding-goals')->name('onboarding-goals');
    Route::livewire('/onboarding-notifications', 'pages::onboarding-notifications')->name('onboarding-notifications');
    Route::livewire('/parent-verify', 'pages::parent-verify')->name('parent-verify');

    Route::middleware('play.time')->group(function () {
        Route::livewire('/game-multiple-choice/{item?}', 'pages::game-multiple-choice')->name('game-multiple-choice');
        Route::livewire('/game-tap-correct/{item?}', 'pages::game-tap-correct')->name('game-tap-correct');
        Route::livewire('/game-counting/{item?}', 'pages::game-counting')->name('game-counting');
    });
});

Route::middleware(['auth:web', RedirectToKidSetup::class])->group(function () {
    Route::livewire('/parent-controls', 'pages::parent-controls')->name('parent-controls');
    Route::livewire('/parent-pin-otp', 'pages::parent-pin-otp')->name('parent-pin-otp');

    Route::middleware('parent.verified')->group(function () {
        Route::livewire('/change-pin', 'pages::change-pin')->name('change-pin');
        Route::livewire('/preferred-subjects', 'pages::preferred-subjects')->name('preferred-subjects');
        Route::livewire('/screen-time', 'pages::screen-time')->name('screen-time');
        Route::livewire('/bedtime-lock', 'pages::bedtime-lock')->name('bedtime-lock');
        Route::livewire('/weekly-report', 'pages::weekly-report')->name('weekly-report');
        Route::livewire('/full-report', 'pages::full-report')->name('full-report');
        Route::livewire('/export-progress', 'pages::export-progress')->name('export-progress');
        Route::livewire('/parent-email', 'pages::parent-email')->name('parent-email');
        Route::livewire('/delete-account', 'pages::delete-account')->name('delete-account');
    });
});
