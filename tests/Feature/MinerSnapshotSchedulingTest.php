<?php

use App\Models\Earning;
use App\Models\Miner;
use App\Models\User;
use App\Models\UserInvestment;
use App\Support\MiningPlatform;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('daily miner snapshot command generates logs and earnings without duplicates', function () {
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

    Artisan::call('miners:generate-daily-snapshots', [
        '--date' => '2026-03-16',
        '--miner' => 'alpha-one',
    ]);

    Artisan::call('miners:generate-daily-snapshots', [
        '--date' => '2026-03-16',
        '--miner' => 'alpha-one',
    ]);

    $log = $miner->performanceLogs()->whereDate('logged_on', '2026-03-16')->first();
    $earning = Earning::query()
        ->where('investment_id', $investment->id)
        ->whereDate('earned_on', '2026-03-16')
        ->where('source', 'mining_daily_share')
        ->get();

    expect($log)->not->toBeNull();
    expect($log->source)->toBe('automatic');
    expect((float) $log->net_profit_usd)->toBeGreaterThan(0);
    expect($earning)->toHaveCount(1);
    expect((float) $earning->first()->amount)->toBe(round((float) $log->revenue_per_share_usd, 2));
});

test('daily miner snapshot command can process all active miners', function () {
    Artisan::call('miners:generate-daily-snapshots', [
        '--date' => '2026-03-17',
    ]);

    $alphaOne = Miner::where('slug', 'alpha-one')->firstOrFail();
    $betaFlux = Miner::where('slug', 'beta-flux')->firstOrFail();

    expect($alphaOne->performanceLogs()->whereDate('logged_on', '2026-03-17')->exists())->toBeTrue();
    expect($betaFlux->performanceLogs()->whereDate('logged_on', '2026-03-17')->exists())->toBeTrue();
});
