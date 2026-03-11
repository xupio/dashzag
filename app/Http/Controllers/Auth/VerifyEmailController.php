<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\FriendInvitation;
use App\Support\MiningPlatform;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            FriendInvitation::where('email', $request->user()->email)
                ->update(['registered_at' => now()]);

            MiningPlatform::awardReferralRegistration($request->user());

            event(new Verified($request->user()));
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
