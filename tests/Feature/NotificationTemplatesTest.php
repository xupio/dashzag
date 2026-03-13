<?php

use App\Models\PayoutRequest;
use App\Models\User;
use App\Notifications\PayoutStatusNotification;
use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

function payoutRequestForTemplateTest(User $user): PayoutRequest
{
    return PayoutRequest::create([
        'user_id' => $user->id,
        'amount' => 55,
        'fee_amount' => 5,
        'net_amount' => 50,
        'fee_rate' => 0.05,
        'method' => 'btc_wallet',
        'destination' => 'bc1-template-destination',
        'status' => 'pending',
        'requested_at' => now(),
    ]);
}

test('admin can view notification templates page', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard.notification-templates'))
        ->assertOk()
        ->assertSee('Notification Templates')
        ->assertSee('Payout Submitted');
});

test('admin can update notification templates and they are applied', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $this->actingAs($admin)
        ->post(route('dashboard.notification-templates.update'), [
            'template_payout_submitted_subject' => 'Cash-out request received',
            'template_payout_submitted_message' => 'We received your :method_label payout request for :net_amount net.',
            'template_payout_approved_subject' => 'Cash-out approved',
            'template_payout_approved_message' => 'Your payout through :method_label has been approved.',
            'template_payout_paid_subject' => 'Cash-out completed',
            'template_payout_paid_message' => 'Your payout to :destination has been marked paid.',
            'template_free_starter_subject' => 'Starter mission enabled',
            'template_free_starter_message' => 'Your free starter package is ready.',
            'template_network_join_subject' => 'A new teammate joined',
            'template_network_join_message' => ':user_name entered your network.',
            'template_reward_registration_subject' => 'Registration reward posted',
            'template_reward_registration_message' => ':user_name triggered a registration reward.',
            'template_network_sponsor_subject' => 'Sponsor connection ready',
            'template_network_sponsor_message' => 'You are now linked to :sponsor_name.',
            'template_basic_unlocked_subject' => 'Basic unlocked',
            'template_basic_unlocked_message' => ':package_name is now active.',
            'template_investment_activated_subject' => 'Investment live',
            'template_investment_activated_message' => ':package_name has started mining.',
            'template_team_level_1_subject' => 'Direct team reward posted',
            'template_team_level_1_message' => ':user_name activated :package_name.',
            'template_team_level_2_subject' => 'Level 2 reward posted',
            'template_team_level_2_message' => ':user_name subscribed in your extended team.',
            'template_team_level_generic_subject' => 'Level :level reward posted',
            'template_team_level_generic_message' => ':user_name subscribed on level :level.',
        ])
        ->assertRedirect(route('dashboard.notification-templates'));

    expect(MiningPlatform::notificationTemplateSetting('template_payout_submitted_subject'))->toBe('Cash-out request received');

    $template = MiningPlatform::activityTemplate('network_join', [
        'user_name' => 'Nora',
    ]);

    expect($template['subject'])->toBe('A new teammate joined');
    expect($template['message'])->toBe('Nora entered your network.');

    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);
    $payoutRequest = payoutRequestForTemplateTest($user);
    $notification = new PayoutStatusNotification($payoutRequest, 'submitted');
    $payload = $notification->toArray($user);

    expect($payload['subject'])->toBe('Cash-out request received');
    expect($payload['message'])->toBe('We received your BTC Wallet payout request for 50.00 net.');
});


test('admin can send a preview notification to the dashboard feed', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $this->actingAs($admin)
        ->post(route('dashboard.notification-templates.preview'), [
            'template_key' => 'reward_registration',
        ])
        ->assertRedirect(route('dashboard.notification-templates'));

    $admin->refresh();

    expect($admin->notifications)->toHaveCount(1);
    expect($admin->notifications->first()->data['subject'])->toBe('Referral registration reward added');
});test('non admin cannot access notification templates page', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'user',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard.notification-templates'))
        ->assertForbidden();
});