<?php

use App\Models\FriendInvitation;
use App\Models\InvestmentPackage;
use App\Models\Miner;
use App\Models\MinerPerformanceLog;
use App\Models\User;
use App\Models\UserInvestment;
use App\Notifications\ActivityFeedNotification;
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

    $networkLead = User::factory()->create([
        'email_verified_at' => now(),
        'name' => 'Network Lead',
        'email' => 'networklead@example.com',
    ]);

    User::factory()->create([
        'email_verified_at' => now(),
        'sponsor_user_id' => $networkLead->id,
        'name' => 'Network Downline',
        'email' => 'networkdownline@example.com',
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

    $buyer->notify(new ActivityFeedNotification([
        'event_key' => 'profile_power_reward_cap',
        'reward_cap_tier' => 'growth',
        'category' => 'milestone',
        'status' => 'success',
        'subject' => 'Growth 500 full reward cap unlocked',
        'message' => 'You unlocked the full 6.00% profile power reward cap for Growth 500.',
    ]));

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
    $response->assertSee('Network tree snapshot');
    $response->assertSee('Click any node for branch details');
    $response->assertSee('Network Lead');
    $response->assertSee('Package performance');
    $response->assertSee('Miner performance breakdown');
    $response->assertSee('Profile power reward analytics');
    $response->assertSee('Unlocked 6% cap');
    $response->assertSee('Selected miner daily performance');
    $response->assertSee('Alpha One');
    $response->assertSee('Beta Flux');
});

test('non admin user cannot access analytics page', function () {
    User::factory()->admin()->create(['email_verified_at' => now()]);
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)->get(route('dashboard.analytics'))->assertForbidden();
});

test('analytics tree can focus on one investor branch', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $focusUser = User::factory()->create([
        'email_verified_at' => now(),
        'name' => 'Focus Root',
        'email' => 'focusroot@example.com',
    ]);

    $otherRoot = User::factory()->create([
        'email_verified_at' => now(),
        'name' => 'Other Root',
        'email' => 'otherroot@example.com',
    ]);

    User::factory()->create([
        'email_verified_at' => now(),
        'sponsor_user_id' => $focusUser->id,
        'name' => 'Focus Child',
        'email' => 'focuschild@example.com',
    ]);

    User::factory()->create([
        'email_verified_at' => now(),
        'sponsor_user_id' => $otherRoot->id,
        'name' => 'Other Child',
        'email' => 'otherchild@example.com',
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.analytics', [
        'tree_focus' => $focusUser->id,
        'tree_depth' => 2,
    ]));

    $response->assertOk();
    $response->assertSee('Focused on');
    $response->assertSee('Focus Root');
    $response->assertSee('The tree now shows only this visible branch.');
    $response->assertSee('Depth 2');
});

test('analytics can export the focused tree branch', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $focusUser = User::factory()->create([
        'email_verified_at' => now(),
        'name' => 'Analytics Export Root',
        'email' => 'analyticsexportroot@example.com',
    ]);

    User::factory()->create([
        'email_verified_at' => now(),
        'sponsor_user_id' => $focusUser->id,
        'name' => 'Analytics Export Child',
        'email' => 'analyticsexportchild@example.com',
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.analytics.tree-export', [
        'tree_focus' => $focusUser->id,
        'tree_depth' => 2,
    ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    $csv = $response->streamedContent();
    expect($csv)->toContain('Focused branch');
    expect($csv)->toContain('analyticsexportroot@example.com');
    expect($csv)->toContain('Analytics Export Child');
});

test('analytics can open a printable focused branch summary', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $focusUser = User::factory()->create([
        'email_verified_at' => now(),
        'name' => 'Printable Analytics Root',
        'email' => 'printableanalyticsroot@example.com',
    ]);

    User::factory()->create([
        'email_verified_at' => now(),
        'sponsor_user_id' => $focusUser->id,
        'name' => 'Printable Analytics Child',
        'email' => 'printableanalyticschild@example.com',
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.analytics.tree-print', [
        'tree_focus' => $focusUser->id,
        'tree_depth' => 2,
    ]));

    $response->assertOk();
    $response->assertSee('Branch Summary');
    $response->assertSee('Analytics Branch View');
    $response->assertSee('printableanalyticsroot@example.com');
    $response->assertSee('Printable Analytics Child');
});
