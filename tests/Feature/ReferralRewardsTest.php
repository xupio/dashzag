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

test('inviter receives registration reward when invited user verifies email', function () {
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

    $inviter->refresh();
    $inviter->load('earnings');

    expect($inviter->earnings->where('source', 'referral_registration'))->toHaveCount(1);
    expect((float) $inviter->earnings->firstWhere('source', 'referral_registration')->amount)->toBe(25.0);
});

test('inviter receives subscription reward when invited user buys a package', function () {
    $inviter = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    FriendInvitation::create([
        'user_id' => $inviter->id,
        'name' => 'Referred Buyer',
        'email' => 'buyer@example.com',
        'registered_at' => now(),
    ]);

    $buyer = User::factory()->create([
        'email' => 'buyer@example.com',
        'email_verified_at' => now(),
        'account_type' => 'user',
    ]);

    $this->actingAs($buyer)->post(route('general.sell-products.subscribe'), [
        'package' => 'growth-500',
    ])->assertRedirect(route('general.sell-products'));

    $inviter->refresh();
    $inviter->load('earnings');

    $reward = $inviter->earnings->firstWhere('source', 'referral_subscription');

    expect($reward)->not->toBeNull();
    expect((float) $reward->amount)->toBe(25.0);
});
