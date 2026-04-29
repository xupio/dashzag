<?php

use App\Models\Miner;
use App\Models\MinerStatusHistory;
use App\Models\ShareHolding;
use App\Models\User;
use App\Services\ShareMarketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

it('advances sold out miners into mature and secondary market open when the maturity window passes', function () {
    $miner = Miner::query()->create([
        'name' => 'Maturity Miner',
        'slug' => 'maturity-miner',
        'share_price' => 100,
        'total_shares' => 100,
        'shares_sold' => 100,
        'status' => 'sold_out',
        'maturity_days' => 30,
        'sold_out_at' => now()->subDays(31),
    ]);

    Artisan::call('miners:sync-lifecycle');

    $miner->refresh();

    expect($miner->status)->toBe('secondary_market_open')
        ->and($miner->matured_at)->not->toBeNull()
        ->and($miner->secondary_market_opened_at)->not->toBeNull();

    expect(MinerStatusHistory::query()
        ->where('miner_id', $miner->id)
        ->where('old_status', 'sold_out')
        ->where('new_status', 'mature')
        ->exists())->toBeTrue();

    expect(MinerStatusHistory::query()
        ->where('miner_id', $miner->id)
        ->where('old_status', 'mature')
        ->where('new_status', 'secondary_market_open')
        ->exists())->toBeTrue();
});

it('does not advance sold out miners before the maturity window passes', function () {
    $miner = Miner::query()->create([
        'name' => 'Not Yet Mature Miner',
        'slug' => 'not-yet-mature-miner',
        'share_price' => 100,
        'total_shares' => 100,
        'shares_sold' => 100,
        'status' => 'sold_out',
        'maturity_days' => 30,
        'sold_out_at' => now()->subDays(5),
    ]);

    Artisan::call('miners:sync-lifecycle');

    expect($miner->fresh()->status)->toBe('sold_out')
        ->and($miner->fresh()->matured_at)->toBeNull()
        ->and($miner->fresh()->secondary_market_opened_at)->toBeNull();
});

it('allows listings only after the secondary market is open', function () {
    $seller = User::factory()->create();
    $miner = Miner::query()->create([
        'name' => 'Listing Gate Miner',
        'slug' => 'listing-gate-miner',
        'share_price' => 100,
        'total_shares' => 100,
        'shares_sold' => 100,
        'status' => 'sold_out',
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

    expect(fn () => $service->createListing($seller, $miner, 2, 150))
        ->toThrow(InvalidArgumentException::class, 'This miner is not open for secondary market listings yet.');
});
