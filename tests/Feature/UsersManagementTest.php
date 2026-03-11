<?php

use App\Models\User;
use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('admin can view users page', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.users'));

    $response->assertOk();
    $response->assertSee('Users');
});

test('admin can update another user role', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'user',
    ]);

    $response = $this->actingAs($admin)->post(route('dashboard.users.role', $user), [
        'role' => 'admin',
    ]);

    $response->assertRedirect(route('dashboard.users'));

    $user->refresh();

    expect($user->role)->toBe('admin');
});

test('non admin user cannot access users page', function () {
    User::factory()->admin()->create(['email_verified_at' => now()]);
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)->get(route('dashboard.users'))->assertForbidden();
});
