<?php

namespace App\Http\Controllers;

use App\Support\AdminTwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class AdminTwoFactorController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        abort_unless($user && $user->isAdmin(), 403);

        $user->forceFill([
            'admin_two_factor_secret' => AdminTwoFactor::encryptSecret(AdminTwoFactor::generateSecret()),
            'admin_two_factor_confirmed_at' => null,
        ])->save();

        return Redirect::route('profile.edit')->with('status', 'admin-two-factor-setup-created');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();

        abort_unless($user && $user->isAdmin(), 403);

        if (! $user->hasPendingAdminTwoFactorSetup()) {
            return Redirect::route('profile.edit')->withErrors([
                'code' => 'Generate a new authenticator setup before confirming.',
            ], 'adminTwoFactor');
        }

        if (! AdminTwoFactor::verifyCodeForUser($user, $validated['code'])) {
            return Redirect::route('profile.edit')->withErrors([
                'code' => 'The authenticator code is invalid.',
            ], 'adminTwoFactor');
        }

        $user->forceFill([
            'admin_two_factor_confirmed_at' => now(),
        ])->save();

        return Redirect::route('profile.edit')->with('status', 'admin-two-factor-enabled');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        abort_unless($user && $user->isAdmin(), 403);

        $user->forceFill([
            'admin_two_factor_secret' => null,
            'admin_two_factor_confirmed_at' => null,
        ])->save();

        $request->session()->forget('auth.admin_two_factor_passed_for');

        return Redirect::route('profile.edit')->with('status', 'admin-two-factor-disabled');
    }
}
