<?php

use App\Models\User;
use App\Support\AdminTwoFactor;
use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('repeated failed login attempts notify admins', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $targetUser = User::factory()->create([
        'email_verified_at' => now(),
        'email' => 'alert-target@example.com',
    ]);

    foreach (range(1, 3) as $attempt) {
        $this->post('/login', [
            'email' => $targetUser->email,
            'password' => 'wrong-password',
        ]);
    }

    $admin->refresh();

    expect($admin->notifications)->not->toBeEmpty();
    expect($admin->notifications->pluck('data.subject'))->toContain('Repeated failed login attempts detected');
});

test('repeated failed admin two factor attempts notify admins', function () {
    $secret = AdminTwoFactor::generateSecret();
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
        'email' => 'admin-alert@example.com',
        'admin_two_factor_secret' => AdminTwoFactor::encryptSecret($secret),
        'admin_two_factor_confirmed_at' => now(),
    ]);

    $this->post('/login', [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.two-factor.challenge'));

    foreach (range(1, 3) as $attempt) {
        $this->post(route('admin.two-factor.verify'), [
            'code' => '000000',
        ]);
    }

    $admin->refresh();

    expect($admin->notifications)->not->toBeEmpty();
    expect($admin->notifications->pluck('data.subject'))->toContain('Repeated failed admin 2FA attempts');
});
