<?php

use App\Http\Controllers\Auth\VerifyEmailController;
use App\Models\FriendInvitation;
use App\Models\User;
use App\Support\MiningPlatform;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

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
    expect($inviter->referralEvents->where('type', 'team_registered'))->toHaveCount(1);
    expect($inviter->notifications->pluck('data.subject'))->toContain('A referred user joined your network');
    expect($inviter->notifications->pluck('data.subject'))->toContain('Referral registration reward added');
    expect($referredUser->notifications->pluck('data.subject'))->toContain('You are now linked to a sponsor team');
});

test('inviter receives subscription rewards and team bonus rate when referred user buys a package', function () {
    $inviter = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($inviter)->post(route('dashboard.buy-shares.subscribe'), [
        'package' => 'growth-500',
    ])->assertRedirect();

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

    $this->actingAs($buyer)->post(route('dashboard.buy-shares.subscribe'), [
        'package' => 'growth-500',
    ])->assertRedirect();

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

    $this->actingAs($referredBuyer)->post(route('dashboard.buy-shares.subscribe'), [
        'package' => 'starter-100',
    ]);

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

    $this->actingAs($buyer)->post(route('dashboard.buy-shares.subscribe'), [
        'package' => 'growth-500',
    ])->assertRedirect();

    $levelOne->refresh();
    $levelOne->load('earnings', 'referralEvents', 'notifications');

    expect((float) $levelOne->earnings->firstWhere('source', 'team_level_3_bonus')->amount)->toBe(2.5)
        ->and($levelOne->referralEvents->where('type', 'team_level_3_subscription'))->toHaveCount(1);

    expect($levelOne->notifications->pluck('data.subject'))->toContain('A level 3 investor subscribed');
});

