<?php

use App\Models\User;
use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('admin can view notification rules page', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard.notification-rules'))
        ->assertOk()
        ->assertSee('Notification Rules')
        ->assertSee('Default channels by category');
});

test('admin can update notification rules and new users inherit them', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $this->actingAs($admin)
        ->post(route('dashboard.notification-rules.update'), [
            'notification_payout_in_app' => 1,
            'notification_payout_email' => 0,
            'notification_reward_in_app' => 1,
            'notification_reward_email' => 1,
            'notification_investment_in_app' => 0,
            'notification_investment_email' => 1,
            'notification_network_in_app' => 1,
            'notification_network_email' => 1,
            'notification_milestone_in_app' => 0,
            'notification_milestone_email' => 1,
        ])
        ->assertRedirect(route('dashboard.notification-rules'));

    expect(MiningPlatform::platformSetting('notification_payout_email'))->toBe('0');
    expect(MiningPlatform::platformSetting('notification_reward_email'))->toBe('1');
    expect(MiningPlatform::platformSetting('notification_investment_in_app'))->toBe('0');
    expect(MiningPlatform::platformSetting('notification_milestone_email'))->toBe('1');

    $newUser = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    expect($newUser->notificationPreferences()['payout']['email'])->toBeFalse();
    expect($newUser->notificationPreferences()['reward']['email'])->toBeTrue();
    expect($newUser->notificationPreferences()['investment']['in_app'])->toBeFalse();
    expect($newUser->notificationPreferences()['network']['email'])->toBeTrue();
    expect($newUser->notificationPreferences()['milestone']['email'])->toBeTrue();
});

test('non admin cannot access notification rules page', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'user',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard.notification-rules'))
        ->assertForbidden();
});
