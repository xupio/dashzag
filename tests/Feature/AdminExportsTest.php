<?php

use App\Models\User;
use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('admin can export shareholders report as csv', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $investor = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'user',
    ]);

    $this->actingAs($investor)->post(route('general.sell-products.subscribe'), [
        'package' => 'momentum-300',
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.shareholders.export', ['miner' => 'beta-flux']));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

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

    $this->actingAs($user)->post(route('general.sell-products.subscribe'), [
        'package' => 'growth-500',
    ]);
    $this->actingAs($user)->post(route('dashboard.wallet.generate'));
    $this->actingAs($user)->post(route('dashboard.wallet.request'), [
        'amount' => 20,
        'method' => 'btc_wallet',
        'destination' => 'bc1-test-wallet-address',
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.operations.export'));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('User Name');
    expect($csv)->toContain('bc1-test-wallet-address');
    expect($csv)->toContain('pending');
});

test('non admin user cannot export admin reports', function () {
    User::factory()->admin()->create(['email_verified_at' => now()]);
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)->get(route('dashboard.shareholders.export'))->assertForbidden();
    $this->actingAs($user)->get(route('dashboard.operations.export'))->assertForbidden();
});
