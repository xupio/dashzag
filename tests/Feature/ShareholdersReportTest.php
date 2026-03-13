<?php

use App\Models\User;
use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('admin can view shareholders page', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.shareholders'));

    $response->assertOk();
    $response->assertSee('Shareholders');
});

test('shareholders page can filter by miner', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $alphaInvestor = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'user',
    ]);

    $betaInvestor = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'user',
    ]);

    $this->actingAs($alphaInvestor)->post(route('dashboard.buy-shares.subscribe'), ['package' => 'growth-500']);
    $this->actingAs($betaInvestor)->post(route('dashboard.buy-shares.subscribe'), ['package' => 'momentum-300']);

    $response = $this->actingAs($admin)->get(route('dashboard.shareholders', ['miner' => 'beta-flux']));

    $response->assertOk();
    $response->assertSee('Beta Flux');
    $response->assertSee('Momentum 300');
    $response->assertDontSee('Growth 500');
});

test('non admin user cannot access shareholders page', function () {
    User::factory()->admin()->create(['email_verified_at' => now()]);
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)->get(route('dashboard.shareholders'))->assertForbidden();
});

