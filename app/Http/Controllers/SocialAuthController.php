<?php

namespace App\Http\Controllers;

use App\Exceptions\AuthProviderException;
use App\Models\User;
use App\Services\KidSetupService;
use App\Services\SocialAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class SocialAuthController extends Controller
{
    public function redirect(string $provider, SocialAuthService $social): RedirectResponse
    {
        try {
            return $social->redirectToProvider($provider);
        } catch (AuthProviderException $exception) {
            return redirect()->route('user-login')->with('auth_error', $exception->getMessage());
        }
    }

    public function callback(string $provider, SocialAuthService $social, KidSetupService $setup): RedirectResponse
    {
        try {
            $user = $social->authenticate($provider);
        } catch (AuthProviderException $exception) {
            return redirect()->route('user-login')->with('auth_error', $exception->getMessage());
        }

        if (! $user instanceof User) {
            return redirect()->route('user-register');
        }

        Auth::login($user);
        request()->session()->regenerate();

        return redirect()->route($setup->nextRouteName($user));
    }
}
