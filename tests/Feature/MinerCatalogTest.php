<?php

use App\Models\Miner;
use App\Models\User;
use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('admin can view miner catalog page', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard.miners'))
        ->assertOk()
        ->assertSee('Miner Catalog')
        ->assertSee('Alpha One')
        ->assertSee('Beta Flux');
});

test('admin can create a miner with starter packages', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($admin)->post(route('dashboard.miners.store'), [
        'name' => 'Gamma Forge',
        'slug' => 'gamma-forge',
        'description' => 'New mining cluster.',
        'total_shares' => 1800,
        'share_price' => 120,
        'daily_output_usd' => 2400,
        'monthly_output_usd' => 72000,
        'base_monthly_return_rate' => 0.0950,
        'status' => 'active',
    ]);

    $response->assertRedirect(route('dashboard.miners'));

    $miner = Miner::where('slug', 'gamma-forge')->with('packages')->first();

    expect($miner)->not->toBeNull();
    expect($miner->packages)->toHaveCount(3);
    expect($miner->packages->pluck('slug')->all())->toBe([
        'gamma-forge-launch',
        'gamma-forge-growth',
        'gamma-forge-scale',
    ]);
});

test('non admin user cannot create miners', function () {
    User::factory()->admin()->create(['email_verified_at' => now()]);
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->post(route('dashboard.miners.store'), [
            'name' => 'Blocked Miner',
            'total_shares' => 1000,
            'share_price' => 100,
            'daily_output_usd' => 1000,
            'monthly_output_usd' => 30000,
            'base_monthly_return_rate' => 0.0800,
            'status' => 'active',
        ])
        ->assertForbidden();
});
