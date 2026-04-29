<?php

use App\Models\InvestmentOrder;
use App\Models\InvestmentPackage;
use App\Models\Miner;
use App\Models\MinerStatusHistory;
use App\Models\ShareHolding;
use App\Models\User;
use App\Models\UserInvestment;
use App\Support\MiningPlatform;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    Notification::fake();
});

it('syncs share holdings and miner shares sold from active investments', function () {
    $user = User::factory()->create();
    $miner = Miner::query()->create([
        'name' => 'Miner Sync Alpha',
        'slug' => 'miner-sync-alpha',
        'share_price' => 100,
        'total_shares' => 1000,
        'shares_sold' => 0,
        'status' => 'open',
    ]);

    $package = InvestmentPackage::query()->create([
        'miner_id' => $miner->id,
        'name' => 'Growth 500',
        'slug' => 'growth-500-sync',
        'price' => 500,
        'shares_count' => 5,
        'units_limit' => 1,
        'monthly_return_rate' => 0.085,
        'display_order' => 1,
        'is_active' => true,
    ]);

    UserInvestment::query()->create([
        'user_id' => $user->id,
        'miner_id' => $miner->id,
        'package_id' => $package->id,
        'amount' => 500,
        'shares_owned' => 5,
        'monthly_return_rate' => 0.085,
        'level_bonus_rate' => 0,
        'team_bonus_rate' => 0,
        'status' => 'active',
        'subscribed_at' => now()->subDay(),
    ]);

    $secondInvestment = UserInvestment::query()->create([
        'user_id' => $user->id,
        'miner_id' => $miner->id,
        'package_id' => $package->id,
        'amount' => 1000,
        'shares_owned' => 10,
        'monthly_return_rate' => 0.09,
        'level_bonus_rate' => 0,
        'team_bonus_rate' => 0,
        'status' => 'active',
        'subscribed_at' => now(),
    ]);

    $holding = ShareHolding::query()
        ->where('user_id', $user->id)
        ->where('miner_id', $miner->id)
        ->first();

    expect($holding)->not->toBeNull()
        ->and($holding->quantity)->toBe(15)
        ->and((float) $holding->avg_buy_price)->toBe(100.0);

    expect($miner->fresh()->shares_sold)->toBe(15);

    $secondInvestment->update(['status' => 'cancelled']);

    $holding->refresh();

    expect($holding->quantity)->toBe(5)
        ->and((float) $holding->avg_buy_price)->toBe(100.0);

    expect($miner->fresh()->shares_sold)->toBe(5);
});

it('creates share holdings when an investment order is approved', function () {
    MiningPlatform::ensureDefaults();

    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'user',
    ]);

    $this->actingAs($user)->post(route('dashboard.buy-shares.subscribe'), [
        'package' => 'growth-500',
        'payment_method' => 'btc_transfer',
        'payment_reference' => 'TX-HOLDING-SYNC-001',
    ])->assertRedirect(route('dashboard.buy-shares', ['miner' => 'alpha-one']));

    $order = InvestmentOrder::query()->firstOrFail();

    $this->actingAs($user)->post(route('dashboard.buy-shares.proof', $order), [
        'payment_proof' => UploadedFile::fake()->create('holding-sync-proof.pdf', 120, 'application/pdf'),
    ])->assertRedirect(route('dashboard.buy-shares', ['miner' => 'alpha-one']));

    $this->actingAs($admin)
        ->post(route('dashboard.operations.investment-orders.approve', $order->fresh()))
        ->assertRedirect(route('dashboard.operations'));

    $approvedOrder = $order->fresh(['miner']);
    $holding = ShareHolding::query()
        ->where('user_id', $user->id)
        ->where('miner_id', $approvedOrder->miner_id)
        ->first();

    expect($holding)->not->toBeNull()
        ->and($holding->quantity)->toBe((int) $approvedOrder->shares_owned)
        ->and((float) $holding->avg_buy_price)->toBe(
            round((float) $approvedOrder->amount / max(1, (int) $approvedOrder->shares_owned), 2)
        )
        ->and($holding->status)->toBe('active');

    expect($approvedOrder->miner->fresh()->shares_sold)->toBe((int) $approvedOrder->shares_owned);
});

it('moves a miner to nearly full and sold out based on active shares sold', function () {
    $user = User::factory()->create();
    $miner = Miner::query()->create([
        'name' => 'Miner Lifecycle Alpha',
        'slug' => 'miner-lifecycle-alpha',
        'share_price' => 100,
        'total_shares' => 10,
        'shares_sold' => 0,
        'near_capacity_threshold_percent' => 90,
        'status' => 'open',
    ]);

    $package = InvestmentPackage::query()->create([
        'miner_id' => $miner->id,
        'name' => 'Lifecycle Package',
        'slug' => 'lifecycle-package',
        'price' => 900,
        'shares_count' => 9,
        'units_limit' => 1,
        'monthly_return_rate' => 0.085,
        'display_order' => 1,
        'is_active' => true,
    ]);

    $firstInvestment = UserInvestment::query()->create([
        'user_id' => $user->id,
        'miner_id' => $miner->id,
        'package_id' => $package->id,
        'amount' => 900,
        'shares_owned' => 9,
        'monthly_return_rate' => 0.085,
        'level_bonus_rate' => 0,
        'team_bonus_rate' => 0,
        'status' => 'active',
        'subscribed_at' => now(),
    ]);

    expect($miner->fresh()->status)->toBe('nearly_full')
        ->and($miner->fresh()->shares_sold)->toBe(9);

    $secondPackage = InvestmentPackage::query()->create([
        'miner_id' => $miner->id,
        'name' => 'Final Share Package',
        'slug' => 'final-share-package',
        'price' => 100,
        'shares_count' => 1,
        'units_limit' => 1,
        'monthly_return_rate' => 0.085,
        'display_order' => 2,
        'is_active' => true,
    ]);

    UserInvestment::query()->create([
        'user_id' => $user->id,
        'miner_id' => $miner->id,
        'package_id' => $secondPackage->id,
        'amount' => 100,
        'shares_owned' => 1,
        'monthly_return_rate' => 0.085,
        'level_bonus_rate' => 0,
        'team_bonus_rate' => 0,
        'status' => 'active',
        'subscribed_at' => now(),
    ]);

    $miner->refresh();

    expect($miner->status)->toBe('sold_out')
        ->and($miner->shares_sold)->toBe(10)
        ->and($miner->sold_out_at)->not->toBeNull();

    expect(MinerStatusHistory::query()
        ->where('miner_id', $miner->id)
        ->where('old_status', 'open')
        ->where('new_status', 'nearly_full')
        ->exists())->toBeTrue();

    expect(MinerStatusHistory::query()
        ->where('miner_id', $miner->id)
        ->where('old_status', 'nearly_full')
        ->where('new_status', 'sold_out')
        ->exists())->toBeTrue();

    $firstInvestment->update(['status' => 'cancelled']);

    $miner->refresh();

    expect($miner->status)->toBe('open')
        ->and($miner->shares_sold)->toBe(1)
        ->and($miner->sold_out_at)->toBeNull();
});
