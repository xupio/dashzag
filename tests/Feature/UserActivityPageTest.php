<?php

use App\Models\FriendInvitation;
use App\Models\User;
use App\Models\UserLoginEvent;
use App\Models\UserPageActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('successful login records a user login event', function () {
    $user = User::factory()->create([
        'email' => 'investor@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $this->post(route('login'), [
        'email' => 'investor@example.com',
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertDatabaseHas('user_login_events', [
        'user_id' => $user->id,
    ]);
});

test('authenticated user page visit is recorded', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('dashboard.activity.page-visit'), [
            'path' => '/dashboard/investments',
            'route_name' => 'dashboard.investments',
            'page_title' => 'Investments',
            'seconds_spent' => 42,
        ])->assertNoContent();

    $this->assertDatabaseHas('user_page_activity_logs', [
        'user_id' => $user->id,
        'path' => '/dashboard/investments',
        'route_name' => 'dashboard.investments',
        'seconds_spent' => 42,
    ]);
});

test('admin can view user activity page with login and invitation metrics', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $user = User::factory()->create([
        'name' => 'Tracked Investor',
        'email' => 'tracked@example.com',
        'email_verified_at' => now(),
    ]);

    FriendInvitation::create([
        'user_id' => $user->id,
        'name' => 'Referral One',
        'email' => 'referral-one@example.com',
    ]);

    UserLoginEvent::create([
        'user_id' => $user->id,
        'login_at' => now()->subDay(),
    ]);

    UserPageActivityLog::create([
        'user_id' => $user->id,
        'path' => '/dashboard/investments',
        'route_name' => 'dashboard.investments',
        'page_title' => 'Investments',
        'seconds_spent' => 180,
        'started_at' => now()->subMinutes(3),
        'ended_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard.user-activity', ['user_id' => $user->id]))
        ->assertOk()
        ->assertSee('Tracked Investor')
        ->assertSee('tracked@example.com')
        ->assertSee('/dashboard/investments')
        ->assertSee('User Activity');
});
