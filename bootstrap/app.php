<?php

use App\Http\Middleware\RedirectToKidSetup;
use App\Models\User;
use App\Services\KidSetupService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'kid.setup' => RedirectToKidSetup::class,
        ]);

        $middleware->redirectGuestsTo(fn (Request $request) => route('user-login'));

        $middleware->redirectUsersTo(function (Request $request): string {
            $user = $request->user();

            if (! $user instanceof User) {
                return route('home');
            }

            return route(app(KidSetupService::class)->nextRouteName($user));
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
