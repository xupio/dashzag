<?php

use App\Models\User;
use App\Support\ActiveSession;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    expect($user->fresh()->active_session_token)->not->toBeNull();
    expect(session(ActiveSession::SESSION_KEY))->toBe($user->fresh()->active_session_token);
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can authenticate with uppercase email input', function () {
    $user = User::factory()->create([
        'email' => 'login-user@example.com',
    ]);

    $response = $this->post('/login', [
        'email' => 'Login-User@Example.COM',
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    expect($user->fresh()->active_session_token)->not->toBeNull();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('stale sessions are logged out after a newer login token exists', function () {
    $user = User::factory()->create([
        'active_session_token' => 'newer-session-token',
    ]);

    $response = $this->actingAs($user)
        ->withSession([ActiveSession::SESSION_KEY => 'older-session-token'])
        ->get(route('dashboard'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

