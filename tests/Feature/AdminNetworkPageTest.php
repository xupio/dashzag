<?php

use App\Models\User;
use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('admin can view the network admin page', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $sponsor = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $branchHead = User::factory()->create([
        'email_verified_at' => now(),
        'sponsor_user_id' => $sponsor->id,
        'name' => 'Branch Head',
        'email' => 'branch@example.com',
    ]);

    User::factory()->create([
        'email_verified_at' => now(),
        'sponsor_user_id' => $branchHead->id,
        'name' => 'Downline User',
        'email' => 'downline@example.com',
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.network-admin'));

    $response->assertOk();
    $response->assertSee('Network Admin');
    $response->assertSee('Visual sponsor tree');
    $response->assertSee('Branch Head');
    $response->assertSee('downline@example.com');
});

test('non admin cannot view the network admin page', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'user',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard.network-admin'))
        ->assertForbidden();
});

