<?php

use App\Models\InvestmentOrder;
use App\Models\User;
use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('user can switch dashboard and sell pages between miners', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'user',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard').'?miner=beta-flux')
        ->assertOk()
        ->assertSee('Beta Flux Overview')
        ->assertSee('Share status');

    $this->actingAs($user)
        ->get(route('dashboard.buy-shares').'?miner=beta-flux')
        ->assertOk()
        ->assertSee('Buy Beta Flux Shares')
        ->assertSee('Momentum 300');
});

test('user can submit a package payment on the secondary miner for review', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'user',
    ]);

    $response = $this->actingAs($user)->post(route('dashboard.buy-shares.subscribe'), [
        'package' => 'momentum-300',
        'payment_method' => 'usdt_transfer',
        'payment_reference' => 'BETA-REF-1001',
    ]);

    $response->assertRedirect(route('dashboard.buy-shares', ['miner' => 'beta-flux']));

    $order = InvestmentOrder::query()->latest('id')->first();

    expect($order)->not->toBeNull();
    expect($order->status)->toBe('pending');
    expect($order->miner->slug)->toBe('beta-flux');
    expect($order->package->slug)->toBe('momentum-300');
});
