<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureZagn8nTokenIsValid
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('services.zagn8n.enabled')) {
            abort(404);
        }

        $configuredToken = trim((string) config('services.zagn8n.token'));
        $providedToken = trim((string) ($request->header('X-ZagChain-Automation-Token')
            ?: $request->bearerToken()
            ?: $request->query('token')));

        abort_unless($configuredToken !== '' && hash_equals($configuredToken, $providedToken), 403);

        return $next($request);
    }
}
