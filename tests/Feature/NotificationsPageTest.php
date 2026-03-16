<?php

use App\Models\PayoutRequest;
use App\Models\User;
use App\Notifications\ActivityFeedNotification;
use App\Notifications\PayoutStatusNotification;
use App\Support\MiningPlatform;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    MiningPlatform::ensureDefaults();
});

function payoutRequestForNotification(User $user, string $status = 'pending'): PayoutRequest
{
    return PayoutRequest::create([
        'user_id' => $user->id,
        'amount' => 30,
        'fee_amount' => 2,
        'net_amount' => 28,
        'fee_rate' => 0.05,
        'method' => 'btc_wallet',
        'destination' => 'bc1-notify-destination',
        'notes' => 'Testing notifications',
        'status' => $status,
        'requested_at' => now(),
        'approved_at' => $status === 'approved' || $status === 'paid' ? now() : null,
        'processed_at' => $status === 'paid' ? now() : null,
    ]);
}

test('verified user can open notifications page', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $payoutRequest = payoutRequestForNotification($user);
    $user->notify(new PayoutStatusNotification($payoutRequest, 'submitted'));

    $response = $this->actingAs($user)->get(route('dashboard.notifications'));

    $response->assertOk();
    $response->assertSee('Notifications');
    $response->assertSee('Payout Request Submitted');
    $response->assertSee('Clear read');
    $response->assertSee('Clear previews');
});

test('notifications page can filter by category', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $payoutRequest = payoutRequestForNotification($user);
    $user->notify(new PayoutStatusNotification($payoutRequest, 'submitted'));
    $user->notify(new ActivityFeedNotification([
        'category' => 'reward',
        'status' => 'success',
        'subject' => 'Referral registration reward added',
        'message' => 'A reward was added to your wallet.',
        'amount' => 25,
        'amount_label' => 'Reward amount',
    ]));

    $response = $this->actingAs($user)->get(route('dashboard.notifications', ['filter' => 'reward']));

    $response->assertOk();
    $response->assertSee('Referral registration reward added');
    $response->assertSee('Reward amount');
    $response->assertDontSee('Net: $28.00');
});

test('header notification dropdown shows unread count and latest item', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $payoutRequest = payoutRequestForNotification($user);
    $user->notify(new PayoutStatusNotification($payoutRequest, 'submitted'));

    $response = $this->actingAs($user)->get(route('dashboard.profile'));

    $response->assertOk();
    $response->assertSee('1 New Notifications');
    $response->assertSee('Payout Request Submitted');
});

test('payout notifications are stored in the database feed', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $payoutRequest = payoutRequestForNotification($user);
    $user->notify(new PayoutStatusNotification($payoutRequest, 'submitted'));

    $user->refresh();

    expect($user->notifications)->toHaveCount(1);
    expect($user->notifications->first()->data['subject'])->toBe('Payout Request Submitted');
    expect($user->notifications->first()->read_at)->toBeNull();
});

test('user can mark a notification as read', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $payoutRequest = payoutRequestForNotification($user);
    $user->notify(new PayoutStatusNotification($payoutRequest, 'submitted'));

    $notification = $user->notifications()->firstOrFail();

    $this->actingAs($user)
        ->post(route('dashboard.notifications.read', $notification->id))
        ->assertRedirect(route('dashboard.notifications'));

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('user can mark all notifications as read', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $firstRequest = payoutRequestForNotification($user);
    $secondRequest = payoutRequestForNotification($user, 'approved');

    $user->notify(new PayoutStatusNotification($firstRequest, 'submitted'));
    $user->notify(new PayoutStatusNotification($secondRequest, 'approved'));

    $this->actingAs($user)
        ->post(route('dashboard.notifications.read-all'))
        ->assertRedirect(route('dashboard.notifications'));

    expect($user->fresh()->unreadNotifications)->toHaveCount(0);
});

test('user can clear read notifications', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $payoutRequest = payoutRequestForNotification($user);
    $user->notify(new PayoutStatusNotification($payoutRequest, 'submitted'));
    $notification = $user->notifications()->firstOrFail();
    $notification->markAsRead();

    $this->actingAs($user)
        ->post(route('dashboard.notifications.clear-read'))
        ->assertRedirect(route('dashboard.notifications'));

    expect($user->fresh()->notifications)->toHaveCount(0);
});

test('user can clear preview notifications', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $user->notify(new ActivityFeedNotification([
        'category' => 'reward',
        'status' => 'success',
        'subject' => 'Preview reward',
        'message' => 'Preview only.',
        'is_preview' => true,
        'context_label' => 'Preview event',
    ]));

    $user->notify(new ActivityFeedNotification([
        'category' => 'reward',
        'status' => 'success',
        'subject' => 'Real reward',
        'message' => 'Keep me.',
    ]));

    $this->actingAs($user)
        ->post(route('dashboard.notifications.clear-previews'))
        ->assertRedirect(route('dashboard.notifications'));

    $subjects = $user->fresh()->notifications->pluck('data.subject')->values();
    expect($subjects)->toContain('Real reward');
    expect($subjects)->not->toContain('Preview reward');
});

test('user cannot mark another users notification as read', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $otherUser = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $payoutRequest = payoutRequestForNotification($otherUser);
    $otherUser->notify(new PayoutStatusNotification($payoutRequest, 'submitted'));
    $notification = $otherUser->notifications()->firstOrFail();

    $this->actingAs($user)
        ->post(route('dashboard.notifications.read', $notification->id))
        ->assertNotFound();

    expect($notification->fresh()->read_at)->toBeNull();
});


test('investment review notifications can force email delivery even when user email preference is off', function () {
    $user = User::factory()->make([
        'notification_preferences' => [
            'investment' => [
                'in_app' => true,
                'email' => false,
            ],
        ],
    ]);

    $channels = (new ActivityFeedNotification([
        'category' => 'investment',
        'force_mail' => true,
        'subject' => 'Investment payment submitted',
        'message' => 'Your payment is waiting for review.',
    ]))->via($user);

    expect($channels)->toContain('database');
    expect($channels)->toContain('mail');
});test('admin can prune old notifications by category', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $oldReward = $admin->notifications()->create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'type' => ActivityFeedNotification::class,
        'data' => [
            'category' => 'reward',
            'status' => 'success',
            'subject' => 'Old reward',
            'message' => 'Old reward body',
        ],
        'created_at' => now()->subDays(40),
        'updated_at' => now()->subDays(40),
    ]);

    $recentReward = $admin->notifications()->create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'type' => ActivityFeedNotification::class,
        'data' => [
            'category' => 'reward',
            'status' => 'success',
            'subject' => 'Recent reward',
            'message' => 'Recent reward body',
        ],
        'created_at' => now()->subDays(2),
        'updated_at' => now()->subDays(2),
    ]);

    $oldPayout = $admin->notifications()->create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'type' => PayoutStatusNotification::class,
        'data' => [
            'category' => 'payout',
            'status' => 'submitted',
            'subject' => 'Old payout',
            'message' => 'Old payout body',
        ],
        'created_at' => now()->subDays(40),
        'updated_at' => now()->subDays(40),
    ]);

    $this->actingAs($admin)
        ->post(route('dashboard.notifications.prune'), [
            'filter' => 'reward',
            'older_than_days' => 30,
        ])
        ->assertRedirect(route('dashboard.notifications'));

    expect($oldReward->fresh())->toBeNull();
    expect($recentReward->fresh())->not->toBeNull();
    expect($oldPayout->fresh())->not->toBeNull();
});
test('notifications page shows investment order proof upload entries with grouped labels', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $user->notify(new ActivityFeedNotification([
        'category' => 'investment',
        'status' => 'info',
        'subject' => 'Payment proof uploaded',
        'message' => 'Your payment proof for Growth 500 has been uploaded successfully and is now waiting for admin review.',
        'context_label' => 'Uploaded file',
        'context_value' => 'receipt.pdf',
        'amount' => 500,
        'amount_label' => 'Submitted amount',
    ]));

    $response = $this->actingAs($user)->get(route('dashboard.notifications', ['filter' => 'investment']));

    $response->assertOk();
    $response->assertSee('Investment Order Update');
    $response->assertSee('Investment review');
    $response->assertSee('Payment proof uploaded');
    $response->assertSee('Your payment proof for Growth 500 has been uploaded successfully');
});

test('notifications page shows override approval entries with grouped labels', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $user->notify(new ActivityFeedNotification([
        'category' => 'investment',
        'status' => 'warning',
        'subject' => 'Investment approved without proof override',
        'message' => 'Your Growth 500 order was approved using an admin override before a payment proof was uploaded.',
        'context_label' => 'Admin reason',
        'context_value' => 'Bank desk confirmed the transfer manually.',
        'amount' => 500,
        'amount_label' => 'Approved amount',
    ]));

    $response = $this->actingAs($user)->get(route('dashboard.notifications', ['filter' => 'investment']));

    $response->assertOk();
    $response->assertSee('Investment Order Update');
    $response->assertSee('Investment approved without proof override');
    $response->assertSee('Bank desk confirmed the transfer manually.');
});

test('notifications page shows hall of fame winner entries with champion grouping', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $user->notify(new ActivityFeedNotification([
        'event_key' => 'hall_of_fame_weekly_winner',
        'category' => 'milestone',
        'status' => 'success',
        'subject' => 'Weekly Hall of Fame winner',
        'message' => 'You reached #1 in the weekly Hall of Fame with 68 points.',
        'context_label' => 'Period',
        'context_value' => 'Mar 09 - Mar 15',
    ]));

    $response = $this->actingAs($user)->get(route('dashboard.notifications', ['filter' => 'milestone']));

    $response->assertOk();
    $response->assertSee('Champion Win');
    $response->assertSee('Hall of Fame');
    $response->assertSee('Weekly Hall of Fame winner');
    $response->assertSee('You reached #1 in the weekly Hall of Fame with 68 points.');
});


