<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\ParentZoneService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureParentUnlocked
{
    public function __construct(private ParentZoneService $zone) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return redirect()->route('user-login');
        }

        if ($this->zone->isUnlocked()) {
            $this->zone->touch();

            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if ($routeName === 'change-pin' && $this->zone->canSetPinWithoutCurrent()) {
            return $next($request);
        }

        return redirect()->route('parent-controls');
    }
}
