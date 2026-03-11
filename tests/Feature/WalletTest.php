<?php

use App\Models\PayoutRequest;
use App\Models\User;
use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('verified user can generate monthly wallet earnings from active investments', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'user',
    ]);

    $this->actingAs($user)->post(route('general.sell-products.subscribe'), [
        'package' => 'growth-500',
    ])->assertRedirect(route('general.sell-products'));

    $response = $this->actingAs($user)->post(route('dashboard.wallet.generate'));

    $response->assertRedirect(route('dashboard.wallet'));

    $user->refresh();
    $user->load('earnings');

    expect($user->earnings->where('source', 'mining_return'))->toHaveCount(1);
    expect($user->earnings->where('status', 'available')->sum('amount'))->toBeGreaterThan(0);
});

test('verified user can request a payout from available balance', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'user',
    ]);

    $this->actingAs($user)->post(route('general.sell-products.subscribe'), [
        'package' => 'growth-500',
    ]);

    $this->actingAs($user)->post(route('dashboard.wallet.generate'));

    $response = $this->actingAs($user)->post(route('dashboard.wallet.request'), [
        'amount' => 20,
        'method' => 'btc_wallet',
        'destination' => 'bc1-test-wallet-address',
        'notes' => 'First withdrawal request',
    ]);

    $response->assertRedirect(route('dashboard.wallet'));

    $user->refresh();
    $user->load(['earnings', 'payoutRequests']);

    expect($user->payoutRequests)->toHaveCount(1);
    expect((float) $user->payoutRequests->first()->amount)->toBe(20.0);
    expect($user->earnings->where('status', 'payout_pending')->sum('amount'))->toBe(20.0);
});

test('admin can approve and pay payout requests', function () {
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

    $payoutRequest = PayoutRequest::firstOrFail();

    $this->actingAs($admin)->post(route('dashboard.operations.payouts.approve', $payoutRequest))
        ->assertRedirect(route('dashboard.operations'));

    $payoutRequest->refresh();
    expect($payoutRequest->status)->toBe('approved');

    $this->actingAs($admin)->post(route('dashboard.operations.payouts.pay', $payoutRequest))
        ->assertRedirect(route('dashboard.operations'));

    $payoutRequest->refresh();
    $user->refresh();
    $user->load('earnings');

    expect($payoutRequest->status)->toBe('paid');
    expect($user->earnings->where('status', 'paid')->sum('amount'))->toBe(20.0);
});

test('non admin user cannot access operations page', function () {
    User::factory()->admin()->create(['email_verified_at' => now()]);
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)->get(route('dashboard.operations'))->assertForbidden();
});

test('non admin user cannot access miner page', function () {
    User::factory()->admin()->create(['email_verified_at' => now()]);
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)->get(route('dashboard.miner'))->assertForbidden();
});

test('wallet page is available to verified users', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard.wallet'));

    $response->assertOk();
    $response->assertSee('Wallet');
});
