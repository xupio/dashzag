<?php

use App\Models\Earning;
use App\Models\Miner;
use App\Models\ShareHolding;
use App\Models\ShareListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows miner lifecycle and market readiness on the share market page', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $tradableMiner = Miner::query()->create([
        'name' => 'Tradable Miner',
        'slug' => 'tradable-miner',
        'share_price' => 120,
        'total_shares' => 100,
        'shares_sold' => 100,
        'status' => 'secondary_market_open',
        'secondary_market_fee_percent' => 5,
        'secondary_market_opened_at' => now()->subDay(),
        'matured_at' => now()->subDays(2),
    ]);

    $maturingMiner = Miner::query()->create([
        'name' => 'Maturing Miner',
        'slug' => 'maturing-miner',
        'share_price' => 100,
        'total_shares' => 100,
        'shares_sold' => 100,
        'status' => 'sold_out',
        'maturity_days' => 30,
        'sold_out_at' => now()->subDays(10),
    ]);

    ShareHolding::query()->create([
        'user_id' => $user->id,
        'miner_id' => $tradableMiner->id,
        'quantity' => 8,
        'locked_quantity' => 2,
        'avg_buy_price' => 120,
        'status' => 'active',
        'last_acquired_at' => now(),
    ]);

    ShareListing::query()->create([
        'seller_user_id' => $user->id,
        'miner_id' => $tradableMiner->id,
        'share_holding_id' => ShareHolding::query()->first()->id,
        'quantity' => 2,
        'remaining_quantity' => 2,
        'price_per_share' => 150,
        'total_price' => 300,
        'platform_fee_percent' => 5,
        'platform_fee_amount' => 15,
        'seller_net_amount' => 285,
        'status' => 'active',
        'listed_at' => now(),
    ]);

    Earning::query()->create([
        'user_id' => $user->id,
        'investment_id' => null,
        'payout_request_id' => null,
        'earned_on' => now()->subDay()->toDateString(),
        'amount' => 450,
        'source' => 'mining_return',
        'status' => 'available',
        'notes' => 'Wallet funding for market view.',
    ]);

    $response = $this->actingAs($user)->get(route('share-market.index'));

    $response->assertOk();
    $response->assertSee('Miner Market Overview');
    $response->assertSee('Tradable Miner');
    $response->assertSee('Maturing Miner');
    $response->assertSee('Tradable');
    $response->assertSee('Open');
    $response->assertSee('Opens after maturity');
    $response->assertSee('Listings are available only for miners whose secondary market is already open.');
    $response->assertSee('Fee');
    $response->assertSee('5.00%');
    $response->assertSee('Available wallet balance');
    $response->assertSee('Buys use available wallet earnings only');
});
