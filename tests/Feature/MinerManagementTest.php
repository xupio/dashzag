<?php

use App\Models\Miner;
use App\Models\User;
use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('admin can update a selected miner details', function () {
    $user = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->post(route('dashboard.miner.update'), [
        'miner_slug' => 'alpha-one',
        'name' => 'Alpha One Prime',
        'description' => 'Updated miner profile.',
        'total_shares' => 1500,
        'share_price' => 125.50,
        'daily_output_usd' => 2100,
        'monthly_output_usd' => 63000,
        'base_monthly_return_rate' => 0.0950,
        'status' => 'maintenance',
    ]);

    $response->assertRedirect(route('dashboard.miner').'?miner=alpha-one');

    $miner = Miner::where('slug', 'alpha-one')->firstOrFail();

    expect($miner->name)->toBe('Alpha One Prime');
    expect($miner->status)->toBe('maintenance');
    expect((float) $miner->share_price)->toBe(125.5);
});

test('admin can update a secondary miner performance log', function () {
    $user = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->post(route('dashboard.miner.logs.store'), [
        'miner_slug' => 'beta-flux',
        'logged_on' => '2026-03-11',
        'revenue_usd' => 1999.99,
        'hashrate_th' => 512.45,
        'uptime_percentage' => 99.10,
        'notes' => 'Strong mining day.',
    ]);

    $response->assertRedirect(route('dashboard.miner').'?miner=beta-flux');

    $miner = Miner::where('slug', 'beta-flux')->firstOrFail();
    $log = $miner->performanceLogs()->whereDate('logged_on', '2026-03-11')->first();

    expect($log)->not->toBeNull();
    expect((float) $log->revenue_usd)->toBe(1999.99);
    expect($log->notes)->toBe('Strong mining day.');
});

test('non admin user cannot manage miner data', function () {
    User::factory()->admin()->create(['email_verified_at' => now()]);
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->post(route('dashboard.miner.update'), [
            'miner_slug' => 'alpha-one',
            'name' => 'Blocked Update',
            'description' => 'Should not be saved.',
            'total_shares' => 1000,
            'share_price' => 100,
            'daily_output_usd' => 1500,
            'monthly_output_usd' => 45000,
            'base_monthly_return_rate' => 0.0800,
            'status' => 'active',
        ])
        ->assertForbidden();
});

