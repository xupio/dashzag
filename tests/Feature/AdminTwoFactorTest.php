<?php

use App\Models\User;
use App\Support\AdminTwoFactor;
use Illuminate\Support\Facades\Crypt;

test('admin can generate and confirm two factor authentication from profile settings', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('profile.admin-two-factor.store'), [
            'current_password' => 'password',
        ])
        ->assertRedirect(route('profile.edit'));

    $admin->refresh();

    expect($admin->admin_two_factor_secret)->not->toBeNull();
    expect($admin->admin_two_factor_confirmed_at)->toBeNull();

    $code = AdminTwoFactor::currentCodeForUser($admin);

    $this->actingAs($admin)
        ->post(route('profile.admin-two-factor.confirm'), [
            'current_password' => 'password',
            'code' => $code,
        ])
        ->assertRedirect(route('profile.edit'));

    expect($admin->fresh()->hasAdminTwoFactorEnabled())->toBeTrue();
});

test('admin login requires a second factor challenge after password authentication', function () {
    $secret = AdminTwoFactor::generateSecret();
    $admin = User::factory()->admin()->create([
        'admin_two_factor_secret' => AdminTwoFactor::encryptSecret($secret),
        'admin_two_factor_confirmed_at' => now(),
    ]);

    $this->post('/login', [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.two-factor.challenge'));

    $this->assertGuest();

    $this->post(route('admin.two-factor.verify'), [
        'code' => AdminTwoFactor::currentCode($secret),
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($admin);
});

test('non admin users still log in without two factor challenge', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});
