<?php

use App\Models\User;
use App\Notifications\ActivityFeedNotification;
use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('verified user can view profile power on dashboard profile page', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard.profile'));

    $response->assertOk();
    $response->assertSee('Profile power');
    $response->assertSee('Power components');
    $response->assertSee('Next rank target');
    $response->assertSee('Achievement badges');
    $response->assertSee('Milestone unlocks');
    $response->assertSee('How to gain power faster');
    $response->assertSee('Rank perks');
    $response->assertSee('Power leaderboard');
    $response->assertSee('Recent celebrations');
    $response->assertSee('Champion wins');
    $response->assertSee('Weekly momentum');
    $response->assertSee('Monthly champion push');
    $response->assertSee('Recent weekly history');
    $response->assertSee('Investment reward boost');
    $response->assertSee('Maximum cap');
    $response->assertSee('To reach full cap');
});

test('profile page shows reward cap unlock celebrations', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $user->notify(new ActivityFeedNotification([
        'event_key' => 'profile_power_reward_cap',
        'category' => 'milestone',
        'status' => 'success',
        'subject' => 'Growth 500 full reward cap unlocked',
        'message' => 'You unlocked the full 6.00% profile power reward cap for Growth 500.',
        'context_value' => '6.00% monthly boost',
        'rank_icon' => 'badge-percent',
    ]));

    $response = $this->actingAs($user)->get(route('dashboard.profile'));

    $response->assertOk();
    $response->assertSee('Growth 500 full reward cap unlocked');
    $response->assertSee('6.00% monthly boost');
});
