<?php

use App\Models\User;
use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('admin dashboard does not show the safety center widget anymore', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk();
    $response->assertDontSee('Admin safety center');
    $response->assertDontSee('Production security snapshot');
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
