<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\KidSetupService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectToKidSetup
{
    public function __construct(private KidSetupService $setup) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        $current = $request->route()?->getName();

        if (! is_string($current) || in_array($current, $this->setup->allowedRouteNames($user), true)) {
            return $next($request);
        }

        return redirect()->route($this->setup->nextRouteName($user));
    }
}
