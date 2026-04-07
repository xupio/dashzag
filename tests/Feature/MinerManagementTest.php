<?php

use App\Models\Earning;
use App\Models\Miner;
use App\Models\User;
use App\Models\UserInvestment;
use App\Support\MiningPlatform;
use Illuminate\Http\UploadedFile;

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

test('miner page shows the market signal summary for admins', function () {
    $user = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $miner = Miner::where('slug', 'alpha-one')->firstOrFail();

    MiningPlatform::savePerformanceLog($miner, [
        'logged_on' => '2026-04-06',
        'revenue_usd' => 1547,
        'electricity_cost_usd' => 278.46,
        'maintenance_cost_usd' => 92.82,
        'hashrate_th' => 509,
        'btc_price_usd' => 88400,
        'uptime_percentage' => 99.1,
    ], 'manual');

    $response = $this->actingAs($user)->get(route('dashboard.miner', ['miner' => 'alpha-one']));

    $response->assertOk();
    $response->assertSee('Market signal summary');
    $response->assertSee('Daily-share factor');
    $response->assertSee('Hashrate signal');
    $response->assertSee('BTC price signal');
    $response->assertSee('Revenue/share signal');
    $response->assertSee('Latest performance log');
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
        'btc_price_usd' => 87450.25,
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
    expect((float) $log->btc_price_usd)->toBe(87450.25);
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
    $expectedAmount = MiningPlatform::dailyShareRoundedAmount(
        MiningPlatform::investmentBaseDailyShareCap($investment),
        MiningPlatform::dailySharePerformanceFactorForLog($log),
        round((float) $log->revenue_per_share_usd * (float) $investment->shares_owned, 2)
    );

    expect((float) $earning->amount)->toBe($expectedAmount);
});

test('daily share amounts visibly change across stronger and weaker miner days while staying inside the package cap', function () {
    $investor = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'shareholder',
    ]);

    $miner = Miner::where('slug', 'alpha-one')->firstOrFail();
    $package = $miner->packages()->where('slug', 'scale-1000')->firstOrFail();
    $package->update(['monthly_return_rate' => 0.04]);

    $investment = UserInvestment::create([
        'user_id' => $investor->id,
        'miner_id' => $miner->id,
        'package_id' => $package->id,
        'shareholder_id' => null,
        'amount' => 1000,
        'shares_owned' => 10,
        'monthly_return_rate' => 0.04,
        'level_bonus_rate' => 0,
        'team_bonus_rate' => 0,
        'status' => 'active',
        'subscribed_at' => now(),
    ]);

    MiningPlatform::savePerformanceLog($miner, [
        'logged_on' => '2026-03-26',
        'revenue_usd' => 1260,
        'electricity_cost_usd' => 255,
        'maintenance_cost_usd' => 78,
        'hashrate_th' => 452,
        'btc_price_usd' => 83800,
        'uptime_percentage' => 96.4,
    ], 'manual');

    MiningPlatform::savePerformanceLog($miner, [
        'logged_on' => '2026-03-27',
        'revenue_usd' => 1330,
        'electricity_cost_usd' => 250,
        'maintenance_cost_usd' => 76,
        'hashrate_th' => 468,
        'btc_price_usd' => 85600,
        'uptime_percentage' => 97.3,
    ], 'manual');

    $weakerLog = MiningPlatform::savePerformanceLog($miner, [
        'logged_on' => '2026-03-28',
        'revenue_usd' => 1180,
        'electricity_cost_usd' => 260,
        'maintenance_cost_usd' => 80,
        'hashrate_th' => 440,
        'btc_price_usd' => 81200,
        'uptime_percentage' => 95.8,
    ], 'manual');

    $strongerLog = MiningPlatform::savePerformanceLog($miner, [
        'logged_on' => '2026-03-29',
        'revenue_usd' => 1420,
        'electricity_cost_usd' => 245,
        'maintenance_cost_usd' => 72,
        'hashrate_th' => 488,
        'btc_price_usd' => 90500,
        'uptime_percentage' => 98.7,
    ], 'manual');

    $weakerAmount = (float) Earning::query()
        ->where('investment_id', $investment->id)
        ->whereDate('earned_on', '2026-03-28')
        ->where('source', 'mining_daily_share')
        ->value('amount');

    $strongerAmount = (float) Earning::query()
        ->where('investment_id', $investment->id)
        ->whereDate('earned_on', '2026-03-29')
        ->where('source', 'mining_daily_share')
        ->value('amount');

    $dailyCap = MiningPlatform::investmentBaseDailyShareCap($investment);

    expect($strongerAmount)->toBeGreaterThan($weakerAmount);
    expect($strongerAmount)->toBeLessThanOrEqual($dailyCap);
    expect($weakerAmount)->toBeLessThanOrEqual($dailyCap);
    expect($weakerAmount)->toBeLessThan($dailyCap);
    expect($weakerLog->revenue_per_share_usd)->not->toBe($strongerLog->revenue_per_share_usd);
});

test('admin can import miner performance logs from csv', function () {
    $user = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $csv = implode("\n", [
        'logged_on,revenue_usd,electricity_cost_usd,maintenance_cost_usd,hashrate_th,btc_price_usd,uptime_percentage,notes',
        '2026-03-10,1550.75,220.10,70.25,498.40,86250.00,98.50,Imported row one',
        '2026-03-11,1625.25,230.00,72.50,503.10,87125.00,99.10,Imported row two',
    ]);

    $response = $this->actingAs($user)->post(route('dashboard.miner.logs.import'), [
        'miner_slug' => 'alpha-one',
        'csv_file' => UploadedFile::fake()->createWithContent('alpha-logs.csv', $csv),
    ]);

    $response->assertRedirect(route('dashboard.miner').'?miner=alpha-one');

    $miner = Miner::where('slug', 'alpha-one')->firstOrFail();

    expect((float) $miner->performanceLogs()->whereDate('logged_on', '2026-03-10')->firstOrFail()->revenue_usd)->toBe(1550.75);
    expect((float) $miner->performanceLogs()->whereDate('logged_on', '2026-03-11')->firstOrFail()->net_profit_usd)->toBe(round(1625.25 - 230.00 - 72.50, 2));
    expect((float) $miner->performanceLogs()->whereDate('logged_on', '2026-03-11')->firstOrFail()->btc_price_usd)->toBe(87125.00);
});

test('admin can download miner performance csv template', function () {
    $user = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard.miner.logs.template', ['miner' => 'alpha-one']));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($response->streamedContent())->toContain('logged_on,revenue_usd,electricity_cost_usd,maintenance_cost_usd,hashrate_th,btc_price_usd,uptime_percentage,notes');
});

test('admin can copy previous miner performance log forward', function () {
    $user = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $miner = Miner::where('slug', 'alpha-one')->firstOrFail();

    MiningPlatform::savePerformanceLog($miner, [
        'logged_on' => '2026-03-19',
        'revenue_usd' => 1400.50,
        'electricity_cost_usd' => 220.20,
        'maintenance_cost_usd' => 60.30,
        'hashrate_th' => 490.10,
        'btc_price_usd' => 88310.55,
        'uptime_percentage' => 98.25,
        'notes' => 'Original source row.',
    ], 'manual');

    $response = $this->actingAs($user)->post(route('dashboard.miner.logs.copy-yesterday'), [
        'miner_slug' => 'alpha-one',
        'logged_on' => '2026-03-20',
    ]);

    $response->assertRedirect(route('dashboard.miner').'?miner=alpha-one');

    $copiedLog = $miner->performanceLogs()
        ->whereDate('logged_on', '2026-03-20')
        ->where('notes', 'like', '%Copied forward from 2026-03-19.%')
        ->firstOrFail();

    expect((float) $copiedLog->revenue_usd)->toBe(1400.50);
    expect((float) $copiedLog->hashrate_th)->toBe(490.10);
    expect((float) $copiedLog->btc_price_usd)->toBe(88310.55);
    expect($copiedLog->notes)->toContain('Copied forward from 2026-03-19.');
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
