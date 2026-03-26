<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ActiveSession
{
    public const SESSION_KEY = 'auth.active_session_token';

    public static function issueFor(User $user, Request $request): string
    {
        $token = (string) Str::uuid();

        $user->forceFill([
            'active_session_token' => $token,
        ])->save();

        $request->session()->put(self::SESSION_KEY, $token);

        return $token;
    }

    public static function clearForCurrentRequest(?User $user, Request $request): void
    {
        $sessionToken = (string) $request->session()->get(self::SESSION_KEY, '');

        if ($user && $sessionToken !== '' && hash_equals((string) ($user->active_session_token ?? ''), $sessionToken)) {
            $user->forceFill([
                'active_session_token' => null,
            ])->save();
        }

        $request->session()->forget(self::SESSION_KEY);
    }

    public static function matchesCurrentRequest(User $user, Request $request): bool
    {
        $sessionToken = (string) $request->session()->get(self::SESSION_KEY, '');
        $activeToken = (string) ($user->active_session_token ?? '');

        return $sessionToken !== ''
            && $activeToken !== ''
            && hash_equals($activeToken, $sessionToken);
    }
}
