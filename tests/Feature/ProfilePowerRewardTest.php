<?php

use App\Models\Earning;
use App\Models\FriendInvitation;
use App\Models\InvestmentPackage;
use App\Models\Miner;
use App\Models\User;
use App\Models\UserInvestment;
use App\Models\UserLevel;
use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('profile power reward caps reach 4 6 and 7 percent for strong investors by package tier', function () {
    $miner = Miner::query()->where('slug', 'alpha-one')->firstOrFail();
    $basicPackage = InvestmentPackage::query()->where('slug', 'starter-100')->firstOrFail();
    $growthPackage = InvestmentPackage::query()->where('slug', 'growth-500')->firstOrFail();
    $scalePackage = InvestmentPackage::query()->where('slug', 'scale-1000')->firstOrFail();
    $platinum = UserLevel::query()->where('slug', 'platinum')->firstOrFail();

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'user_level_id' => $platinum->id,
        'account_type' => 'shareholder',
    ]);

    foreach (range(1, 10) as $index) {
        FriendInvitation::query()->create([
            'user_id' => $user->id,
            'name' => 'Invite '.$index,
            'email' => 'invite-'.$index.'@example.test',
            'verified_at' => now(),
            'registered_at' => now(),
        ]);
    }

    foreach (range(1, 3) as $index) {
        $directUser = User::factory()->create([
            'email_verified_at' => now(),
            'sponsor_user_id' => $user->id,
            'account_type' => 'shareholder',
        ]);

        UserInvestment::query()->create([
            'user_id' => $directUser->id,
            'miner_id' => $miner->id,
            'package_id' => $basicPackage->id,
            'amount' => 100,
            'shares_owned' => 1,
            'monthly_return_rate' => 0,
            'level_bonus_rate' => 0,
            'team_bonus_rate' => 0,
            'status' => 'active',
            'subscribed_at' => now(),
        ]);
    }

    $basicInvestment = UserInvestment::query()->create([
        'user_id' => $user->id,
        'miner_id' => $miner->id,
        'package_id' => $basicPackage->id,
        'amount' => 100,
        'shares_owned' => 1,
        'monthly_return_rate' => 0,
        'level_bonus_rate' => 0,
        'team_bonus_rate' => 0,
        'status' => 'active',
        'subscribed_at' => now(),
    ]);

    $growthInvestment = UserInvestment::query()->create([
        'user_id' => $user->id,
        'miner_id' => $miner->id,
        'package_id' => $growthPackage->id,
        'amount' => 500,
        'shares_owned' => 5,
        'monthly_return_rate' => 0,
        'level_bonus_rate' => 0,
        'team_bonus_rate' => 0,
        'status' => 'active',
        'subscribed_at' => now(),
    ]);

    $scaleInvestment = UserInvestment::query()->create([
        'user_id' => $user->id,
        'miner_id' => $miner->id,
        'package_id' => $scalePackage->id,
        'amount' => 1000,
        'shares_owned' => 10,
        'monthly_return_rate' => 0,
        'level_bonus_rate' => 0,
        'team_bonus_rate' => 0,
        'status' => 'active',
        'subscribed_at' => now(),
    ]);

    UserInvestment::query()->create([
        'user_id' => $user->id,
        'miner_id' => $miner->id,
        'package_id' => $scalePackage->id,
        'amount' => 1500,
        'shares_owned' => 15,
        'monthly_return_rate' => 0,
        'level_bonus_rate' => 0,
        'team_bonus_rate' => 0,
        'status' => 'active',
        'subscribed_at' => now(),
    ]);

    $user->load(['userLevel', 'friendInvitations', 'investments', 'sponsoredUsers.investments']);

    expect(MiningPlatform::profilePowerSummary($user)['score'])->toBe(100);
    expect(MiningPlatform::investmentProfilePowerRewardRate($basicInvestment->fresh()))->toBe(0.04);
    expect(MiningPlatform::investmentProfilePowerRewardRate($growthInvestment->fresh()))->toBe(0.06);
    expect(MiningPlatform::investmentProfilePowerRewardRate($scaleInvestment->fresh()))->toBe(0.07);

    MiningPlatform::generateMonthlyEarnings($user->fresh());
    MiningPlatform::maybeCelebrateProfilePower($user->fresh());
    MiningPlatform::maybeCelebrateProfilePower($user->fresh());

    expect(Earning::query()
        ->where('user_id', $user->id)
        ->where('investment_id', $basicInvestment->id)
        ->where('source', 'mining_return')
        ->firstOrFail()
        ->amount)->toBe('4.00');

    expect(Earning::query()
        ->where('user_id', $user->id)
        ->where('investment_id', $growthInvestment->id)
        ->where('source', 'mining_return')
        ->firstOrFail()
        ->amount)->toBe('30.00');

    expect(Earning::query()
        ->where('user_id', $user->id)
        ->where('investment_id', $scaleInvestment->id)
        ->where('source', 'mining_return')
        ->firstOrFail()
        ->amount)->toBe('70.00');

    $subjects = $user->fresh()->notifications->pluck('data.subject');

    expect($subjects)->toContain('Basic 100 full reward cap unlocked');
    expect($subjects)->toContain('Growth 500 full reward cap unlocked');
    expect($subjects)->toContain('Scale 1000+ full reward cap unlocked');
    expect($subjects->filter(fn ($subject) => str_contains($subject, 'full reward cap unlocked'))->count())->toBe(3);
});
