<?php

use App\Models\User;
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
});
