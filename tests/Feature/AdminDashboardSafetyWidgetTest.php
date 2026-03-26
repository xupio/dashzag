<?php

use App\Models\User;
use App\Support\AdminTwoFactor;
use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('admin dashboard shows the safety center widget', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
        'admin_two_factor_secret' => AdminTwoFactor::encryptSecret(AdminTwoFactor::generateSecret()),
        'admin_two_factor_confirmed_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('Admin safety center');
    $response->assertSee('Production security snapshot');
    $response->assertSee('Create a fresh DB backup');
    $response->assertSee('Enabled');
});

test('regular investor dashboard does not show the admin safety center widget', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertDontSee('Admin safety center');
    $response->assertDontSee('Production security snapshot');
});
