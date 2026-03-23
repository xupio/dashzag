<?php

use App\Models\MockManagerScenario;
use App\Models\User;
use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('admin can view the mock manager page', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.mock-manager'));

    $response->assertOk();
    $response->assertSee('Mock Manager');
    $response->assertSee('Final projected profit');
    $response->assertSee('Scenario inputs');
});

test('admin can calculate a mock profit scenario', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $miner = \App\Models\Miner::query()->where('slug', 'alpha-one')->firstOrFail();
    $package = $miner->packages()->where('slug', 'starter-100')->firstOrFail();

    $response = $this->actingAs($admin)->post(route('dashboard.mock-manager.calculate'), [
        'miner_slug' => $miner->slug,
        'package_id' => $package->id,
        'monthly_hashrate_th' => 500,
        'monthly_revenue_usd' => 45000,
        'monthly_electricity_cost_usd' => 9000,
        'monthly_maintenance_cost_usd' => 3000,
        'active_shares' => 1000,
        'verified_invites' => 20,
        'registered_referrals' => 5,
        'level_1_basic_subscribers' => 2,
        'level_1_growth_subscribers' => 0,
        'level_1_scale_subscribers' => 0,
        'level_2_basic_subscribers' => 1,
        'level_2_growth_subscribers' => 0,
        'level_2_scale_subscribers' => 0,
        'level_3_basic_subscribers' => 0,
        'level_3_growth_subscribers' => 0,
        'level_3_scale_subscribers' => 0,
        'level_4_basic_subscribers' => 0,
        'level_4_growth_subscribers' => 0,
        'level_4_scale_subscribers' => 0,
        'level_5_basic_subscribers' => 0,
        'level_5_growth_subscribers' => 0,
        'level_5_scale_subscribers' => 0,
    ]);

    $response->assertOk();
    $response->assertSee('Connector Rank');
    $response->assertSee('Level 5 team rewards');
    $response->assertSee('$153.56', false);
    $response->assertSee('$33.00', false);
    $response->assertSee('$186.56', false);
});

test('non admin cannot view the mock manager page', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard.mock-manager'))
        ->assertForbidden();
});

test('admin can save and reopen a mock manager scenario', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $miner = \App\Models\Miner::query()->where('slug', 'alpha-one')->firstOrFail();
    $package = $miner->packages()->where('slug', 'growth-500')->firstOrFail();

    $saveResponse = $this->actingAs($admin)->post(route('dashboard.mock-manager.save'), [
        'scenario_name' => 'Growth medium team',
        'miner_slug' => $miner->slug,
        'package_id' => $package->id,
        'monthly_hashrate_th' => 620,
        'monthly_revenue_usd' => 50000,
        'monthly_electricity_cost_usd' => 10000,
        'monthly_maintenance_cost_usd' => 3500,
        'active_shares' => 1100,
        'verified_invites' => 25,
        'registered_referrals' => 7,
        'level_1_basic_subscribers' => 2,
        'level_1_growth_subscribers' => 1,
        'level_1_scale_subscribers' => 0,
        'level_2_basic_subscribers' => 1,
        'level_2_growth_subscribers' => 1,
        'level_2_scale_subscribers' => 0,
        'level_3_basic_subscribers' => 1,
        'level_3_growth_subscribers' => 0,
        'level_3_scale_subscribers' => 0,
        'level_4_basic_subscribers' => 0,
        'level_4_growth_subscribers' => 0,
        'level_4_scale_subscribers' => 0,
        'level_5_basic_subscribers' => 0,
        'level_5_growth_subscribers' => 0,
        'level_5_scale_subscribers' => 0,
    ]);

    $scenario = MockManagerScenario::query()->where('name', 'Growth medium team')->firstOrFail();

    $saveResponse->assertRedirect(route('dashboard.mock-manager', ['miner' => $miner->slug, 'scenario' => $scenario->id]));

    $response = $this->actingAs($admin)->get(route('dashboard.mock-manager', [
        'miner' => $miner->slug,
        'scenario' => $scenario->id,
    ]));

    $response->assertOk();
    $response->assertSee('Growth medium team');
    $response->assertSee((string) $package->id, false);
    $response->assertSee('Scenario "Growth medium team" was saved.');
});

test('admin can delete a saved mock manager scenario', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $miner = \App\Models\Miner::query()->where('slug', 'alpha-one')->firstOrFail();
    $package = $miner->packages()->where('slug', 'starter-100')->firstOrFail();

    $scenario = MockManagerScenario::query()->create([
        'user_id' => $admin->id,
        'miner_id' => $miner->id,
        'package_id' => $package->id,
        'name' => 'Delete me',
        'inputs' => [
            'package_id' => $package->id,
            'monthly_hashrate_th' => 500,
            'monthly_revenue_usd' => 45000,
            'monthly_electricity_cost_usd' => 9000,
            'monthly_maintenance_cost_usd' => 3000,
            'active_shares' => 1000,
            'verified_invites' => 20,
            'registered_referrals' => 5,
            'level_1_basic_subscribers' => 2,
            'level_1_growth_subscribers' => 0,
            'level_1_scale_subscribers' => 0,
            'level_2_basic_subscribers' => 1,
            'level_2_growth_subscribers' => 0,
            'level_2_scale_subscribers' => 0,
            'level_3_basic_subscribers' => 0,
            'level_3_growth_subscribers' => 0,
            'level_3_scale_subscribers' => 0,
            'level_4_basic_subscribers' => 0,
            'level_4_growth_subscribers' => 0,
            'level_4_scale_subscribers' => 0,
            'level_5_basic_subscribers' => 0,
            'level_5_growth_subscribers' => 0,
            'level_5_scale_subscribers' => 0,
        ],
    ]);

    $response = $this->actingAs($admin)->post(route('dashboard.mock-manager.delete', $scenario));

    $response->assertRedirect(route('dashboard.mock-manager', ['miner' => $miner->slug]));
    $this->assertDatabaseMissing('mock_manager_scenarios', ['id' => $scenario->id]);
});
