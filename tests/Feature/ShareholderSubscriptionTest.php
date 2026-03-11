<?php

use App\Models\User;
use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('verified user can subscribe to a mining package and become a shareholder', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'user',
    ]);

    $response = $this->actingAs($user)->post(route('general.sell-products.subscribe'), [
        'package' => 'growth-500',
    ]);

    $response->assertRedirect(route('general.sell-products', ['miner' => 'alpha-one']));

    $user->refresh();
    $user->load(['shareholder', 'investments.package', 'userLevel']);

    expect($user->account_type)->toBe('shareholder');
    expect($user->shareholder)->not->toBeNull();
    expect($user->shareholder->package_name)->toBe('Growth 500');
    expect((int) $user->shareholder->units_limit)->toBe(5);
    expect($user->shareholder->status)->toBe('active');
    expect($user->investments)->toHaveCount(1);
    expect($user->investments->first()->package->slug)->toBe('growth-500');
    expect((int) $user->investments->first()->shares_owned)->toBe(5);
    expect($user->userLevel)->not->toBeNull();
});

