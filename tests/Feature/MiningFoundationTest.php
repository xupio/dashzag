<?php

use App\Models\InvestmentPackage;
use App\Models\Miner;
use App\Models\User;
use App\Models\UserLevel;
use App\Support\MiningPlatform;

test('mining platform seeds alpha one defaults', function () {
    MiningPlatform::ensureDefaults();

    $miner = Miner::where('slug', 'alpha-one')->first();

    expect($miner)->not->toBeNull();
    expect($miner->name)->toBe('Alpha One');
    expect(InvestmentPackage::count())->toBe(3);
    expect(UserLevel::count())->toBe(4);
    expect($miner->performanceLogs()->count())->toBe(7);
});

test('user level upgrades when investment and referrals increase', function () {
    MiningPlatform::ensureDefaults();

    $user = User::factory()->create();

    $user->friendInvitations()->createMany([
        ['name' => 'Friend One', 'email' => 'friend1@example.com', 'registered_at' => now()],
        ['name' => 'Friend Two', 'email' => 'friend2@example.com', 'registered_at' => now()],
    ]);

    $miner = Miner::where('slug', 'alpha-one')->firstOrFail();
    $package = InvestmentPackage::where('slug', 'growth-500')->firstOrFail();

    $user->investments()->create([
        'miner_id' => $miner->id,
        'package_id' => $package->id,
        'amount' => 500,
        'shares_owned' => 5,
        'monthly_return_rate' => 0.0850,
        'level_bonus_rate' => 0.0000,
        'status' => 'active',
        'subscribed_at' => now(),
    ]);

    $level = MiningPlatform::syncUserLevel($user->fresh());

    expect($level->slug)->toBe('silver');
    expect($user->fresh()->userLevel->slug)->toBe('silver');
});

