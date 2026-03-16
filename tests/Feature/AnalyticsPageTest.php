<?php

use App\Models\FriendInvitation;
use App\Models\InvestmentPackage;
use App\Models\Miner;
use App\Models\MinerPerformanceLog;
use App\Models\User;
use App\Models\UserInvestment;
use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('admin can view analytics page', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.analytics'));

    $response->assertOk();
    $response->assertSee('Admin Analytics');
});

test('analytics page shows investment referral miner and daily performance metrics', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $alphaMiner = Miner::where('slug', 'alpha-one')->firstOrFail();
    $betaMiner = Miner::where('slug', 'beta-flux')->firstOrFail();
    $alphaPackage = InvestmentPackage::where('slug', 'starter-100')->firstOrFail();
    $betaPackage = InvestmentPackage::where('slug', 'momentum-300')->firstOrFail();

    $investor = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'shareholder',
    ]);

    UserInvestment::create([
        'user_id' => $investor->id,
        'miner_id' => $alphaMiner->id,
        'package_id' => $alphaPackage->id,
        'shareholder_id' => null,
        'amount' => 100,
        'shares_owned' => 1,
        'monthly_return_rate' => $alphaPackage->monthly_return_rate,
        'level_bonus_rate' => 0,
        'team_bonus_rate' => 0,
        'status' => 'active',
        'subscribed_at' => now(),
    ]);

    $referrer = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    FriendInvitation::create([
        'user_id' => $referrer->id,
        'name' => 'Analytics Buyer',
        'email' => 'analyticsbuyer@example.com',
        'verified_at' => now(),
        'registered_at' => now(),
    ]);

    $buyer = User::factory()->create([
        'email' => 'analyticsbuyer@example.com',
        'email_verified_at' => now(),
        'account_type' => 'shareholder',
    ]);

    UserInvestment::create([
        'user_id' => $buyer->id,
        'miner_id' => $betaMiner->id,
        'package_id' => $betaPackage->id,
        'shareholder_id' => null,
        'amount' => 300,
        'shares_owned' => 3,
        'monthly_return_rate' => $betaPackage->monthly_return_rate,
        'level_bonus_rate' => 0,
        'team_bonus_rate' => 0,
        'status' => 'active',
        'subscribed_at' => now(),
    ]);

    MinerPerformanceLog::updateOrCreate([
        'miner_id' => $alphaMiner->id,
        'logged_on' => '2026-03-15',
    ], [
        'revenue_usd' => 1500,
        'electricity_cost_usd' => 250,
        'maintenance_cost_usd' => 90,
        'net_profit_usd' => 1160,
        'hashrate_th' => 505,
        'uptime_percentage' => 98.8,
        'active_shares' => 1,
        'revenue_per_share_usd' => 1160,
        'source' => 'automatic',
        'notes' => 'Analytics test snapshot',
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.analytics', ['miner' => 'alpha-one']));

    $response->assertOk();
    $response->assertSee('Top investors');
    $response->assertSee('Top referrers');
    $response->assertSee('Package performance');
    $response->assertSee('Miner performance breakdown');
    $response->assertSee('Selected miner daily performance');
    $response->assertSee('Alpha One');
    $response->assertSee('Beta Flux');
});

test('non admin user cannot access analytics page', function () {
    User::factory()->admin()->create(['email_verified_at' => now()]);
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)->get(route('dashboard.analytics'))->assertForbidden();
});
