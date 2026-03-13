<?php

use App\Models\PayoutRequest;
use App\Models\User;
use App\Notifications\ActivityFeedNotification;
use App\Notifications\DigestSummaryNotification;
use App\Notifications\PayoutStatusNotification;
use App\Support\MiningPlatform;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    MiningPlatform::ensureDefaults();
});

function payoutRequestForPreferences(User $user, string $status = 'pending'): PayoutRequest
{
    return PayoutRequest::create([
        'user_id' => $user->id,
        'amount' => 45,
        'fee_amount' => 3,
        'net_amount' => 42,
        'fee_rate' => 0.05,
        'method' => 'btc_wallet',
        'destination' => 'bc1-preferences-destination',
        'notes' => 'Testing preferences',
        'status' => $status,
        'requested_at' => now(),
    ]);
}

test('verified user can open notification preferences page', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard.notification-preferences'));

    $response->assertOk();
    $response->assertSee('Notification Preferences');
    $response->assertSee('Delivery channels');
    $response->assertSee('Digest frequency');
    $response->assertSee('Send digest summary');
});

test('user can update notification preferences including digest settings', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('dashboard.notification-preferences.update'), [
            'payout_in_app' => '1',
            'payout_email' => '0',
            'reward_in_app' => '1',
            'reward_email' => '1',
            'investment_in_app' => '0',
            'investment_email' => '1',
            'network_in_app' => '1',
            'network_email' => '0',
            'milestone_in_app' => '1',
            'milestone_email' => '1',
            'digest_in_app' => '1',
            'digest_email' => '1',
            'digest_frequency' => 'daily',
        ])
        ->assertRedirect(route('dashboard.notification-preferences'));

    $user->refresh();

    expect($user->notificationPreferences()['payout']['email'])->toBeFalse();
    expect($user->notificationPreferences()['reward']['email'])->toBeTrue();
    expect($user->notificationPreferences()['investment']['in_app'])->toBeFalse();
    expect($user->notificationPreferences()['milestone']['email'])->toBeTrue();
    expect($user->notificationPreferences()['digest']['email'])->toBeTrue();
    expect($user->digestFrequency())->toBe('daily');
});

test('user can generate a digest summary notification', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'notification_preferences' => array_replace_recursive(User::defaultNotificationPreferences(), [
            'digest' => ['in_app' => true, 'email' => false, 'frequency' => 'weekly'],
        ]),
    ]);

    $user->notify(new ActivityFeedNotification([
        'category' => 'reward',
        'status' => 'success',
        'subject' => 'Reward event',
        'message' => 'Reward event body',
    ]));

    $this->actingAs($user)
        ->post(route('dashboard.notification-preferences.generate-digest'))
        ->assertRedirect(route('dashboard.notification-preferences'));

    $subjects = $user->fresh()->notifications->pluck('data.subject')->values();
    expect($subjects)->toContain('Weekly digest summary');
});

test('payout notification is skipped when both channels are disabled', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'notification_preferences' => array_replace_recursive(User::defaultNotificationPreferences(), [
            'payout' => ['in_app' => false, 'email' => false],
        ]),
    ]);

    $payoutRequest = payoutRequestForPreferences($user);
    $user->notify(new PayoutStatusNotification($payoutRequest, 'submitted'));
    $user->refresh();

    expect($user->notifications)->toHaveCount(0);
    Mail::assertNothingSent();
});

test('activity notification uses email-only preference when enabled', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'notification_preferences' => array_replace_recursive(User::defaultNotificationPreferences(), [
            'reward' => ['in_app' => false, 'email' => true],
        ]),
    ]);

    $notification = new ActivityFeedNotification([
        'category' => 'reward',
        'status' => 'success',
        'subject' => 'Referral registration reward added',
        'message' => 'A reward was added to your wallet.',
        'amount' => 25,
        'amount_label' => 'Reward amount',
    ]);

    expect($notification->via($user))->toBe(['mail']);

    $user->notify($notification);
    $user->refresh();

    expect($user->notifications)->toHaveCount(0);
});