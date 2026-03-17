<?php

use App\Models\InvestmentOrder;
use App\Models\User;
use App\Notifications\ActivityFeedNotification;
use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('user can switch dashboard and sell pages between miners', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'user',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard').'?miner=beta-flux')
        ->assertOk()
        ->assertSee('Beta Flux Overview')
        ->assertSee('Share status')
        ->assertSee('Live miner performance')
        ->assertSee('Open Daily Miner Report')
        ->assertSee('Open Hall of Fame');

    $this->actingAs($user)
        ->get(route('dashboard.buy-shares').'?miner=beta-flux')
        ->assertOk()
        ->assertSee('Buy Beta Flux Shares')
        ->assertSee('Daily miner report')
        ->assertSee('Momentum 300');
});

test('user can submit a package payment on the secondary miner for review', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'user',
    ]);

    $response = $this->actingAs($user)->post(route('dashboard.buy-shares.subscribe'), [
        'package' => 'momentum-300',
        'payment_method' => 'usdt_transfer',
        'payment_reference' => 'BETA-REF-1001',
    ]);

    $response->assertRedirect(route('dashboard.buy-shares', ['miner' => 'beta-flux']));

    $order = InvestmentOrder::query()->latest('id')->first();

    expect($order)->not->toBeNull();
    expect($order->status)->toBe('pending');
    expect($order->miner->slug)->toBe('beta-flux');
    expect($order->package->slug)->toBe('momentum-300');
});

test('dashboard investor pipeline shows unlocked reward cap badges', function () {
    $viewer = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'user',
    ]);

    $investor = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'shareholder',
    ]);

    $miner = \App\Models\Miner::query()->where('slug', 'alpha-one')->firstOrFail();
    $package = \App\Models\InvestmentPackage::query()->where('slug', 'growth-500')->firstOrFail();

    \App\Models\UserInvestment::query()->create([
        'user_id' => $investor->id,
        'miner_id' => $miner->id,
        'package_id' => $package->id,
        'amount' => 500,
        'shares_owned' => 5,
        'monthly_return_rate' => 0,
        'level_bonus_rate' => 0,
        'team_bonus_rate' => 0,
        'status' => 'active',
        'subscribed_at' => now(),
    ]);

    $investor->notify(new ActivityFeedNotification([
        'event_key' => 'profile_power_reward_cap',
        'reward_cap_tier' => 'growth',
        'category' => 'milestone',
        'status' => 'success',
        'subject' => 'Growth 500 full reward cap unlocked',
        'message' => 'You unlocked the full 6.00% profile power reward cap for Growth 500.',
    ]));

    $response = $this->actingAs($viewer)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('6% cap');
});
