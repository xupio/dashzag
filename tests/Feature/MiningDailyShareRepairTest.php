<?php

use App\Models\Earning;
use App\Models\InvestmentPackage;
use App\Models\Miner;
use App\Models\MinerPerformanceLog;
use App\Models\User;
use App\Models\UserInvestment;
use App\Support\MiningPlatform;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;

it('does not create mining daily share earnings before an investment subscription date', function () {
    MiningPlatform::ensureDefaults();

    $user = User::factory()->create();
    $miner = Miner::query()->create([
        'name' => 'Repair Test Miner A',
        'slug' => 'repair-test-miner-a',
        'total_shares' => 100,
        'shares_sold' => 5,
        'share_price' => 100,
        'daily_output_usd' => 40,
        'monthly_output_usd' => 1200,
        'base_monthly_return_rate' => 0.0350,
        'status' => 'active',
    ]);
    $package = InvestmentPackage::query()->create([
        'miner_id' => $miner->id,
        'name' => 'Repair Growth 500 A',
        'slug' => 'repair-growth-500-a',
        'price' => 500,
        'shares_count' => 5,
        'units_limit' => 5,
        'monthly_return_rate' => 0.0350,
        'display_order' => 1,
        'is_active' => true,
    ]);

    $investment = UserInvestment::query()->create([
        'user_id' => $user->id,
        'miner_id' => $miner->id,
        'package_id' => $package->id,
        'amount' => 500,
        'shares_owned' => 5,
        'monthly_return_rate' => 0.0350,
        'level_bonus_rate' => 0.0000,
        'team_bonus_rate' => 0.0000,
        'status' => 'active',
        'subscribed_at' => Carbon::parse('2026-04-01 10:00:00'),
    ]);

    $beforeLog = MinerPerformanceLog::query()->create([
        'miner_id' => $miner->id,
        'logged_on' => '2026-03-31',
        'revenue_usd' => 50,
        'electricity_cost_usd' => 5,
        'maintenance_cost_usd' => 5,
        'net_profit_usd' => 40,
        'hashrate_th' => 100,
        'btc_price_usd' => 70000,
        'uptime_percentage' => 98,
        'active_shares' => 100,
        'revenue_per_share_usd' => 0.1000,
        'source' => 'manual',
    ]);

    $afterLog = MinerPerformanceLog::query()->create([
        'miner_id' => $miner->id,
        'logged_on' => '2026-04-01',
        'revenue_usd' => 50,
        'electricity_cost_usd' => 5,
        'maintenance_cost_usd' => 5,
        'net_profit_usd' => 40,
        'hashrate_th' => 100,
        'btc_price_usd' => 70000,
        'uptime_percentage' => 98,
        'active_shares' => 100,
        'revenue_per_share_usd' => 0.1000,
        'source' => 'manual',
    ]);

    MiningPlatform::distributeDailyPerformanceEarnings($beforeLog);
    MiningPlatform::distributeDailyPerformanceEarnings($afterLog);

    $earnings = Earning::query()
        ->where('investment_id', $investment->id)
        ->where('source', 'mining_daily_share')
        ->orderBy('earned_on')
        ->get();

    expect($earnings)->toHaveCount(1)
        ->and($earnings->first()->earned_on->toDateString())->toBe('2026-04-01');
});

it('repairs backdated mining daily share earnings by rebuilding from the subscription date', function () {
    MiningPlatform::ensureDefaults();

    $user = User::factory()->create();
    $miner = Miner::query()->create([
        'name' => 'Repair Test Miner B',
        'slug' => 'repair-test-miner-b',
        'total_shares' => 100,
        'shares_sold' => 5,
        'share_price' => 100,
        'daily_output_usd' => 40,
        'monthly_output_usd' => 1200,
        'base_monthly_return_rate' => 0.0350,
        'status' => 'active',
    ]);
    $package = InvestmentPackage::query()->create([
        'miner_id' => $miner->id,
        'name' => 'Repair Growth 500 B',
        'slug' => 'repair-growth-500-b',
        'price' => 500,
        'shares_count' => 5,
        'units_limit' => 5,
        'monthly_return_rate' => 0.0350,
        'display_order' => 1,
        'is_active' => true,
    ]);

    $investment = UserInvestment::query()->create([
        'user_id' => $user->id,
        'miner_id' => $miner->id,
        'package_id' => $package->id,
        'amount' => 500,
        'shares_owned' => 5,
        'monthly_return_rate' => 0.0350,
        'level_bonus_rate' => 0.0000,
        'team_bonus_rate' => 0.0000,
        'status' => 'active',
        'subscribed_at' => Carbon::parse('2026-04-01 10:00:00'),
    ]);

    MinerPerformanceLog::query()->create([
        'miner_id' => $miner->id,
        'logged_on' => '2026-03-30',
        'revenue_usd' => 50,
        'electricity_cost_usd' => 5,
        'maintenance_cost_usd' => 5,
        'net_profit_usd' => 40,
        'hashrate_th' => 100,
        'btc_price_usd' => 70000,
        'uptime_percentage' => 98,
        'active_shares' => 100,
        'revenue_per_share_usd' => 0.1000,
        'source' => 'manual',
    ]);

    MinerPerformanceLog::query()->create([
        'miner_id' => $miner->id,
        'logged_on' => '2026-04-01',
        'revenue_usd' => 50,
        'electricity_cost_usd' => 5,
        'maintenance_cost_usd' => 5,
        'net_profit_usd' => 40,
        'hashrate_th' => 100,
        'btc_price_usd' => 70000,
        'uptime_percentage' => 98,
        'active_shares' => 100,
        'revenue_per_share_usd' => 0.1000,
        'source' => 'manual',
    ]);

    Earning::query()->create([
        'user_id' => $user->id,
        'investment_id' => $investment->id,
        'earned_on' => '2026-03-30',
        'amount' => 0.25,
        'source' => 'mining_daily_share',
        'status' => 'pending',
        'notes' => 'Incorrect backdated earning.',
    ]);

    Earning::query()->create([
        'user_id' => $user->id,
        'investment_id' => $investment->id,
        'earned_on' => '2026-04-01',
        'amount' => 0.01,
        'source' => 'mining_daily_share',
        'status' => 'pending',
        'notes' => 'Outdated earning that should be rebuilt.',
    ]);

    Artisan::call('miners:repair-daily-share-earnings', [
        '--investment' => $investment->id,
    ]);

    $earnings = Earning::query()
        ->where('investment_id', $investment->id)
        ->where('source', 'mining_daily_share')
        ->orderBy('earned_on')
        ->get();

    expect($earnings)->toHaveCount(1)
        ->and($earnings->first()->earned_on->toDateString())->toBe('2026-04-01')
        ->and((float) $earnings->first()->amount)->toBe(0.5);
});
