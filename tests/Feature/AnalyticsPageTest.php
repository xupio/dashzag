<?php

use App\Models\FriendInvitation;
use App\Models\User;
use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('admin can view analytics page', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.analytics'));

    $response->assertOk();
    $response->assertSee('Admin Analytics');
});

test('analytics page shows investment referral and miner metrics', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $investor = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'user',
    ]);

    $referrer = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    FriendInvitation::create([
        'user_id' => $referrer->id,
        'name' => 'Analytics Buyer',
        'email' => 'analyticsbuyer@example.com',
        'verified_at' => now(),
        'registered_at' => now(),
    ]);

    $buyer = User::factory()->create([
        'email' => 'analyticsbuyer@example.com',
        'email_verified_at' => now(),
        'account_type' => 'user',
    ]);

    $this->actingAs($investor)->post(route('dashboard.buy-shares.subscribe'), ['package' => 'growth-500']);
    $this->actingAs($buyer)->post(route('dashboard.buy-shares.subscribe'), ['package' => 'momentum-300']);

    $response = $this->actingAs($admin)->get(route('dashboard.analytics'));

    $response->assertOk();
    $response->assertSee('Top investors');
    $response->assertSee('Top referrers');
    $response->assertSee('Package performance');
    $response->assertSee('Miner performance breakdown');
    $response->assertSee('Alpha One');
    $response->assertSee('Beta Flux');
});

test('non admin user cannot access analytics page', function () {
    User::factory()->admin()->create(['email_verified_at' => now()]);
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)->get(route('dashboard.analytics'))->assertForbidden();
});


