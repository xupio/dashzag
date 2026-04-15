<?php

use App\Models\InvestmentPackage;
use App\Models\Miner;
use App\Models\PayoutRequest;
use App\Models\User;
use App\Models\UserInvestment;
use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('zagn8n marketing feed requires a valid token', function () {
    config()->set('services.zagn8n.enabled', true);
    config()->set('services.zagn8n.token', 'secret-token');

    $this->get(route('zagn8n.marketing-feed'))->assertForbidden();
    $this->get(route('zagn8n.marketing-feed', ['token' => 'wrong-token']))->assertForbidden();
});

test('zagn8n marketing feed returns safe marketing metrics payload', function () {
    config()->set('services.zagn8n.enabled', true);
    config()->set('services.zagn8n.token', 'secret-token');

    $miner = Miner::query()->where('slug', 'alpha-one')->firstOrFail();
    $package = InvestmentPackage::query()->where('slug', 'scale-1000')->firstOrFail();

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'kyc_status' => 'approved',
        'kyc_reviewed_at' => now()->subHours(3),
    ]);

    UserInvestment::query()->create([
        'user_id' => $user->id,
        'miner_id' => $miner->id,
        'package_id' => $package->id,
        'amount' => 1000,
        'shares_owned' => 10,
        'monthly_return_rate' => $package->monthly_return_rate,
        'level_bonus_rate' => 0,
        'team_bonus_rate' => 0,
        'status' => 'active',
        'subscribed_at' => now()->subHours(6),
    ]);

    PayoutRequest::query()->create([
        'user_id' => $user->id,
        'amount' => 100,
        'fee_amount' => 5,
        'net_amount' => 95,
        'fee_rate' => 0.05,
        'method' => 'btc_wallet',
        'destination' => 'bc1qzagn8ntest',
        'status' => 'paid',
        'requested_at' => now()->subHours(4),
        'approved_at' => now()->subHours(3),
        'processed_at' => now()->subHours(2),
    ]);

    $response = $this->withHeaders([
        'X-ZagChain-Automation-Token' => 'secret-token',
    ])->get(route('zagn8n.marketing-feed'));

    $response->assertOk();
    $response->assertJsonPath('feed', 'zagn8n_marketing_safe_metrics');
    $response->assertJsonPath('totals.active_investor_count', 1);
    $response->assertJsonPath('totals.approved_kyc_count', 1);
    $response->assertJsonPath('totals.paid_payout_count', 1);
    $response->assertJsonPath('totals.paid_payout_total_usd', 95);
    $response->assertJsonPath('last_24_hours.new_active_investments', 1);
    $response->assertJsonPath('last_24_hours.paid_payouts', 1);
    $response->assertJsonStructure([
        'app' => ['name', 'env', 'url'],
        'totals',
        'last_24_hours',
        'last_7_days',
        'top_packages',
        'content_rules',
    ]);
});
