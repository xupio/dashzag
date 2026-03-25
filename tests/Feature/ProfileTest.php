<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile photo can be updated', function () {
    $user = User::factory()->create();
    Storage::fake('public');

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'profile_photo' => UploadedFile::fake()->create('avatar.png', 128, 'image/png'),
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    expect($user->profile_photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($user->profile_photo_path);
});

test('payout destinations can be saved from account settings', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'btc_wallet_address' => 'bc1qzagchaintestwallet',
            'usdt_wallet_address' => 'TXYZzagchaintestwallet',
            'bank_transfer_details' => 'Beneficiary: ZagChain Treasury | IBAN: AE001234567890',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    expect($user->btc_wallet_address)->toBe('bc1qzagchaintestwallet');
    expect($user->usdt_wallet_address)->toBe('TXYZzagchaintestwallet');
    expect($user->bank_transfer_details)->toBe('Beneficiary: ZagChain Treasury | IBAN: AE001234567890');
});

test('users keep email hidden by default', function () {
    $user = User::factory()->create();

    expect($user->is_email_visible)->toBeFalse();
    expect($user->displayEmail())->toBe('Email hidden');
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});

