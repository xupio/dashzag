<?php

use App\Models\AdminActivityLog;
use App\Models\FriendInvitation;
use App\Models\InvestmentPackage;
use App\Models\InvestmentOrder;
use App\Models\Miner;
use App\Models\PayoutRequest;
use App\Models\User;
use App\Notifications\AdminHealthSummaryNotification;
use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('admin health summary command sends a notification to verified admins only', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $miner = Miner::query()->firstOrFail();
    $package = InvestmentPackage::query()->firstOrFail();

    InvestmentOrder::query()->create([
        'user_id' => $user->id,
        'miner_id' => $miner->id,
        'package_id' => $package->id,
        'amount' => 500,
        'shares_owned' => 5,
        'payment_method' => 'usdt_transfer',
        'payment_reference' => 'TEST-HEALTH-ORDER',
        'status' => 'pending',
        'submitted_at' => now()->subDays(2),
    ]);

    PayoutRequest::query()->create([
        'user_id' => $user->id,
        'amount' => 120,
        'fee_amount' => 0,
        'net_amount' => 120,
        'fee_rate' => 0,
        'method' => 'btc_wallet',
        'destination' => 'bc1qhealthsummarytest',
        'status' => 'pending',
        'requested_at' => now()->subDays(2),
    ]);

    FriendInvitation::query()->create([
        'user_id' => $admin->id,
        'name' => 'Pending Invite',
        'email' => 'pending-invite@example.test',
        'verified_at' => null,
    ]);

    AdminActivityLog::query()->create([
        'admin_user_id' => $admin->id,
        'action' => 'investment.approve',
        'summary' => 'Approved an investment order',
        'details' => ['source' => 'test'],
    ]);

    $this->artisan('admin:send-health-summary')
        ->expectsOutput('Sent 1 admin health summary notifications.')
        ->assertExitCode(0);

    $admin->refresh();
    $user->refresh();

    expect($admin->notifications)->toHaveCount(1);
    expect($admin->notifications->first()->type)->toBe(AdminHealthSummaryNotification::class);
    expect($admin->notifications->first()->data['admin_health_summary']['pending_investment_orders'])->toBe(1);
    expect($admin->notifications->first()->data['admin_health_summary']['pending_payout_requests'])->toBe(1);
    expect($user->notifications)->toHaveCount(0);
});

test('admin health summary command does not duplicate notifications on the same day', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $this->artisan('admin:send-health-summary')->assertExitCode(0);
    $this->artisan('admin:send-health-summary')
        ->expectsOutput('Sent 0 admin health summary notifications.')
        ->assertExitCode(0);

    expect($admin->fresh()->notifications)->toHaveCount(1);
});
