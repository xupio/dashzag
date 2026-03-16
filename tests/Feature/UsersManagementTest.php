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


test('admin can filter users by role and search', function () {
    $admin = User::factory()->admin()->create([
        'name' => 'Main Admin',
        'email_verified_at' => now(),
    ]);

    User::factory()->create([
        'name' => 'Growth Shareholder',
        'email' => 'growth@example.com',
        'email_verified_at' => now(),
        'account_type' => 'shareholder',
        'role' => 'user',
    ]);

    User::factory()->admin()->create([
        'name' => 'Hidden Admin',
        'email' => 'hidden-admin@example.com',
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.users', [
        'role' => 'user',
        'search' => 'Growth',
    ]));

    $response->assertOk();
    $response->assertSee('Growth Shareholder');
    $response->assertSee('Verification');
    $response->assertDontSee('Hidden Admin');
});

test('admin can export filtered users as csv', function () {
    $admin = User::factory()->admin()->create([
        'name' => 'Main Admin',
        'email_verified_at' => now(),
    ]);

    User::factory()->create([
        'name' => 'Growth Shareholder',
        'email' => 'growth@example.com',
        'email_verified_at' => now(),
        'account_type' => 'shareholder',
        'role' => 'user',
    ]);

    User::factory()->admin()->create([
        'name' => 'Hidden Admin',
        'email' => 'hidden-admin@example.com',
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.users.export', [
        'role' => 'user',
        'account_type' => 'shareholder',
        'verification' => 'verified',
        'search' => 'Growth',
    ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('Filter');
    expect($csv)->toContain('Growth');
    expect($csv)->toContain('shareholder');
    expect($csv)->toContain('Name');
    expect($csv)->toContain('Growth Shareholder');
    expect($csv)->not->toContain('Hidden Admin');
});

