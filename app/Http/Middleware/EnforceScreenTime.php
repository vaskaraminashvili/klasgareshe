<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\ScreenTimeService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceScreenTime
{
    public function __construct(private ScreenTimeService $time) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        $fresh = app(UserRepository::class)->findOrFail($user->id);

        if ($this->time->blockReason($fresh) !== null) {
            return redirect()->route('play-paused');
        }

        return $next($request);
    }
}
