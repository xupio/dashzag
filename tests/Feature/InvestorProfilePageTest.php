<?php

use App\Models\User;
use App\Notifications\ActivityFeedNotification;
use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('user can view investor power on investor profile page', function () {
    $viewer = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $investor = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($viewer)->get(route('dashboard.investors.show', $investor));

    $response->assertOk();
    $response->assertSee('Investor power');
    $response->assertSee('Strength breakdown');
    $response->assertSee('Next rank target');
    $response->assertSee('Achievement badges');
    $response->assertSee('Milestone unlocks');
    $response->assertSee('Champion wins');
    $response->assertSee('Reward cap status');
});

test('investor profile shows unlocked reward cap milestones', function () {
    $viewer = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $investor = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $investor->notify(new ActivityFeedNotification([
        'event_key' => 'profile_power_reward_cap',
        'reward_cap_tier' => 'growth',
        'category' => 'milestone',
        'status' => 'success',
        'subject' => 'Growth 500 full reward cap unlocked',
        'message' => 'You unlocked the full 6.00% profile power reward cap for Growth 500.',
    ]));

    $response = $this->actingAs($viewer)->get(route('dashboard.investors.show', $investor));

    $response->assertOk();
    $response->assertSee('Growth 500 full reward cap unlocked');
});
