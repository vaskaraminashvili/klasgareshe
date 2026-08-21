<?php

use App\Http\Controllers\ParentVerificationController;
use App\Http\Middleware\RedirectToKidSetup;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::livewire('/login', 'pages::user-login')->name('user-login');
    Route::livewire('/register', 'pages::user-register')->name('user-register');
});

Route::get('/parent-verify/confirm/{user}', [ParentVerificationController::class, 'confirm'])
    ->middleware('signed')
    ->name('parent-verify.confirm');

Route::middleware(['auth:web', RedirectToKidSetup::class])->group(function () {
    Route::livewire('/', 'pages::home')->name('home');
    Route::livewire('/profile', 'pages::profile')->name('profile');
    Route::livewire('/game-multiple-choice', 'pages::game-multiple-choice')->name('game-multiple-choice');
    Route::livewire('/xp-progress', 'pages::xp-progress')->name('xp-progress');
    Route::livewire('/leaderboard', 'pages::leaderboard')->name('leaderboard');
    Route::livewire('/ranking-weekly', 'pages::ranking-weekly')->name('ranking-weekly');
    Route::livewire('/league', 'pages::league')->name('league');
    Route::livewire('/onboarding-age', 'pages::onboarding-age')->name('onboarding-age');
    Route::livewire('/onboarding-categories', 'pages::onboarding-categories')->name('onboarding-categories');
    Route::livewire('/onboarding-goals', 'pages::onboarding-goals')->name('onboarding-goals');
    Route::livewire('/onboarding-notifications', 'pages::onboarding-notifications')->name('onboarding-notifications');
    Route::livewire('/parent-verify', 'pages::parent-verify')->name('parent-verify');
});
