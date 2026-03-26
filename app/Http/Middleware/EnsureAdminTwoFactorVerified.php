<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminTwoFactorVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('logout')) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user || ! $user->hasAdminTwoFactorEnabled()) {
            return $next($request);
        }

        if ((int) $request->session()->get('auth.admin_two_factor_passed_for') === (int) $user->getKey()) {
            return $next($request);
        }

        Auth::guard('web')->logout();

        $request->session()->put('auth.admin_2fa.pending_user_id', $user->getKey());

        return redirect()->route('admin.two-factor.challenge');
    }
}
