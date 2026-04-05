<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\ActivityFeedNotification;
use App\Support\MiningPlatform;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'account_type' => 'starter',
        ]);

        $starterInvestment = MiningPlatform::ensureStarterPackage($user);
        $template = MiningPlatform::activityTemplate('free_starter');

        $user->notify(new ActivityFeedNotification([
            'category' => 'milestone',
            'status' => 'success',
            'subject' => $template['subject'],
            'message' => $template['message'],
            'context_label' => 'Package',
            'context_value' => 'Starter Free',
            'investment_id' => $starterInvestment->id,
        ]));

        MiningPlatform::notifyAdminsOfNewRegistration($user);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('verification.notice', absolute: false));
    }
}
