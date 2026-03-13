<?php

use App\Models\User;
use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('verified user can view investments page', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'user',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard.investments'));

    $response->assertOk();
    $response->assertSee('My Investments');
});

test('investments page shows subscribed package history', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'user',
    ]);

    $this->actingAs($user)->post(route('dashboard.buy-shares.subscribe'), [
        'package' => 'growth-500',
    ])->assertRedirect(route('dashboard.buy-shares', ['miner' => 'alpha-one']));

    $response = $this->actingAs($user)->get(route('dashboard.investments'));

    $response->assertOk();
    $response->assertSee('Growth 500');
    $response->assertSee('Alpha One');
});


