<?php

use App\Models\Earning;
use App\Models\InvestmentPackage;
use App\Models\Miner;
use App\Models\User;
use App\Models\UserInvestment;
use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('verified user can view daily miner report page', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard.miner-report'));

    $response->assertOk();
    $response->assertSee('Daily Miner Report');
    $response->assertSee('Export CSV');
    $response->assertSee('Print Report');
    $response->assertSee('How daily share earnings are produced');
    $response->assertSee('Recent daily logs');
});

test('daily miner report shows user stake and payout details for selected miner', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'shareholder',
    ]);

    $miner = Miner::where('slug', 'alpha-one')->firstOrFail();
    $package = InvestmentPackage::where('slug', 'starter-100')->firstOrFail();

    $investment = UserInvestment::create([
        'user_id' => $user->id,
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

    Earning::create([
        'user_id' => $user->id,
        'investment_id' => $investment->id,
        'earned_on' => now()->toDateString(),
        'amount' => 9.75,
        'source' => 'mining_daily_share',
        'status' => 'available',
        'notes' => 'Daily miner report payout row.',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard.miner-report', ['miner' => 'alpha-one']));

    $response->assertOk();
    $response->assertSee('Your stake in this miner');
    $response->assertSee('Latest daily payout');
    $response->assertSee('9.75');
    $response->assertSee('Alpha One performance trend');
});

test('user can export daily miner report as csv', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'shareholder',
    ]);

    $miner = Miner::where('slug', 'alpha-one')->firstOrFail();
    $package = InvestmentPackage::where('slug', 'starter-100')->firstOrFail();

    $investment = UserInvestment::create([
        'user_id' => $user->id,
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

    Earning::create([
        'user_id' => $user->id,
        'investment_id' => $investment->id,
        'earned_on' => now()->toDateString(),
        'amount' => 11.25,
        'source' => 'mining_daily_share',
        'status' => 'available',
        'notes' => 'Export payout row.',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard.miner-report.export', ['miner' => 'alpha-one']));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($response->streamedContent())->toContain('Section,Label,Value');
    expect($response->streamedContent())->toContain('Summary,Miner,"Alpha One"');
    expect($response->streamedContent())->toContain('Your payout');
});

test('user can open printable daily miner report view', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard.miner-report.print', ['miner' => 'alpha-one']));

    $response->assertOk();
    $response->assertSee('Alpha One Daily Miner Report');
    $response->assertSee('Print Report');
    $response->assertSee('Recent daily logs');
});
