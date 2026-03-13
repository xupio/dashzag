<?php

use App\Models\FriendInvitation;
use App\Models\User;
use App\Notifications\InvitationAwareVerifyEmail;
use Illuminate\Support\Facades\Notification;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    Notification::fake();

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'test@example.com')->first();
    $user->load(['shareholder', 'investments.package']);

    $this->assertAuthenticated();
    expect($user)->not->toBeNull();
    expect($user->hasVerifiedEmail())->toBeFalse();
    expect($user->account_type)->toBe('starter');
    expect($user->shareholder?->package_name)->toBe('Starter Free');
    expect($user->investments->first()?->package?->slug)->toBe('starter-free');
    Notification::assertSentTo($user, InvitationAwareVerifyEmail::class);
    $response->assertRedirect(route('verification.notice', absolute: false));
});

test('invited users still receive verification email on registration', function () {
    Notification::fake();

    $inviter = User::factory()->create();

    FriendInvitation::create([
        'user_id' => $inviter->id,
        'name' => 'Invited User',
        'email' => 'invited@example.com',
    ]);

    $response = $this->post('/register', [
        'name' => 'Invited User',
        'email' => 'invited@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'invited@example.com')->first();

    Notification::assertSentTo($user, InvitationAwareVerifyEmail::class);
    $response->assertRedirect(route('verification.notice', absolute: false));
});

