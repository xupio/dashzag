<?php

use App\Models\Earning;
use App\Models\Miner;
use App\Models\ShareHolding;
use App\Models\ShareListing;
use App\Models\ShareMarketTransaction;
use App\Models\ShareSale;
use App\Models\User;
use App\Notifications\ActivityFeedNotification;
use App\Services\ShareMarketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('creates a listing and locks seller shares', function () {
    $seller = User::factory()->create();
    $miner = Miner::query()->create([
        'name' => 'Miner Alpha',
        'slug' => 'miner-alpha',
        'share_price' => 100,
        'total_shares' => 1000,
        'shares_sold' => 100,
        'secondary_market_fee_percent' => 5,
        'status' => 'secondary_market_open',
    ]);

    $holding = ShareHolding::query()->create([
        'user_id' => $seller->id,
        'miner_id' => $miner->id,
        'quantity' => 20,
        'locked_quantity' => 0,
        'avg_buy_price' => 100,
        'status' => 'active',
    ]);

    $listing = app(ShareMarketService::class)->createListing($seller, $miner, 5, 150);

    expect($listing)->toBeInstanceOf(ShareListing::class)
        ->and($listing->quantity)->toBe(5)
        ->and($listing->remaining_quantity)->toBe(5)
        ->and((float) $listing->price_per_share)->toBe(150.0)
        ->and($listing->status)->toBe('active');

    $holding->refresh();

    expect($holding->locked_quantity)->toBe(5)
        ->and($holding->transferable_quantity)->toBe(15);
});

it('cancels a listing and unlocks remaining shares', function () {
    $seller = User::factory()->create();
    $miner = Miner::query()->create([
        'name' => 'Miner Beta',
        'slug' => 'miner-beta',
        'share_price' => 100,
        'total_shares' => 1000,
        'shares_sold' => 100,
        'secondary_market_fee_percent' => 5,
        'status' => 'secondary_market_open',
    ]);

    $holding = ShareHolding::query()->create([
        'user_id' => $seller->id,
        'miner_id' => $miner->id,
        'quantity' => 20,
        'locked_quantity' => 0,
        'avg_buy_price' => 100,
        'status' => 'active',
    ]);

    $service = app(ShareMarketService::class);
    $listing = $service->createListing($seller, $miner, 6, 140);

    $service->cancelListing($listing);

    $holding->refresh();
    $listing->refresh();

    expect($holding->locked_quantity)->toBe(0)
        ->and($listing->status)->toBe('cancelled')
        ->and($listing->remaining_quantity)->toBe(0);
});

it('completes a sale and transfers shares from seller to buyer', function () {
    Notification::fake();

    $seller = User::factory()->create();
    $buyer = User::factory()->create();
    $miner = Miner::query()->create([
        'name' => 'Miner Gamma',
        'slug' => 'miner-gamma',
        'share_price' => 100,
        'total_shares' => 1000,
        'shares_sold' => 300,
        'secondary_market_fee_percent' => 5,
        'status' => 'secondary_market_open',
    ]);

    Earning::query()->create([
        'user_id' => $buyer->id,
        'investment_id' => null,
        'payout_request_id' => null,
        'earned_on' => now()->subDay()->toDateString(),
        'amount' => 600,
        'source' => 'mining_return',
        'status' => 'available',
        'notes' => 'Seeded buyer wallet balance.',
    ]);

    $sellerHolding = ShareHolding::query()->create([
        'user_id' => $seller->id,
        'miner_id' => $miner->id,
        'quantity' => 10,
        'locked_quantity' => 0,
        'avg_buy_price' => 100,
        'status' => 'active',
    ]);

    $service = app(ShareMarketService::class);
    $listing = $service->createListing($seller, $miner, 4, 150);
    $sale = $service->completeSale($listing, $buyer, 4);

    $sellerHolding->refresh();
    $buyerHolding = ShareHolding::query()
        ->where('user_id', $buyer->id)
        ->where('miner_id', $miner->id)
        ->first();
    $listing->refresh();

    expect($sale)->toBeInstanceOf(ShareSale::class)
        ->and($sale->status)->toBe('completed')
        ->and($sellerHolding->quantity)->toBe(6)
        ->and($sellerHolding->locked_quantity)->toBe(0)
        ->and($buyerHolding)->not->toBeNull()
        ->and($buyerHolding->quantity)->toBe(4)
        ->and((float) $buyerHolding->avg_buy_price)->toBe(150.0)
        ->and($listing->status)->toBe('sold')
        ->and($listing->remaining_quantity)->toBe(0);

    Notification::assertSentTo($buyer, ActivityFeedNotification::class, function (ActivityFeedNotification $notification, array $channels) use ($buyer) {
        $payload = $notification->toArray($buyer);

        return in_array('database', $channels, true)
            && $payload['subject'] === 'Secondary market purchase completed'
            && $payload['action_url'] === route('share-market.index');
    });

    Notification::assertSentTo($seller, ActivityFeedNotification::class, function (ActivityFeedNotification $notification, array $channels) use ($seller) {
        $payload = $notification->toArray($seller);

        return in_array('database', $channels, true)
            && $payload['subject'] === 'Secondary market sale completed'
            && $payload['action_url'] === route('share-market.index');
    });
});

it('records buyer, seller, and platform fee transactions on sale', function () {
    $seller = User::factory()->create();
    $buyer = User::factory()->create();
    $miner = Miner::query()->create([
        'name' => 'Miner Delta',
        'slug' => 'miner-delta',
        'share_price' => 100,
        'total_shares' => 1000,
        'shares_sold' => 400,
        'secondary_market_fee_percent' => 5,
        'status' => 'secondary_market_open',
    ]);

    Earning::query()->create([
        'user_id' => $buyer->id,
        'investment_id' => null,
        'payout_request_id' => null,
        'earned_on' => now()->subDay()->toDateString(),
        'amount' => 1000,
        'source' => 'mining_return',
        'status' => 'available',
        'notes' => 'Seeded buyer wallet balance.',
    ]);

    ShareHolding::query()->create([
        'user_id' => $seller->id,
        'miner_id' => $miner->id,
        'quantity' => 10,
        'locked_quantity' => 0,
        'avg_buy_price' => 100,
        'status' => 'active',
    ]);

    $service = app(ShareMarketService::class);
    $listing = $service->createListing($seller, $miner, 2, 200);
    $sale = $service->completeSale($listing, $buyer, 2);

    $transactions = ShareMarketTransaction::query()
        ->where('reference_type', ShareSale::class)
        ->where('reference_id', $sale->id)
        ->orderBy('id')
        ->get();

    $sellerWalletCredit = Earning::query()
        ->where('user_id', $seller->id)
        ->where('source', 'secondary_share_sale')
        ->latest('id')
        ->first();

    expect($transactions)->toHaveCount(3)
        ->and($transactions->pluck('type')->all())->toBe([
            'secondary_share_purchase',
            'secondary_share_sale',
            'platform_fee',
        ])
        ->and((float) $transactions[0]->amount)->toBe(400.0)
        ->and((float) $transactions[1]->amount)->toBe(380.0)
        ->and((float) $transactions[2]->amount)->toBe(20.0)
        ->and($sellerWalletCredit)->not->toBeNull()
        ->and((float) $sellerWalletCredit->amount)->toBe(380.0)
        ->and($sellerWalletCredit->status)->toBe('available');
});

it('rejects a secondary market purchase when the buyer lacks available earnings', function () {
    $seller = User::factory()->create();
    $buyer = User::factory()->create();
    $miner = Miner::query()->create([
        'name' => 'Miner Epsilon',
        'slug' => 'miner-epsilon',
        'share_price' => 100,
        'total_shares' => 1000,
        'shares_sold' => 250,
        'secondary_market_fee_percent' => 5,
        'status' => 'secondary_market_open',
    ]);

    Earning::query()->create([
        'user_id' => $buyer->id,
        'investment_id' => null,
        'payout_request_id' => null,
        'earned_on' => now()->subDays(2)->toDateString(),
        'amount' => 100,
        'source' => 'mining_return',
        'status' => 'available',
        'notes' => 'Not enough to cover the listing.',
    ]);

    ShareHolding::query()->create([
        'user_id' => $seller->id,
        'miner_id' => $miner->id,
        'quantity' => 10,
        'locked_quantity' => 0,
        'avg_buy_price' => 100,
        'status' => 'active',
    ]);

    $service = app(ShareMarketService::class);
    $listing = $service->createListing($seller, $miner, 2, 150);

    expect(fn () => $service->completeSale($listing, $buyer, 2))
        ->toThrow(InvalidArgumentException::class, 'Buyer does not have enough available earnings to fund this secondary market purchase.');

    $listing->refresh();

    expect($listing->status)->toBe('active')
        ->and($listing->remaining_quantity)->toBe(2)
        ->and(ShareSale::query()->count())->toBe(0)
        ->and(ShareMarketTransaction::query()->count())->toBe(0);
});

it('deducts wallet earnings and splits the final earning row when a buyer purchases shares', function () {
    $seller = User::factory()->create();
    $buyer = User::factory()->create();
    $miner = Miner::query()->create([
        'name' => 'Miner Zeta',
        'slug' => 'miner-zeta',
        'share_price' => 100,
        'total_shares' => 1000,
        'shares_sold' => 500,
        'secondary_market_fee_percent' => 5,
        'status' => 'secondary_market_open',
    ]);

    $firstEarning = Earning::query()->create([
        'user_id' => $buyer->id,
        'investment_id' => null,
        'payout_request_id' => null,
        'earned_on' => now()->subDays(3)->toDateString(),
        'amount' => 120,
        'source' => 'mining_return',
        'status' => 'available',
        'notes' => 'Oldest earning row.',
    ]);

    $secondEarning = Earning::query()->create([
        'user_id' => $buyer->id,
        'investment_id' => null,
        'payout_request_id' => null,
        'earned_on' => now()->subDays(2)->toDateString(),
        'amount' => 200,
        'source' => 'mining_return',
        'status' => 'available',
        'notes' => 'Newest earning row.',
    ]);

    ShareHolding::query()->create([
        'user_id' => $seller->id,
        'miner_id' => $miner->id,
        'quantity' => 12,
        'locked_quantity' => 0,
        'avg_buy_price' => 100,
        'status' => 'active',
    ]);

    $service = app(ShareMarketService::class);
    $listing = $service->createListing($seller, $miner, 2, 150);
    $service->completeSale($listing, $buyer, 2);

    $firstEarning->refresh();
    $secondEarning->refresh();

    $spentSplit = Earning::query()
        ->where('user_id', $buyer->id)
        ->where('status', 'market_spent')
        ->orderBy('id')
        ->get();

    expect($firstEarning->status)->toBe('market_spent')
        ->and((float) $firstEarning->amount)->toBe(120.0)
        ->and($firstEarning->notes)->toContain('Allocated to secondary market purchase')
        ->and($secondEarning->status)->toBe('available')
        ->and((float) $secondEarning->amount)->toBe(20.0)
        ->and($spentSplit)->toHaveCount(2)
        ->and((float) $spentSplit[1]->amount)->toBe(180.0)
        ->and($spentSplit[1]->source)->toBe('secondary_share_purchase');
});
