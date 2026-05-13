<?php

use App\Models\AdminActivityLog;
use App\Models\FriendInvitation;
use App\Models\InvestmentOrder;
use App\Models\InvestmentPackage;
use App\Models\Miner;
use App\Models\ShareHolding;
use App\Models\ShareListing;
use App\Models\ShareMarketTransaction;
use App\Models\ShareSale;
use App\Models\User;
use App\Models\UserInvestment;
use App\Models\UserLevel;
use App\Support\MiningPlatform;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    MiningPlatform::ensureDefaults();
});

function pendingInvestmentOrderFor(User $user, string $reference = 'TX-PENDING-001', string $status = 'pending'): InvestmentOrder
{
    $package = InvestmentPackage::query()->where('slug', 'growth-500')->with('miner')->firstOrFail();

    return InvestmentOrder::create([
        'user_id' => $user->id,
        'miner_id' => $package->miner_id,
        'package_id' => $package->id,
        'amount' => $package->price,
        'shares_owned' => $package->shares_count,
        'payment_method' => 'btc_transfer',
        'payment_reference' => $reference,
        'status' => $status,
        'submitted_at' => now(),
        'rejected_at' => $status === 'rejected' ? now() : null,
        'approved_at' => $status === 'approved' ? now() : null,
        'cancelled_at' => $status === 'cancelled' ? now() : null,
    ]);
}

function createStrongRewardCapInvestor(string $name = 'Cap Queue Investor', string $email = 'cap-queue@example.com'): User
{
    $miner = Miner::query()->where('slug', 'alpha-one')->firstOrFail();
    $basicPackage = InvestmentPackage::query()->where('slug', 'starter-100')->firstOrFail();
    $growthPackage = InvestmentPackage::query()->where('slug', 'growth-500')->firstOrFail();
    $scalePackage = InvestmentPackage::query()->where('slug', 'scale-1000')->firstOrFail();
    $platinum = UserLevel::query()->where('slug', 'platinum')->firstOrFail();

    $user = User::factory()->create([
        'name' => $name,
        'email' => $email,
        'email_verified_at' => now(),
        'user_level_id' => $platinum->id,
        'account_type' => 'shareholder',
    ]);

    foreach (range(1, 10) as $index) {
        FriendInvitation::query()->create([
            'user_id' => $user->id,
            'name' => 'Invite '.$index,
            'email' => 'queue-cap-'.$index.'@example.test',
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

    UserInvestment::query()->create([
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

    UserInvestment::query()->create([
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
        'amount' => 1000,
        'shares_owned' => 10,
        'monthly_return_rate' => 0,
        'level_bonus_rate' => 0,
        'team_bonus_rate' => 0,
        'status' => 'active',
        'subscribed_at' => now(),
    ]);

    return $user->fresh();
}

test('user can view filtered investment order history and cancel a pending order', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $pendingOrder = pendingInvestmentOrderFor($user, 'TX-HISTORY-PENDING', 'pending');
    pendingInvestmentOrderFor($user, 'TX-HISTORY-REJECTED', 'rejected');

    $this->actingAs($user)
        ->get(route('dashboard.investment-orders', ['status' => 'pending']))
        ->assertOk()
        ->assertSee('TX-HISTORY-PENDING')
        ->assertDontSee('TX-HISTORY-REJECTED');

    $this->actingAs($user)
        ->post(route('dashboard.investment-orders.cancel', $pendingOrder))
        ->assertRedirect(route('dashboard.investment-orders'));

    expect($pendingOrder->fresh()->status)->toBe('cancelled');
    expect($pendingOrder->fresh()->cancelled_at)->not->toBeNull();
});

test('user cannot cancel a non pending investment order', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $approvedOrder = pendingInvestmentOrderFor($user, 'TX-APPROVED-LOCKED', 'approved');

    $this->actingAs($user)
        ->from(route('dashboard.investment-orders'))
        ->post(route('dashboard.investment-orders.cancel', $approvedOrder))
        ->assertRedirect(route('dashboard.investment-orders'))
        ->assertSessionHasErrors('cancel');

    expect($approvedOrder->fresh()->status)->toBe('approved');
});

test('admin can filter operations investment queue by status and search term', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $firstUser = User::factory()->create(['email_verified_at' => now(), 'name' => 'Alpha Investor']);
    $secondUser = User::factory()->create(['email_verified_at' => now(), 'name' => 'Beta Investor']);

    pendingInvestmentOrderFor($firstUser, 'FILTER-PENDING-100', 'pending');
    pendingInvestmentOrderFor($secondUser, 'FILTER-REJECTED-200', 'rejected');

    $this->actingAs($admin)
        ->get(route('dashboard.operations', ['investment_status' => 'pending', 'investment_search' => 'Alpha']))
        ->assertOk()
        ->assertSee('FILTER-PENDING-100')
        ->assertSee('Missing proof')
        ->assertSee('Bulk review reminders')
        ->assertSee('Admin review note')
        ->assertSee('Confirm the wallet matches the active BTC treasury address')
        ->assertDontSee('FILTER-REJECTED-200');
});

test('admin can export filtered investment orders csv', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $matchingUser = User::factory()->create(['email_verified_at' => now(), 'name' => 'Gamma Investor']);
    $otherUser = User::factory()->create(['email_verified_at' => now(), 'name' => 'Delta Investor']);

    pendingInvestmentOrderFor($matchingUser, 'EXPORT-MATCH-001', 'pending');
    pendingInvestmentOrderFor($otherUser, 'EXPORT-OTHER-002', 'approved');

    $response = $this->actingAs($admin)
        ->get(route('dashboard.operations.investment-orders.export', [
            'investment_status' => 'pending',
            'investment_search' => 'Gamma',
        ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('Filter');
    expect($csv)->toContain('pending');
    expect($csv)->toContain('Gamma');
    expect($csv)->toContain('EXPORT-MATCH-001');
    expect($csv)->not->toContain('EXPORT-OTHER-002');
});
test('admin can bulk approve only pending investment orders that already have proof', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $proofUser = User::factory()->create(['email_verified_at' => now()]);
    $missingProofUser = User::factory()->create(['email_verified_at' => now()]);

    $approvableOrder = pendingInvestmentOrderFor($proofUser, 'BULK-APPROVE-001', 'pending');
    $approvableOrder->forceFill([
        'payment_proof_path' => 'investment-proofs/bulk-approve-proof.pdf',
        'payment_proof_original_name' => 'bulk-approve-proof.pdf',
        'proof_uploaded_at' => now(),
    ])->save();

    $pendingWithoutProof = pendingInvestmentOrderFor($missingProofUser, 'BULK-APPROVE-002', 'pending');

    $this->actingAs($admin)
        ->post(route('dashboard.operations.investment-orders.bulk'), [
            'action' => 'approve',
            'order_ids' => [$approvableOrder->id, $pendingWithoutProof->id],
        ])
        ->assertRedirect(route('dashboard.operations'));

    expect($approvableOrder->fresh()->status)->toBe('approved');
    expect($pendingWithoutProof->fresh()->status)->toBe('pending');
});

test('admin can bulk reject pending investment orders with one shared note', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $firstUser = User::factory()->create(['email_verified_at' => now()]);
    $secondUser = User::factory()->create(['email_verified_at' => now()]);
    $approvedUser = User::factory()->create(['email_verified_at' => now()]);

    $firstPending = pendingInvestmentOrderFor($firstUser, 'BULK-REJECT-001', 'pending');
    $secondPending = pendingInvestmentOrderFor($secondUser, 'BULK-REJECT-002', 'pending');
    $approvedOrder = pendingInvestmentOrderFor($approvedUser, 'BULK-REJECT-003', 'approved');

    $this->actingAs($admin)
        ->post(route('dashboard.operations.investment-orders.bulk'), [
            'action' => 'reject',
            'admin_notes' => 'Bulk review rejected these references.',
            'order_ids' => [$firstPending->id, $secondPending->id, $approvedOrder->id],
        ])
        ->assertRedirect(route('dashboard.operations'));

    expect($firstPending->fresh()->status)->toBe('rejected');
    expect($secondPending->fresh()->status)->toBe('rejected');
    expect($firstPending->fresh()->admin_notes)->toBe('Bulk review rejected these references.');
    expect($approvedOrder->fresh()->status)->toBe('approved');
});



test('admin can filter operations investment queue by payment method', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $btcUser = User::factory()->create(['email_verified_at' => now(), 'name' => 'Bitcoin Investor']);
    $usdtUser = User::factory()->create(['email_verified_at' => now(), 'name' => 'Tether Investor']);

    pendingInvestmentOrderFor($btcUser, 'METHOD-BTC-001', 'pending');
    $usdtOrder = pendingInvestmentOrderFor($usdtUser, 'METHOD-USDT-002', 'pending');
    $usdtOrder->forceFill(['payment_method' => 'usdt_transfer'])->save();

    $this->actingAs($admin)
        ->get(route('dashboard.operations', ['investment_status' => 'pending', 'investment_payment_method' => 'usdt_transfer']))
        ->assertOk()
        ->assertSee('METHOD-USDT-002')
        ->assertSee('USDT: 1')
        ->assertDontSee('METHOD-BTC-001');
});

test('admin can filter operations investment queue by proof state', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $missingProofUser = User::factory()->create(['email_verified_at' => now(), 'name' => 'Missing Proof Investor']);
    $proofUser = User::factory()->create(['email_verified_at' => now(), 'name' => 'Uploaded Proof Investor']);

    $missingProofOrder = pendingInvestmentOrderFor($missingProofUser, 'PROOF-MISSING-001', 'pending');
    $uploadedProofOrder = pendingInvestmentOrderFor($proofUser, 'PROOF-UPLOADED-002', 'pending');
    $uploadedProofOrder->forceFill([
        'payment_proof_path' => 'investment-proofs/proof-filter.pdf',
        'payment_proof_original_name' => 'proof-filter.pdf',
        'proof_uploaded_at' => now(),
    ])->save();

    $this->actingAs($admin)
        ->get(route('dashboard.operations', ['investment_status' => 'pending', 'investment_proof_state' => 'proof_needed']))
        ->assertOk()
        ->assertSee('PROOF-MISSING-001')
        ->assertSee('Proof needed: 1')
        ->assertDontSee('PROOF-UPLOADED-002');
});

test('admin can filter operations investment queue by high risk state', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $highRiskUser = User::factory()->create(['email_verified_at' => now(), 'name' => 'High Risk Investor']);
    $cleanUser = User::factory()->create(['email_verified_at' => now(), 'name' => 'Clean Investor']);

    pendingInvestmentOrderFor($highRiskUser, 'RISK-HIGH-001', 'pending');
    $cleanOrder = pendingInvestmentOrderFor($cleanUser, 'RISK-CLEAN-002', 'pending');
    $cleanOrder->forceFill([
        'payment_proof_path' => 'investment-proofs/risk-clean.pdf',
        'payment_proof_original_name' => 'risk-clean.pdf',
        'proof_uploaded_at' => now(),
        'notes' => 'Valid proof uploaded for review.',
    ])->save();

    $this->actingAs($admin)
        ->get(route('dashboard.operations', ['investment_status' => 'pending', 'investment_risk_state' => 'high_risk']))
        ->assertOk()
        ->assertSee('RISK-HIGH-001')
        ->assertSee('High risk only: 1')
        ->assertDontSee('RISK-CLEAN-002');
});

test('operations queue shows unlocked reward cap badges for strong investors', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $strongInvestor = createStrongRewardCapInvestor();
    pendingInvestmentOrderFor($strongInvestor, 'CAP-BADGE-001', 'pending');

    $this->actingAs($admin)
        ->get(route('dashboard.operations', ['investment_status' => 'pending', 'investment_search' => 'CAP-BADGE']))
        ->assertOk()
        ->assertSee('Growth 500')
        ->assertSee('6% cap')
        ->assertSee('7% cap');
});

test('admin operations page shows recent admin activity logs', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    AdminActivityLog::create([
        'admin_user_id' => $admin->id,
        'action' => 'investment.approve',
        'summary' => 'Approved investment order #501',
        'subject_type' => InvestmentOrder::class,
        'subject_id' => 501,
        'details' => [
            'user_id' => 99,
            'package' => 'growth-500',
            'amount' => 500,
        ],
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest test browser',
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard.operations'))
        ->assertOk()
        ->assertViewHas('adminActivityLogs', function ($logs) {
            return $logs->contains(fn ($log) => $log->summary === 'Approved investment order #501'
                && $log->action === 'investment.approve'
                && $log->ip_address === '127.0.0.1');
        });
});

test('admin operations page shows investor payout board with countdown and withdrawable balance', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $investor = User::factory()->create([
        'email_verified_at' => now(),
        'name' => 'Payout Countdown Investor',
        'email' => 'payout-countdown@example.test',
    ]);

    $package = InvestmentPackage::query()->where('slug', 'growth-500')->firstOrFail();
    $miner = Miner::query()->where('slug', 'alpha-one')->firstOrFail();

    UserInvestment::query()->create([
        'user_id' => $investor->id,
        'miner_id' => $miner->id,
        'package_id' => $package->id,
        'amount' => 500,
        'shares_owned' => 5,
        'monthly_return_rate' => 0.0850,
        'level_bonus_rate' => 0,
        'team_bonus_rate' => 0,
        'status' => 'active',
        'subscribed_at' => now()->subDays(10),
    ]);

    \App\Models\Earning::query()->create([
        'user_id' => $investor->id,
        'investment_id' => null,
        'earned_on' => now()->toDateString(),
        'amount' => 120,
        'source' => 'mining_return',
        'status' => 'available',
        'notes' => 'Available monthly earning.',
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard.operations'))
        ->assertOk()
        ->assertSee('Investor payout board')
        ->assertSee('Payout Countdown Investor')
        ->assertSee('withdrawable now', false)
        ->assertSee('Earnings only')
        ->assertSee('Projected next payout');
});

test('admin operations page shows secondary market activity board', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $seller = User::factory()->create([
        'email_verified_at' => now(),
        'name' => 'Market Seller',
    ]);

    $buyer = User::factory()->create([
        'email_verified_at' => now(),
        'name' => 'Market Buyer',
    ]);

    $miner = Miner::query()->create([
        'name' => 'Market Ready Miner',
        'slug' => 'market-ready-miner',
        'share_price' => 100,
        'total_shares' => 1000,
        'shares_sold' => 1000,
        'status' => 'secondary_market_open',
        'secondary_market_fee_percent' => 5,
        'secondary_market_opened_at' => now()->subDay(),
    ]);

    $holding = ShareHolding::query()->create([
        'user_id' => $seller->id,
        'miner_id' => $miner->id,
        'quantity' => 20,
        'locked_quantity' => 4,
        'avg_buy_price' => 100,
        'status' => 'active',
        'last_acquired_at' => now()->subDays(5),
    ]);

    $listing = ShareListing::query()->create([
        'seller_user_id' => $seller->id,
        'miner_id' => $miner->id,
        'share_holding_id' => $holding->id,
        'quantity' => 4,
        'remaining_quantity' => 2,
        'price_per_share' => 160,
        'total_price' => 640,
        'platform_fee_percent' => 5,
        'platform_fee_amount' => 16,
        'seller_net_amount' => 304,
        'status' => 'partially_sold',
        'listed_at' => now()->subHour(),
    ]);

    $sale = ShareSale::query()->create([
        'listing_id' => $listing->id,
        'miner_id' => $miner->id,
        'seller_user_id' => $seller->id,
        'buyer_user_id' => $buyer->id,
        'quantity' => 2,
        'price_per_share' => 160,
        'gross_amount' => 320,
        'platform_fee_percent' => 5,
        'platform_fee_amount' => 16,
        'seller_net_amount' => 304,
        'status' => 'completed',
        'completed_at' => now()->subMinutes(30),
    ]);

    ShareMarketTransaction::query()->create([
        'user_id' => null,
        'type' => 'platform_fee',
        'reference_type' => ShareSale::class,
        'reference_id' => $sale->id,
        'amount' => 16,
        'currency' => 'USD',
        'status' => 'completed',
        'meta' => ['miner_id' => $miner->id],
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard.operations'))
        ->assertOk()
        ->assertSee('Secondary market operations')
        ->assertSee('Market Ready Miner')
        ->assertSee('Market Seller')
        ->assertSee('Market Buyer')
        ->assertSee('Completed sales')
        ->assertSee('Platform fees')
        ->assertSee('16.00');
});

test('admin can filter and cancel secondary market listings from operations page', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $seller = User::factory()->create([
        'email_verified_at' => now(),
        'name' => 'Ops Market Seller',
    ]);

    $openMiner = Miner::query()->create([
        'name' => 'Ops Open Miner',
        'slug' => 'ops-open-miner',
        'share_price' => 100,
        'total_shares' => 1000,
        'shares_sold' => 1000,
        'status' => 'secondary_market_open',
        'secondary_market_fee_percent' => 5,
        'secondary_market_opened_at' => now()->subDay(),
    ]);

    $otherMiner = Miner::query()->create([
        'name' => 'Ops Mature Miner',
        'slug' => 'ops-mature-miner',
        'share_price' => 120,
        'total_shares' => 1000,
        'shares_sold' => 1000,
        'status' => 'mature',
        'secondary_market_fee_percent' => 5,
        'matured_at' => now()->subDay(),
    ]);

    $openHolding = ShareHolding::query()->create([
        'user_id' => $seller->id,
        'miner_id' => $openMiner->id,
        'quantity' => 15,
        'locked_quantity' => 3,
        'avg_buy_price' => 100,
        'status' => 'active',
        'last_acquired_at' => now()->subDays(3),
    ]);

    $otherHolding = ShareHolding::query()->create([
        'user_id' => $seller->id,
        'miner_id' => $otherMiner->id,
        'quantity' => 15,
        'locked_quantity' => 2,
        'avg_buy_price' => 120,
        'status' => 'active',
        'last_acquired_at' => now()->subDays(3),
    ]);

    $activeListing = ShareListing::query()->create([
        'seller_user_id' => $seller->id,
        'miner_id' => $openMiner->id,
        'share_holding_id' => $openHolding->id,
        'quantity' => 3,
        'remaining_quantity' => 3,
        'price_per_share' => 150,
        'total_price' => 450,
        'platform_fee_percent' => 5,
        'platform_fee_amount' => 22.5,
        'seller_net_amount' => 427.5,
        'status' => 'active',
        'listed_at' => now()->subMinutes(10),
    ]);

    ShareListing::query()->create([
        'seller_user_id' => $seller->id,
        'miner_id' => $otherMiner->id,
        'share_holding_id' => $otherHolding->id,
        'quantity' => 2,
        'remaining_quantity' => 0,
        'price_per_share' => 155,
        'total_price' => 310,
        'platform_fee_percent' => 5,
        'platform_fee_amount' => 15.5,
        'seller_net_amount' => 294.5,
        'status' => 'sold',
        'listed_at' => now()->subHour(),
        'sold_at' => now()->subMinutes(30),
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard.operations', [
            'market_miner_id' => $openMiner->id,
            'market_listing_status' => 'active',
            'market_sale_status' => 'all',
        ]))
        ->assertOk()
        ->assertSee('Ops Open Miner')
        ->assertSee('Cancel listing')
        ->assertViewHas('secondaryMarketListings', function ($listings) use ($activeListing, $openMiner) {
            return $listings->count() === 1
                && (int) $listings->first()->id === (int) $activeListing->id
                && (int) $listings->first()->miner_id === (int) $openMiner->id;
        });

    $response = $this->actingAs($admin)
        ->post(route('dashboard.operations.share-market.listings.cancel', $activeListing));

    $response->assertRedirect(route('dashboard.operations'));

    $activeListing->refresh();
    $openHolding->refresh();

    expect($activeListing->status)->toBe('cancelled')
        ->and($activeListing->remaining_quantity)->toBe(0)
        ->and($openHolding->locked_quantity)->toBe(0);
});

test('admin operations page can focus stale listings and highest fee trades', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $seller = User::factory()->create([
        'email_verified_at' => now(),
        'name' => 'Filter Market Seller',
    ]);

    $buyer = User::factory()->create([
        'email_verified_at' => now(),
        'name' => 'Filter Market Buyer',
    ]);

    $staleMiner = Miner::query()->create([
        'name' => 'Stale Market Miner',
        'slug' => 'stale-market-miner',
        'share_price' => 110,
        'total_shares' => 1000,
        'shares_sold' => 1000,
        'status' => 'secondary_market_open',
        'secondary_market_fee_percent' => 5,
        'secondary_market_opened_at' => now()->subDays(10),
    ]);

    $hotMiner = Miner::query()->create([
        'name' => 'Hot Market Miner',
        'slug' => 'hot-market-miner',
        'share_price' => 140,
        'total_shares' => 1000,
        'shares_sold' => 1000,
        'status' => 'secondary_market_open',
        'secondary_market_fee_percent' => 5,
        'secondary_market_opened_at' => now()->subDays(12),
    ]);

    $staleHolding = ShareHolding::query()->create([
        'user_id' => $seller->id,
        'miner_id' => $staleMiner->id,
        'quantity' => 20,
        'locked_quantity' => 4,
        'avg_buy_price' => 110,
        'status' => 'active',
        'last_acquired_at' => now()->subDays(10),
    ]);

    $hotHolding = ShareHolding::query()->create([
        'user_id' => $seller->id,
        'miner_id' => $hotMiner->id,
        'quantity' => 20,
        'locked_quantity' => 5,
        'avg_buy_price' => 140,
        'status' => 'active',
        'last_acquired_at' => now()->subDays(12),
    ]);

    $staleListing = ShareListing::query()->create([
        'seller_user_id' => $seller->id,
        'miner_id' => $staleMiner->id,
        'share_holding_id' => $staleHolding->id,
        'quantity' => 4,
        'remaining_quantity' => 4,
        'price_per_share' => 170,
        'total_price' => 680,
        'platform_fee_percent' => 5,
        'platform_fee_amount' => 34,
        'seller_net_amount' => 646,
        'status' => 'active',
        'listed_at' => now()->subDays(5),
    ]);

    ShareListing::query()->create([
        'seller_user_id' => $seller->id,
        'miner_id' => $hotMiner->id,
        'share_holding_id' => $hotHolding->id,
        'quantity' => 5,
        'remaining_quantity' => 3,
        'price_per_share' => 190,
        'total_price' => 950,
        'platform_fee_percent' => 5,
        'platform_fee_amount' => 47.5,
        'seller_net_amount' => 902.5,
        'status' => 'partially_sold',
        'listed_at' => now()->subHours(5),
    ]);

    $lowerFeeListing = ShareListing::query()->create([
        'seller_user_id' => $seller->id,
        'miner_id' => $staleMiner->id,
        'share_holding_id' => $staleHolding->id,
        'quantity' => 2,
        'remaining_quantity' => 0,
        'price_per_share' => 155,
        'total_price' => 310,
        'platform_fee_percent' => 5,
        'platform_fee_amount' => 15.5,
        'seller_net_amount' => 294.5,
        'status' => 'sold',
        'listed_at' => now()->subDays(4),
        'sold_at' => now()->subHours(8),
    ]);

    $higherFeeListing = ShareListing::query()->create([
        'seller_user_id' => $seller->id,
        'miner_id' => $hotMiner->id,
        'share_holding_id' => $hotHolding->id,
        'quantity' => 3,
        'remaining_quantity' => 0,
        'price_per_share' => 220,
        'total_price' => 660,
        'platform_fee_percent' => 5,
        'platform_fee_amount' => 33,
        'seller_net_amount' => 627,
        'status' => 'sold',
        'listed_at' => now()->subDays(2),
        'sold_at' => now()->subHours(2),
    ]);

    $lowerFeeSale = ShareSale::query()->create([
        'listing_id' => $lowerFeeListing->id,
        'miner_id' => $staleMiner->id,
        'seller_user_id' => $seller->id,
        'buyer_user_id' => $buyer->id,
        'quantity' => 2,
        'price_per_share' => 155,
        'gross_amount' => 310,
        'platform_fee_percent' => 5,
        'platform_fee_amount' => 15.5,
        'seller_net_amount' => 294.5,
        'status' => 'completed',
        'completed_at' => now()->subHours(8),
    ]);

    $higherFeeSale = ShareSale::query()->create([
        'listing_id' => $higherFeeListing->id,
        'miner_id' => $hotMiner->id,
        'seller_user_id' => $seller->id,
        'buyer_user_id' => $buyer->id,
        'quantity' => 3,
        'price_per_share' => 220,
        'gross_amount' => 660,
        'platform_fee_percent' => 5,
        'platform_fee_amount' => 33,
        'seller_net_amount' => 627,
        'status' => 'completed',
        'completed_at' => now()->subHours(2),
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard.operations', [
            'market_listing_focus' => 'stale_active',
            'market_sale_status' => 'completed',
            'market_sale_sort' => 'highest_fee',
        ]))
        ->assertOk()
        ->assertSee('Per-miner market activity')
        ->assertSee('Stale active listings (3+ days)')
        ->assertViewHas('secondaryMarketFilters', function (array $filters) {
            return $filters['listing_focus'] === 'stale_active'
                && $filters['sale_sort'] === 'highest_fee';
        })
        ->assertViewHas('secondaryMarketListings', function ($listings) use ($staleListing) {
            return $listings->count() === 1
                && (int) $listings->first()->id === (int) $staleListing->id;
        })
        ->assertViewHas('secondaryMarketSales', function ($sales) use ($higherFeeSale, $lowerFeeSale) {
            return $sales->count() === 2
                && (int) $sales->first()->id === (int) $higherFeeSale->id
                && (int) $sales->last()->id === (int) $lowerFeeSale->id;
        })
        ->assertViewHas('secondaryMarketMinerActivity', function ($rows) use ($staleMiner, $hotMiner) {
            return $rows->contains(fn (array $row) => (int) $row['miner']->id === (int) $staleMiner->id && (int) $row['active_listings_count'] === 1)
                && $rows->contains(fn (array $row) => (int) $row['miner']->id === (int) $hotMiner->id && (float) $row['platform_fee_total'] >= 33);
        });
});

test('admin operations page can filter admin activity logs by action', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    AdminActivityLog::create([
        'admin_user_id' => $admin->id,
        'action' => 'investment.reject',
        'summary' => 'Rejected investment order #700',
    ]);

    AdminActivityLog::create([
        'admin_user_id' => $admin->id,
        'action' => 'payout.pay',
        'summary' => 'Marked payout request #901 as paid',
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard.operations', ['activity_action' => 'payout.pay']))
        ->assertOk()
        ->assertViewHas('activityFilters', fn ($filters) => ($filters['action'] ?? null) === 'payout.pay')
        ->assertViewHas('adminActivityLogs', function ($logs) {
            return $logs->count() === 1
                && $logs->first()->summary === 'Marked payout request #901 as paid'
                && $logs->first()->action === 'payout.pay';
        });
});
