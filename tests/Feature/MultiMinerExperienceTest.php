<?php

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
        ->assertSee('Beta Flux Mining Dashboard');

    $this->actingAs($user)
        ->get(route('general.sell-products').'?miner=beta-flux')
        ->assertOk()
        ->assertSee('Buy shares in Beta Flux')
        ->assertSee('Momentum 300');
});

test('user can subscribe to a package on the secondary miner', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'user',
    ]);

    $response = $this->actingAs($user)->post(route('general.sell-products.subscribe'), [
        'package' => 'momentum-300',
    ]);

    $response->assertRedirect(route('general.sell-products', ['miner' => 'beta-flux']));

    $user->refresh();
    $investment = $user->investments()->with(['miner', 'package'])->latest('id')->first();

    expect($investment)->not->toBeNull();
    expect($investment->miner->slug)->toBe('beta-flux');
    expect($investment->package->slug)->toBe('momentum-300');
    expect((int) $investment->shares_owned)->toBe(4);
});

