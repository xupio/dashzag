<?php

use App\Models\AdminActivityLog;
use App\Models\User;
use App\Notifications\ActivityFeedNotification;
use App\Notifications\AdminHealthSummaryNotification;
use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('admin can open the security center page', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $admin->notify(new ActivityFeedNotification([
        'category' => 'admin',
        'status' => 'warning',
        'subject' => 'Repeated failed login attempts detected',
        'message' => 'Test alert message.',
        'context_label' => 'Login identifier',
        'context_value' => 'alert@example.com',
        'force_mail' => true,
    ]));

    $admin->notify(new AdminHealthSummaryNotification([
        'period_label' => 'the last 24 hours',
        'pending_investment_orders' => 2,
        'pending_payout_requests' => 1,
        'pending_orders_with_proof' => 1,
        'pending_orders_missing_proof' => 1,
        'stale_pending_investments' => 0,
        'stale_pending_payouts' => 0,
        'recent_admin_actions' => 3,
        'pending_friend_invitations' => 4,
    ]));

    AdminActivityLog::query()->create([
        'admin_user_id' => $admin->id,
        'action' => 'payout.approve',
        'summary' => 'Approved payout request #42',
        'details' => ['source' => 'test'],
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.security-center'));

    $response->assertOk();
    $response->assertSee('Security Center');
    $response->assertSee('Admin safety center');
    $response->assertSee('Current health snapshot');
    $response->assertSee('Repeated failed login attempts detected');
    $response->assertSee('Daily admin health summary');
    $response->assertSee('Approved payout request #42');
});

test('admin can export the security center as word and printable pdf view', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $wordResponse = $this->actingAs($admin)->get(route('dashboard.security-center.export.word'));
    $wordResponse->assertOk();
    $wordResponse->assertHeader('content-type', 'application/msword; charset=UTF-8');
    $wordResponse->assertSee('ZagChain Security Center');
    $wordResponse->assertSee('Admin safety center');

    $pdfResponse = $this->actingAs($admin)->get(route('dashboard.security-center.export.pdf'));
    $pdfResponse->assertOk();
    $pdfResponse->assertSee('ZagChain Security Center');
    $pdfResponse->assertSee('Print / Save as PDF');
});

test('non admin users cannot open the security center page', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard.security-center'))
        ->assertForbidden();
});
