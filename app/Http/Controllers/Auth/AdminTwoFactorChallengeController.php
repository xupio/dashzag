<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AdminTwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminTwoFactorChallengeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $pendingUser = $this->pendingUser($request);

        if (! $pendingUser) {
            return Redirect::route('login');
        }

        return view('auth.admin-two-factor-challenge', [
            'pendingUser' => $pendingUser,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $pendingUser = $this->pendingUser($request);

        if (! $pendingUser) {
            return Redirect::route('login');
        }

        if (! AdminTwoFactor::verifyCodeForUser($pendingUser, (string) $request->input('code'))) {
            throw ValidationException::withMessages([
                'code' => 'The authenticator code is invalid.',
            ]);
        }

        $remember = (bool) $request->session()->pull('auth.admin_2fa.remember', false);

        $request->session()->forget('auth.admin_2fa.pending_user_id');

        Auth::login($pendingUser, $remember);

        $request->session()->regenerate();
        $request->session()->put('auth.admin_two_factor_passed_for', $pendingUser->getKey());

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function pendingUser(Request $request): ?User
    {
        $pendingUserId = $request->session()->get('auth.admin_2fa.pending_user_id');

        if (! $pendingUserId) {
            return null;
        }

        $user = User::query()->find($pendingUserId);

        if (! $user || ! $user->hasAdminTwoFactorEnabled()) {
            $request->session()->forget([
                'auth.admin_2fa.pending_user_id',
                'auth.admin_2fa.remember',
            ]);

            return null;
        }

        return $user;
    }
}
