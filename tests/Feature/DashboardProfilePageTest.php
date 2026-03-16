<?php

use App\Models\User;
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
});
