<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\ActiveSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        if ($user && $user->hasAdminTwoFactorEnabled()) {
            $request->session()->put([
                'auth.admin_2fa.pending_user_id' => $user->getKey(),
                'auth.admin_2fa.remember' => $request->boolean('remember'),
            ]);

            Auth::guard('web')->logout();

            return redirect()->route('admin.two-factor.challenge');
        }

        if ($user) {
            ActiveSession::issueFor($user, $request);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        ActiveSession::clearForCurrentRequest($request->user(), $request);

        Auth::guard('web')->logout();

        $request->session()->forget([
            'auth.admin_2fa.pending_user_id',
            'auth.admin_2fa.remember',
            'auth.admin_two_factor_passed_for',
        ]);

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
