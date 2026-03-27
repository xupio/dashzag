<?php

use App\Http\Controllers\Auth\VerifyEmailController;
use App\Models\FriendInvitation;
use App\Models\InvestmentPackage;
use App\Models\Shareholder;
use App\Models\User;
use App\Models\UserInvestment;
use App\Support\MiningPlatform;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

function createActiveInvestmentForUser(User $user, string $packageSlug = 'growth-500'): UserInvestment
{
    $package = InvestmentPackage::query()->where('slug', $packageSlug)->with('miner')->firstOrFail();
    $level = MiningPlatform::syncUserLevel($user);
    $teamBonusRate = MiningPlatform::teamBonusRate($user);

    $shareholder = Shareholder::query()->updateOrCreate(
        ['user_id' => $user->id],
        [
            'package_name' => $package->name,
            'price' => $package->price,
            'billing_cycle' => 'monthly',
            'units_limit' => $package->units_limit,
            'status' => 'active',
            'subscribed_at' => now(),
        ],
    );

    $investment = UserInvestment::query()->create([
        'user_id' => $user->id,
        'miner_id' => $package->miner_id,
        'package_id' => $package->id,
        'shareholder_id' => $shareholder->id,
        'amount' => $package->price,
        'shares_owned' => $package->shares_count,
        'monthly_return_rate' => $package->monthly_return_rate,
        'level_bonus_rate' => $level->bonus_rate,
        'team_bonus_rate' => $teamBonusRate,
        'status' => 'active',
        'subscribed_at' => now(),
    ]);

    if (! in_array($user->account_type, ['starter', 'shareholder'], true)) {
        $user->forceFill(['account_type' => 'shareholder'])->save();
    }

    return $investment->fresh();
}

test('invited user is attached to sponsor when email is verified', function () {
    $inviter = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    FriendInvitation::create([
        'user_id' => $inviter->id,
        'name' => 'Referred User',
        'email' => 'referred@example.com',
    ]);

    $referredUser = User::factory()->create([
        'email' => 'referred@example.com',
        'email_verified_at' => null,
    ]);

    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $referredUser->id,
        'hash' => sha1($referredUser->email),
    ]);

    $request = EmailVerificationRequest::create($url, 'GET');
    $request->setUserResolver(fn () => $referredUser);

    app(VerifyEmailController::class)($request);

    $referredUser->refresh();
    $referredUser->load('notifications');
    $inviter->refresh();
    $inviter->load(['earnings', 'referralEvents', 'notifications']);

    expect($referredUser->sponsor_user_id)->toBe($inviter->id);
    expect($inviter->earnings->where('source', 'referral_registration'))->toHaveCount(1);
    expect($inviter->earnings->firstWhere('source', 'referral_registration')?->status)->toBe('pending');
    expect($inviter->referralEvents->where('type', 'team_registered'))->toHaveCount(1);
    expect($inviter->notifications->pluck('data.subject'))->toContain('A referred user joined your network');
    expect($inviter->notifications->pluck('data.subject'))->toContain('Referral registration reward added');
    expect($referredUser->notifications->pluck('data.subject'))->toContain('You are now linked to a sponsor team');
});

test('referral registration reward unlocks only after inviter is on basic 100 and tree investment supports the cap', function () {
    $inviter = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    FriendInvitation::create([
        'user_id' => $inviter->id,
        'name' => 'First Direct Referral',
        'email' => 'first-direct@example.com',
        'verified_at' => now(),
        'registered_at' => now(),
    ]);

    $directReferral = User::factory()->create([
        'email_verified_at' => now(),
        'email' => 'first-direct@example.com',
        'sponsor_user_id' => $inviter->id,
    ]);

    MiningPlatform::awardReferralRegistration($directReferral);

    expect((float) $inviter->fresh()->earnings()->where('source', 'referral_registration')->where('status', 'available')->sum('amount'))->toBe(0.0);

    createActiveInvestmentForUser($inviter, 'starter-100');

    expect((float) $inviter->fresh()->earnings()->where('source', 'referral_registration')->where('status', 'available')->sum('amount'))->toBe(0.0);

    $investment = createActiveInvestmentForUser($directReferral, 'starter-100');
    MiningPlatform::awardReferralSubscription($directReferral, $investment);

    expect((float) $inviter->fresh()->earnings()->where('source', 'referral_registration')->where('status', 'available')->sum('amount'))->toBe(25.0);
});

test('referral registration rewards stay visible but only unlock up to fifty percent of three-level tree investment', function () {
    $inviter = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    createActiveInvestmentForUser($inviter, 'starter-100');

    foreach (range(1, 3) as $index) {
        FriendInvitation::create([
            'user_id' => $inviter->id,
            'name' => 'Referral '.$index,
            'email' => 'reward-cap-'.$index.'@example.com',
            'verified_at' => now(),
            'registered_at' => now(),
        ]);

        $registeredUser = User::factory()->create([
            'email_verified_at' => now(),
            'email' => 'reward-cap-'.$index.'@example.com',
            'sponsor_user_id' => $inviter->id,
        ]);

        MiningPlatform::awardReferralRegistration($registeredUser);
    }

    $firstBuyer = User::query()->where('email', 'reward-cap-1@example.com')->firstOrFail();

    $investment = createActiveInvestmentForUser($firstBuyer, 'starter-100');
    MiningPlatform::awardReferralSubscription($firstBuyer, $investment);

    $inviter->refresh();

    expect((float) $inviter->earnings()->where('source', 'referral_registration')->sum('amount'))->toBe(75.0);
    expect((float) $inviter->earnings()->where('source', 'referral_registration')->where('status', 'available')->sum('amount'))->toBe(50.0);
    expect((float) $inviter->earnings()->where('source', 'referral_registration')->where('status', 'pending')->sum('amount'))->toBe(25.0);
});

test('inviter receives subscription rewards and team bonus rate when referred user buys a package', function () {
    $inviter = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    createActiveInvestmentForUser($inviter, 'growth-500');

    FriendInvitation::create([
        'user_id' => $inviter->id,
        'name' => 'Referred Buyer',
        'email' => 'buyer@example.com',
        'verified_at' => now(),
        'registered_at' => now(),
    ]);

    $buyer = User::factory()->create([
        'email' => 'buyer@example.com',
        'email_verified_at' => now(),
        'account_type' => 'user',
        'sponsor_user_id' => $inviter->id,
    ]);

    $investment = createActiveInvestmentForUser($buyer, 'growth-500');
    MiningPlatform::awardReferralSubscription($buyer, $investment);

    $inviter->refresh();
    $inviter->load(['earnings', 'investments', 'referralEvents', 'notifications']);
    $buyer->refresh();
    $buyer->load('notifications');

    expect((float) $inviter->earnings->firstWhere('source', 'referral_subscription')->amount)->toBe(25.0)
        ->and((float) $inviter->earnings->firstWhere('source', 'team_subscription_bonus')->amount)->toBe(15.0)
        ->and((float) $inviter->investments->first()->fresh()->team_bonus_rate)->toBe(0.0025)
        ->and($inviter->referralEvents->where('type', 'team_subscription'))->toHaveCount(1);

    expect($inviter->notifications->pluck('data.subject'))->toContain('Direct referral investment reward added');
    expect($buyer->notifications->pluck('data.subject'))->toContain('Investment subscription activated');
});

test('starter user unlocks basic 100 after referral mission is completed', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'starter',
    ]);

    MiningPlatform::ensureStarterPackage($user);

    foreach (range(1, 20) as $index) {
        FriendInvitation::create([
            'user_id' => $user->id,
            'name' => 'Invite '.$index,
            'email' => 'invite'.$index.'@example.com',
            'verified_at' => now(),
        ]);
    }

    $referredBuyer = User::factory()->create([
        'email_verified_at' => now(),
        'email' => 'basic-referral@example.com',
        'sponsor_user_id' => $user->id,
    ]);

    $investment = createActiveInvestmentForUser($referredBuyer, 'starter-100');
    MiningPlatform::awardReferralSubscription($referredBuyer, $investment);

    $user->refresh();
    $user->load(['shareholder', 'investments.package', 'notifications']);

    expect($user->account_type)->toBe('shareholder');
    expect($user->shareholder?->package_name)->toBe('Basic 100');
    expect($user->investments->where('package.slug', 'starter-100')->count())->toBeGreaterThan(0);
    expect($user->notifications->pluck('data.subject'))->toContain('Basic 100 unlocked');
});

test('third level sponsor receives configured mlm subscription reward', function () {
    MiningPlatform::updateRewardSettings([
        'team_level_3_subscription_reward_rate' => '0.0050',
    ]);

    $levelOne = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $levelTwo = User::factory()->create([
        'email_verified_at' => now(),
        'sponsor_user_id' => $levelOne->id,
    ]);

    $levelThree = User::factory()->create([
        'email_verified_at' => now(),
        'sponsor_user_id' => $levelTwo->id,
    ]);

    $buyer = User::factory()->create([
        'email_verified_at' => now(),
        'email' => 'deepbuyer@example.com',
        'sponsor_user_id' => $levelThree->id,
    ]);

    $investment = createActiveInvestmentForUser($buyer, 'growth-500');
    MiningPlatform::awardReferralSubscription($buyer, $investment);

    $levelOne->refresh();
    $levelOne->load('earnings', 'referralEvents', 'notifications');

    expect((float) $levelOne->earnings->firstWhere('source', 'team_level_3_bonus')->amount)->toBe(2.5)
        ->and($levelOne->referralEvents->where('type', 'team_level_3_subscription'))->toHaveCount(1);

    expect($levelOne->notifications->pluck('data.subject'))->toContain('A level 3 investor subscribed');
});

