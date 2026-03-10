<?php

use App\Models\User;

test('verified user can subscribe to a package and become a shareholder', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'user',
    ]);

    $response = $this->actingAs($user)->post(route('general.sell-products.subscribe'), [
        'package' => 'business',
    ]);

    $response->assertRedirect(route('general.sell-products'));

    $user->refresh();

    expect($user->account_type)->toBe('shareholder');
    expect($user->shareholder)->not->toBeNull();
    expect($user->shareholder->package_name)->toBe('Business');
    expect((int) $user->shareholder->units_limit)->toBe(75);
    expect($user->shareholder->status)->toBe('active');
});
