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
    $inviter->refresh();
    $inviter->load(['earnings', 'referralEvents']);

    expect($referredUser->sponsor_user_id)->toBe($inviter->id);
    expect($inviter->earnings->where('source', 'referral_registration'))->toHaveCount(1);
    expect($inviter->referralEvents->where('type', 'team_registered'))->toHaveCount(1);
});

test('inviter receives subscription rewards and team bonus rate when referred user buys a package', function () {
    $inviter = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($inviter)->post(route('general.sell-products.subscribe'), [
        'package' => 'starter-100',
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

    $this->actingAs($buyer)->post(route('general.sell-products.subscribe'), [
        'package' => 'growth-500',
    ])->assertRedirect();

    $inviter->refresh();
    $inviter->load(['earnings', 'investments', 'referralEvents']);

    expect((float) $inviter->earnings->firstWhere('source', 'referral_subscription')->amount)->toBe(25.0)
        ->and((float) $inviter->earnings->firstWhere('source', 'team_subscription_bonus')->amount)->toBe(15.0)
        ->and((float) $inviter->investments->first()->fresh()->team_bonus_rate)->toBe(0.0025)
        ->and($inviter->referralEvents->where('type', 'team_subscription'))->toHaveCount(1);
});
