<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/login', 'pages::user-login')->name('user-login');
Route::livewire('/register', 'pages::user-register')->name('user-register');

Route::middleware('auth:web')->group(function () {
    Route::livewire('/', 'pages::home')->name('home');
    Route::livewire('/profile', 'pages::profile')->name('profile');
});
