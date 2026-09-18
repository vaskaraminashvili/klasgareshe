<?php

namespace App\Http\Middleware;

use App\Services\ParentZoneService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LockParentZoneOnExit
{
    public function __construct(private ParentZoneService $zone) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $name = $request->route()?->getName();

        if (is_string($name)) {
            $this->zone->lockIfOutsideParentZone($name);
        }

        return $next($request);
    }
}
