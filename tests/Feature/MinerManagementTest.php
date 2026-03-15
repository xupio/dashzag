<?php

use App\Models\Earning;
use App\Models\Miner;
use App\Models\User;
use App\Models\UserInvestment;
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
        'base_monthly_return_rate' => 9.50,
        'status' => 'maintenance',
    ]);

    $response->assertRedirect(route('dashboard.miner').'?miner=alpha-one');

    $miner = Miner::where('slug', 'alpha-one')->firstOrFail();

    expect($miner->name)->toBe('Alpha One Prime');
    expect($miner->status)->toBe('maintenance');
    expect((float) $miner->share_price)->toBe(125.5);
    expect((float) $miner->base_monthly_return_rate)->toBe(0.095);
    expect((float) $miner->packages()->where('slug', 'starter-100')->firstOrFail()->monthly_return_rate)->toBe(0.095);
    expect((float) $miner->packages()->where('slug', 'growth-500')->firstOrFail()->monthly_return_rate)->toBe(0.1);
});

test('admin can update a secondary miner performance log with financial fields', function () {
    $user = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->post(route('dashboard.miner.logs.store'), [
        'miner_slug' => 'beta-flux',
        'logged_on' => '2026-03-11',
        'revenue_usd' => 1999.99,
        'electricity_cost_usd' => 250.25,
        'maintenance_cost_usd' => 80.10,
        'hashrate_th' => 512.45,
        'uptime_percentage' => 99.10,
        'notes' => 'Strong mining day.',
    ]);

    $response->assertRedirect(route('dashboard.miner').'?miner=beta-flux');

    $miner = Miner::where('slug', 'beta-flux')->firstOrFail();
    $log = $miner->performanceLogs()->whereDate('logged_on', '2026-03-11')->first();

    expect($log)->not->toBeNull();
    expect((float) $log->revenue_usd)->toBe(1999.99);
    expect((float) $log->electricity_cost_usd)->toBe(250.25);
    expect((float) $log->maintenance_cost_usd)->toBe(80.10);
    expect((float) $log->net_profit_usd)->toBe(round(1999.99 - 250.25 - 80.10, 2));
    expect($log->source)->toBe('manual');
    expect($log->notes)->toBe('Strong mining day.');
});

test('automatic snapshot generates daily per share earnings for active investors', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $investor = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'shareholder',
    ]);

    $miner = Miner::where('slug', 'alpha-one')->firstOrFail();
    $package = $miner->packages()->where('slug', 'starter-100')->firstOrFail();

    $investment = UserInvestment::create([
        'user_id' => $investor->id,
        'miner_id' => $miner->id,
        'package_id' => $package->id,
        'shareholder_id' => null,
        'amount' => 100,
        'shares_owned' => 1,
        'monthly_return_rate' => $package->monthly_return_rate,
        'level_bonus_rate' => 0,
        'team_bonus_rate' => 0,
        'status' => 'active',
        'subscribed_at' => now(),
    ]);

    $response = $this->actingAs($admin)->post(route('dashboard.miner.logs.generate'), [
        'miner_slug' => 'alpha-one',
        'logged_on' => '2026-03-15',
    ]);

    $response->assertRedirect(route('dashboard.miner').'?miner=alpha-one');

    $log = $miner->performanceLogs()->whereDate('logged_on', '2026-03-15')->first();
    $earning = Earning::query()
        ->where('investment_id', $investment->id)
        ->whereDate('earned_on', '2026-03-15')
        ->where('source', 'mining_daily_share')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->source)->toBe('automatic');
    expect((float) $log->net_profit_usd)->toBeGreaterThan(0);
    expect((float) $log->revenue_per_share_usd)->toBeGreaterThan(0);
    expect($earning)->not->toBeNull();
    expect((float) $earning->amount)->toBe(round((float) $log->revenue_per_share_usd * (float) $investment->shares_owned, 2));
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
            'base_monthly_return_rate' => 8,
            'status' => 'active',
        ])
        ->assertForbidden();
});

test('default seeding does not overwrite admin miner changes', function () {
    $user = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)->post(route('dashboard.miner.update'), [
        'miner_slug' => 'alpha-one',
        'name' => 'Alpha One Prime',
        'description' => 'Updated miner profile.',
        'total_shares' => 1500,
        'share_price' => 125.50,
        'daily_output_usd' => 2100,
        'monthly_output_usd' => 63000,
        'base_monthly_return_rate' => 9.50,
        'status' => 'maintenance',
    ]);

    MiningPlatform::ensureDefaults();

    $miner = Miner::where('slug', 'alpha-one')->firstOrFail();

    expect($miner->name)->toBe('Alpha One Prime');
    expect($miner->status)->toBe('maintenance');
    expect((float) $miner->base_monthly_return_rate)->toBe(0.095);
});
