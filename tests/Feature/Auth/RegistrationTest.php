<?php

use App\Models\FriendInvitation;
use App\Models\User;
use App\Notifications\ActivityFeedNotification;
use App\Notifications\InvitationAwareVerifyEmail;
use Illuminate\Support\Facades\Notification;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

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
    Notification::assertSentTo($admin, ActivityFeedNotification::class, function ($notification, array $channels) use ($user) {
        $payload = $notification->toArray($user);
        $mailMessage = $notification->toMail($user);

        return in_array('database', $channels, true)
            && in_array('mail', $channels, true)
            && ($payload['subject'] ?? null) === 'New user registration received'
            && ($payload['context_value'] ?? null) === 'test@example.com'
            && ($payload['related_user_id'] ?? null) === $user->id
            && ($payload['action_text'] ?? null) === 'Open Admin Users'
            && str_contains((string) ($payload['action_url'] ?? ''), urlencode('test@example.com'))
            && $mailMessage->actionText === 'Open Admin Users';
    });
    $response->assertRedirect(route('verification.notice', absolute: false));
});

test('new users can register with uppercase email and it is normalized', function () {
    Notification::fake();

    $response = $this->post('/register', [
        'name' => 'Uppercase User',
        'email' => 'Upper.User@Example.COM',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'upper.user@example.com')->first();

    $this->assertAuthenticated();
    expect($user)->not->toBeNull();
    expect($user?->email)->toBe('upper.user@example.com');
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

test('invitation aware verification email uses zagchain invitation wording', function () {
    $inviter = User::factory()->create([
        'name' => 'Mohammad',
        'email_verified_at' => now(),
    ]);

    FriendInvitation::create([
        'user_id' => $inviter->id,
        'name' => 'Invited User',
        'email' => 'invited@example.com',
    ]);

    $user = User::factory()->unverified()->create([
        'email' => 'invited@example.com',
    ]);

    $notification = new InvitationAwareVerifyEmail(
        FriendInvitation::with('user:id,name')->where('email', $user->email)->get()
    );

    $mailMessage = $notification->toMail($user);
    $introLines = collect($mailMessage->introLines);

    expect($mailMessage->subject)->toBe('Verify Your ZagChain Email Address');
    expect($mailMessage->greeting)->toBe('Welcome to ZagChain');
    expect($introLines->join(' '))
        ->toContain('Mohammad to join us with ZagChain');
});

