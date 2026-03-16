<?php

use App\Models\HallOfFameSnapshot;
use App\Models\User;
use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('verified user can view hall of fame page', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard.hall-of-fame'));

    $response->assertOk();
    $response->assertSee('Hall of Fame');
    $response->assertSee('All-time leaders');
    $response->assertSee('Weekly winners');
    $response->assertSee('Monthly champions');
});

test('hall of fame page shows saved winner history', function () {
    $user = User::factory()->create([
        'name' => 'History Winner',
        'email_verified_at' => now(),
    ]);

    MiningPlatform::captureCompetitionSnapshot('weekly');
    MiningPlatform::captureCompetitionSnapshot('monthly');

    $response = $this->actingAs($user)->get(route('dashboard.hall-of-fame'));

    $response->assertOk();
    $response->assertSee('Saved weekly winners');
    $response->assertSee('Saved monthly champions');
    $response->assertSee('History Winner');

    expect(HallOfFameSnapshot::query()->where('category', 'weekly')->exists())->toBeTrue();
    expect(HallOfFameSnapshot::query()->where('category', 'monthly')->exists())->toBeTrue();
});
