<?php

namespace App\Support;

use App\Models\User;
use App\Models\UserLoginEvent;
use App\Models\UserPageActivityLog;
use Illuminate\Http\Request;

class UserActivity
{
    public static function recordLogin(User $user, Request $request): UserLoginEvent
    {
        return UserLoginEvent::create([
            'user_id' => $user->id,
            'login_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);
    }

    public static function recordPageVisit(User $user, Request $request, array $payload): UserPageActivityLog
    {
        $secondsSpent = max((int) ($payload['seconds_spent'] ?? 0), 1);
        $endedAt = now();

        return UserPageActivityLog::create([
            'user_id' => $user->id,
            'path' => (string) ($payload['path'] ?? '/'),
            'route_name' => $payload['route_name'] ?: null,
            'page_title' => $payload['page_title'] ?: null,
            'seconds_spent' => $secondsSpent,
            'started_at' => $endedAt->copy()->subSeconds($secondsSpent),
            'ended_at' => $endedAt,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);
    }
}
