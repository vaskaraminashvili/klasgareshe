<?php

namespace App\Http\Controllers;

use App\Repositories\UserRepository;
use App\Services\AccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParentEmailConfirmController extends Controller
{
    public function __invoke(
        Request $request,
        int $user,
        UserRepository $users,
        AccountService $accounts,
    ): RedirectResponse {
        $token = $request->query('token');

        if (! is_string($token) || $token === '') {
            abort(403);
        }

        $account = $users->findOrFail($user);

        if (! $accounts->confirmPendingEmail($account, $token)) {
            abort(403);
        }

        if (Auth::id() === $account->id) {
            return redirect()->route('parent-controls');
        }

        return redirect()->route('user-login');
    }
}
