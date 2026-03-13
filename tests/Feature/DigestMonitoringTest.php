<?php

use App\Models\User;
use App\Notifications\ActivityFeedNotification;
use App\Notifications\DigestSummaryNotification;
use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('admin can open digest monitoring page', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $dailyUser = User::factory()->create([
        'name' => 'Daily Digest User',
        'email' => 'daily-digest@example.com',
        'email_verified_at' => now(),
        'notification_preferences' => array_replace_recursive(User::defaultNotificationPreferences(), [
            'digest' => ['in_app' => true, 'email' => true, 'frequency' => 'daily'],
        ]),
        'last_daily_digest_sent_at' => now()->subHours(6),
    ]);

    $weeklyUser = User::factory()->create([
        'name' => 'Weekly Digest User',
        'email' => 'weekly-digest@example.com',
        'email_verified_at' => now(),
        'notification_preferences' => array_replace_recursive(User::defaultNotificationPreferences(), [
            'digest' => ['in_app' => true, 'email' => false, 'frequency' => 'weekly'],
        ]),
        'last_weekly_digest_sent_at' => now()->subDays(3),
    ]);

    $dailyUser->notify(new ActivityFeedNotification([
        'category' => 'reward',
        'status' => 'success',
        'subject' => 'Reward event',
        'message' => 'Reward body',
    ]));

    $dailyUser->notify(new DigestSummaryNotification('daily', [
        'frequency' => 'daily',
        'period_label' => 'the last 24 hours',
        'total' => 3,
        'unread' => 1,
        'payout' => 1,
        'reward' => 1,
        'investment' => 1,
        'network' => 0,
        'milestone' => 0,
    ], 'the last 24 hours'));

    $response = $this->actingAs($admin)->get(route('dashboard.digests'));

    $response->assertOk();
    $response->assertSee('Digest Monitoring');
    $response->assertSee('Daily Digest User');
    $response->assertSee('daily-digest@example.com');
    $response->assertSee('Weekly Digest User');
    $response->assertSee('weekly-digest@example.com');
    $response->assertSee('Daily digests');
    $response->assertSee('Weekly digests');
    $response->assertSee('Email enabled');
    $response->assertSee('No recent activity');
    $response->assertSee('Active');
});


test('admin can manually send a digest from monitoring page', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $user = User::factory()->create([
        'name' => 'Manual Digest User',
        'email' => 'manual-digest@example.com',
        'email_verified_at' => now(),
        'notification_preferences' => array_replace_recursive(User::defaultNotificationPreferences(), [
            'digest' => ['in_app' => true, 'email' => false, 'frequency' => 'weekly'],
        ]),
    ]);

    $this->actingAs($admin)
        ->post(route('dashboard.digests.send', $user), [
            'frequency' => 'daily',
        ])
        ->assertRedirect(route('dashboard.digests'));

    $user->refresh();

    expect($user->last_daily_digest_sent_at)->not->toBeNull();
    expect($user->notifications->pluck('data.subject')->contains('Daily digest summary'))->toBeTrue();
});

test('admin can bulk send digests to matching verified users', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $dailyUser = User::factory()->create([
        'name' => 'Bulk Daily User',
        'email' => 'bulk-daily@example.com',
        'email_verified_at' => now(),
        'notification_preferences' => array_replace_recursive(User::defaultNotificationPreferences(), [
            'digest' => ['in_app' => true, 'email' => false, 'frequency' => 'daily'],
        ]),
    ]);

    $weeklyUser = User::factory()->create([
        'name' => 'Bulk Weekly User',
        'email' => 'bulk-weekly@example.com',
        'email_verified_at' => now(),
        'notification_preferences' => array_replace_recursive(User::defaultNotificationPreferences(), [
            'digest' => ['in_app' => true, 'email' => false, 'frequency' => 'weekly'],
        ]),
    ]);

    $this->actingAs($admin)
        ->post(route('dashboard.digests.bulk-send'), [
            'frequency' => 'daily',
            'scope' => 'matching',
            'segment' => 'all',
        ])
        ->assertRedirect(route('dashboard.digests'));

    $dailyUser->refresh();
    $weeklyUser->refresh();

    expect($dailyUser->last_daily_digest_sent_at)->not->toBeNull();
    expect($dailyUser->notifications->pluck('data.subject')->contains('Daily digest summary'))->toBeTrue();
    expect($weeklyUser->last_daily_digest_sent_at)->toBeNull();
    expect($weeklyUser->notifications->pluck('data.subject')->contains('Daily digest summary'))->toBeFalse();
});

test('admin can bulk send digests to email-enabled users only', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $emailEnabledUser = User::factory()->create([
        'name' => 'Email Enabled User',
        'email' => 'email-enabled@example.com',
        'email_verified_at' => now(),
        'notification_preferences' => array_replace_recursive(User::defaultNotificationPreferences(), [
            'digest' => ['in_app' => true, 'email' => true, 'frequency' => 'weekly'],
        ]),
    ]);

    $emailDisabledUser = User::factory()->create([
        'name' => 'Email Disabled User',
        'email' => 'email-disabled@example.com',
        'email_verified_at' => now(),
        'notification_preferences' => array_replace_recursive(User::defaultNotificationPreferences(), [
            'digest' => ['in_app' => true, 'email' => false, 'frequency' => 'weekly'],
        ]),
    ]);

    $this->actingAs($admin)
        ->post(route('dashboard.digests.bulk-send'), [
            'frequency' => 'weekly',
            'scope' => 'all_verified',
            'segment' => 'email_enabled',
        ])
        ->assertRedirect(route('dashboard.digests'));

    $emailEnabledUser->refresh();
    $emailDisabledUser->refresh();

    expect($emailEnabledUser->last_weekly_digest_sent_at)->not->toBeNull();
    expect($emailEnabledUser->notifications->pluck('data.subject')->contains('Weekly digest summary'))->toBeTrue();
    expect($emailDisabledUser->last_weekly_digest_sent_at)->toBeNull();
    expect($emailDisabledUser->notifications->pluck('data.subject')->contains('Weekly digest summary'))->toBeFalse();
});



test('admin can filter digest monitoring by segment', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    User::factory()->create([
        'name' => 'Filtered Email User',
        'email' => 'filtered-email@example.com',
        'email_verified_at' => now(),
        'notification_preferences' => array_replace_recursive(User::defaultNotificationPreferences(), [
            'digest' => ['in_app' => true, 'email' => true, 'frequency' => 'weekly'],
        ]),
    ]);

    User::factory()->create([
        'name' => 'Hidden Non Email User',
        'email' => 'hidden-non-email@example.com',
        'email_verified_at' => now(),
        'notification_preferences' => array_replace_recursive(User::defaultNotificationPreferences(), [
            'digest' => ['in_app' => true, 'email' => false, 'frequency' => 'daily'],
        ]),
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.digests', [
        'segment' => 'email_enabled',
    ]));

    $response->assertOk();
    $response->assertSee('Quick filters:');
    $response->assertSee('Filtered Email User');
    $response->assertDontSee('Hidden Non Email User');
    $response->assertSee('1 of 3 verified users');
});


test('admin can filter and export digest history report', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $manualUser = User::factory()->create([
        'name' => 'History Digest User',
        'email' => 'history-digest@example.com',
        'email_verified_at' => now(),
        'notification_preferences' => array_replace_recursive(User::defaultNotificationPreferences(), [
            'digest' => ['in_app' => true, 'email' => false, 'frequency' => 'weekly'],
        ]),
    ]);

    $bulkUser = User::factory()->create([
        'name' => 'Bulk Export User',
        'email' => 'bulk-export@example.com',
        'email_verified_at' => now(),
        'notification_preferences' => array_replace_recursive(User::defaultNotificationPreferences(), [
            'digest' => ['in_app' => true, 'email' => false, 'frequency' => 'daily'],
        ]),
    ]);

    $this->actingAs($admin)
        ->post(route('dashboard.digests.send', $manualUser), [
            'frequency' => 'weekly',
        ])
        ->assertRedirect(route('dashboard.digests'));

    $this->actingAs($admin)
        ->post(route('dashboard.digests.bulk-send'), [
            'frequency' => 'daily',
            'scope' => 'matching',
            'segment' => 'all',
        ])
        ->assertRedirect(route('dashboard.digests'));

    $response = $this->actingAs($admin)->get(route('dashboard.digests.history', [
        'source' => 'admin_manual',
        'frequency' => 'weekly',
    ]));

    $response->assertOk();
    $response->assertSee('Digest History');
    $response->assertSee('history-digest@example.com');
    $response->assertSee('Admin manual');
    $response->assertSee('Top recipients');
    $response->assertSee('Source breakdown');
    $response->assertSee('History Digest User');
    $response->assertDontSee('bulk-export@example.com');

    $export = $this->actingAs($admin)->get(route('dashboard.digests.history.export', [
        'source' => 'admin_bulk',
        'frequency' => 'daily',
    ]));

    $export->assertOk();
    $export->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($export->streamedContent())->toContain('bulk-export@example.com');
    expect($export->streamedContent())->not->toContain('history-digest@example.com');
});
test('non admin user cannot open digest monitoring page', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'user',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard.digests'))
        ->assertForbidden();
});


