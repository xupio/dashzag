<?php

use App\Models\PayoutRequest;
use App\Models\User;
use App\Models\UserInvestment;
use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('admin can export shareholders report as csv', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $investor = User::factory()->create([
        'name' => 'Test Investor',
        'email_verified_at' => now(),
        'account_type' => 'shareholder',
    ]);

    $package = \App\Models\InvestmentPackage::where('slug', 'momentum-300')->firstOrFail();

    UserInvestment::create([
        'user_id' => $investor->id,
        'miner_id' => $package->miner_id,
        'package_id' => $package->id,
        'shareholder_id' => null,
        'amount' => 300,
        'shares_owned' => 3,
        'monthly_return_rate' => $package->monthly_return_rate,
        'level_bonus_rate' => 0,
        'team_bonus_rate' => 0,
        'status' => 'active',
        'subscribed_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.shareholders.export', [
        'miner' => 'beta-flux',
        'status' => 'active',
        'package' => 'momentum-300',
        'search' => 'test',
    ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('Filter');
    expect($csv)->toContain('beta-flux');
    expect($csv)->toContain('momentum-300');
    expect($csv)->toContain('Investor Name');
    expect($csv)->toContain('Beta Flux');
    expect($csv)->toContain('Momentum 300');
});

test('admin can export payout operations report as csv', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'user',
    ]);

    PayoutRequest::create([
        'user_id' => $user->id,
        'amount' => 35,
        'fee_amount' => 0,
        'net_amount' => 35,
        'fee_rate' => 0,
        'method' => 'btc_wallet',
        'destination' => 'bc1-test-wallet-address',
        'status' => 'pending',
        'requested_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.operations.export'));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('User Name');
    expect($csv)->toContain('bc1-test-wallet-address');
    expect($csv)->toContain('pending');
});


test('admin can export analytics report as csv', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.analytics.export', ['miner' => 'alpha-one']));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('Section');
    expect($csv)->toContain('Selected miner slug');
    expect($csv)->toContain('alpha-one');
    expect($csv)->toContain('Total invested');
    expect($csv)->toContain('MLM payout breakdown');
    expect($csv)->toContain('Selected miner daily performance');
});

test('non admin user cannot export admin reports', function () {
    User::factory()->admin()->create(['email_verified_at' => now()]);
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)->get(route('dashboard.shareholders.export'))->assertForbidden();
    $this->actingAs($user)->get(route('dashboard.operations.export'))->assertForbidden();
    $this->actingAs($user)->get(route('dashboard.analytics.export'))->assertForbidden();
});
