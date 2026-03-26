<?php

namespace App\Http\Middleware;

use App\Support\ActiveSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSingleDeviceSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $request->routeIs('logout')) {
            return $next($request);
        }

        if (blank($user->active_session_token)) {
            ActiveSession::issueFor($user, $request);

            return $next($request);
        }

        if (! $request->session()->has(ActiveSession::SESSION_KEY)) {
            $request->session()->put(ActiveSession::SESSION_KEY, $user->active_session_token);

            return $next($request);
        }

        if (ActiveSession::matchesCurrentRequest($user, $request)) {
            return $next($request);
        }

        Auth::guard('web')->logout();
        $request->session()->forget([
            'auth.admin_2fa.pending_user_id',
            'auth.admin_2fa.remember',
            'auth.admin_two_factor_passed_for',
        ]);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors([
            'email' => 'Your account was signed in on another device. Please log in again.',
        ]);
    }
}
