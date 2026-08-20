<?php

namespace App\Http\Controllers;

use App\Repositories\UserRepository;
use App\Services\KidSetupService;
use App\Services\ParentVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParentVerificationController extends Controller
{
    public function confirm(
        Request $request,
        int $user,
        UserRepository $users,
        ParentVerificationService $verification,
        KidSetupService $setup,
    ): RedirectResponse {
        $account = $users->findOrFail($user);
        $account = $verification->markVerified($account);

        Auth::login($account);
        $request->session()->regenerate();

        return redirect()->route($setup->nextRouteName($account));
    }
}
