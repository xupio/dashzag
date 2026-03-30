<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // Keep the miner overview readable for signed-in users while
        // leaving all write actions under the admin guard.
        if ($request->routeIs('dashboard.miner')) {
            return $next($request);
        }

        abort_unless($request->user()?->isAdmin(), 403);

        return $next($request);
    }
}
