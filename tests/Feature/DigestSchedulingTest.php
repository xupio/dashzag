<?php

use App\Models\User;
use App\Notifications\ActivityFeedNotification;
use App\Support\MiningPlatform;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    MiningPlatform::ensureDefaults();
});

test('daily digest command sends to due daily users only once per day', function () {
    $dailyUser = User::factory()->create([
        'email_verified_at' => now(),
        'notification_preferences' => array_replace_recursive(User::defaultNotificationPreferences(), [
            'digest' => ['in_app' => true, 'email' => false, 'frequency' => 'daily'],
        ]),
    ]);

    $weeklyUser = User::factory()->create([
        'email_verified_at' => now(),
        'notification_preferences' => array_replace_recursive(User::defaultNotificationPreferences(), [
            'digest' => ['in_app' => true, 'email' => false, 'frequency' => 'weekly'],
        ]),
    ]);

    $dailyUser->notify(new ActivityFeedNotification([
        'category' => 'reward',
        'status' => 'success',
        'subject' => 'Daily reward',
        'message' => 'Daily reward body',
    ]));

    $weeklyUser->notify(new ActivityFeedNotification([
        'category' => 'reward',
        'status' => 'success',
        'subject' => 'Weekly reward',
        'message' => 'Weekly reward body',
    ]));

    Artisan::call('notifications:send-digests', ['--frequency' => 'daily']);

    $dailySubjects = $dailyUser->fresh()->notifications->pluck('data.subject')->values();
    $weeklySubjects = $weeklyUser->fresh()->notifications->pluck('data.subject')->values();

    expect($dailySubjects)->toContain('Daily digest summary');
    expect($weeklySubjects)->not->toContain('Weekly digest summary');
    expect($dailyUser->fresh()->last_daily_digest_sent_at)->not->toBeNull();

    Artisan::call('notifications:send-digests', ['--frequency' => 'daily']);

    $digestCount = $dailyUser->fresh()->notifications->filter(fn ($notification) => ($notification->data['category'] ?? null) === 'digest')->count();
    expect($digestCount)->toBe(1);
});

test('weekly digest command sends to due weekly users', function () {
    $weeklyUser = User::factory()->create([
        'email_verified_at' => now(),
        'notification_preferences' => array_replace_recursive(User::defaultNotificationPreferences(), [
            'digest' => ['in_app' => true, 'email' => false, 'frequency' => 'weekly'],
        ]),
    ]);

    $weeklyUser->notify(new ActivityFeedNotification([
        'category' => 'network',
        'status' => 'info',
        'subject' => 'Network update',
        'message' => 'Network update body',
    ]));

    Artisan::call('notifications:send-digests', ['--frequency' => 'weekly']);

    $subjects = $weeklyUser->fresh()->notifications->pluck('data.subject')->values();

    expect($subjects)->toContain('Weekly digest summary');
    expect($weeklyUser->fresh()->last_weekly_digest_sent_at)->not->toBeNull();
});

test('digest command skips users with no digest activity', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'notification_preferences' => array_replace_recursive(User::defaultNotificationPreferences(), [
            'digest' => ['in_app' => true, 'email' => false, 'frequency' => 'daily'],
        ]),
    ]);

    Artisan::call('notifications:send-digests', ['--frequency' => 'daily']);

    expect($user->fresh()->notifications)->toHaveCount(0);
    expect($user->fresh()->last_daily_digest_sent_at)->toBeNull();
});