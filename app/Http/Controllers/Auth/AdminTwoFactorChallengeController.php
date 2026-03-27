<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ActiveSession;
use App\Support\AdminTwoFactor;
use App\Support\MiningPlatform;
use App\Support\UserActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\RateLimiter;
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

        $throttleKey = $this->throttleKey($request, $pendingUser);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            MiningPlatform::notifyAdminsOfCriticalAlert(
                'Admin 2FA challenge locked',
                'An admin 2FA challenge has been rate limited after repeated invalid codes.',
                'Throttle window: '.$seconds.' seconds',
                'Check whether the admin device is out of sync or an unauthorized user is attempting access.',
                'Admin email',
                strtolower($pendingUser->email),
                [
                    'admin_user_id' => $pendingUser->id,
                    'email' => strtolower($pendingUser->email),
                    'ip' => $request->ip(),
                    'seconds' => $seconds,
                ],
            );

            throw ValidationException::withMessages([
                'code' => 'Too many invalid codes. Try again in '.$seconds.' seconds.',
            ]);
        }

        if (! AdminTwoFactor::verifyCodeForUser($pendingUser, (string) $request->input('code'))) {
            RateLimiter::hit($throttleKey);

            $attempts = RateLimiter::attempts($throttleKey);

            if ($attempts >= 3) {
                MiningPlatform::notifyAdminsOfCriticalAlert(
                    'Repeated failed admin 2FA attempts',
                    'An admin account has failed the 2FA challenge multiple times.',
                    'Attempts: '.$attempts,
                    'Verify the admin device and review any unusual login activity.',
                    'Admin email',
                    strtolower($pendingUser->email),
                    [
                        'admin_user_id' => $pendingUser->id,
                        'email' => strtolower($pendingUser->email),
                        'ip' => $request->ip(),
                        'attempts' => $attempts,
                    ],
                );
            }

            throw ValidationException::withMessages([
                'code' => 'The authenticator code is invalid.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        $remember = (bool) $request->session()->pull('auth.admin_2fa.remember', false);

        $request->session()->forget('auth.admin_2fa.pending_user_id');

        Auth::login($pendingUser, $remember);

        $request->session()->regenerate();
        ActiveSession::issueFor($pendingUser, $request);
        UserActivity::recordLogin($pendingUser, $request);
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

    private function throttleKey(Request $request, User $user): string
    {
        return 'admin-2fa|'.$user->getKey().'|'.$request->ip();
    }
}
