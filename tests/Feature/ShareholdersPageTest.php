<?php

use App\Models\FriendInvitation;
use App\Models\InvestmentPackage;
use App\Models\InvestmentOrder;
use App\Models\Miner;
use App\Models\User;
use App\Models\UserInvestment;
use App\Models\UserLevel;
use App\Support\MiningPlatform;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    MiningPlatform::ensureDefaults();
});

function approveShareholderOrder($test, User $admin, User $user, string $package, string $reference): void
{
    $test->actingAs($user)->post(route('dashboard.buy-shares.subscribe'), [
        'package' => $package,
        'payment_method' => 'btc_transfer',
        'payment_reference' => $reference,
    ])->assertStatus(302);

    $order = InvestmentOrder::query()->latest('id')->firstOrFail();

    $test->actingAs($user)->post(route('dashboard.buy-shares.proof', $order), [
        'payment_proof' => UploadedFile::fake()->create($reference.'.pdf', 120, 'application/pdf'),
    ])->assertStatus(302);

    $test->actingAs($admin)
        ->post(route('dashboard.operations.investment-orders.approve', $order->fresh()))
        ->assertRedirect(route('dashboard.operations'));
}

function createStrongRewardCapShareholder(string $packageSlug = 'growth-500'): User
{
    $miner = Miner::query()->where('slug', 'alpha-one')->firstOrFail();
    $basicPackage = InvestmentPackage::query()->where('slug', 'starter-100')->firstOrFail();
    $selectedPackage = InvestmentPackage::query()->where('slug', $packageSlug)->firstOrFail();
    $scalePackage = InvestmentPackage::query()->where('slug', 'scale-1000')->firstOrFail();
    $platinum = UserLevel::query()->where('slug', 'platinum')->firstOrFail();

    $user = User::factory()->create([
        'name' => 'Reward Cap Investor',
        'email' => 'reward-cap@example.com',
        'email_verified_at' => now(),
        'user_level_id' => $platinum->id,
        'account_type' => 'shareholder',
    ]);

    foreach (range(1, 10) as $index) {
        FriendInvitation::query()->create([
            'user_id' => $user->id,
            'name' => 'Invite '.$index,
            'email' => 'cap-invite-'.$index.'@example.test',
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
        'package_id' => $selectedPackage->id,
        'amount' => (float) $selectedPackage->price,
        'shares_owned' => max((int) $selectedPackage->share_quantity, 1),
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

test('admin can filter shareholders by package and search', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $growthInvestor = User::factory()->create([
        'name' => 'Growth Investor',
        'email_verified_at' => now(),
    ]);

    $basicInvestor = User::factory()->create([
        'name' => 'Basic Investor',
        'email_verified_at' => now(),
    ]);

    approveShareholderOrder($this, $admin, $growthInvestor, 'growth-500', 'TX-SH-GROWTH');
    approveShareholderOrder($this, $admin, $basicInvestor, 'momentum-300', 'TX-SH-MOMENTUM');

    $response = $this->actingAs($admin)->get(route('dashboard.shareholders', [
        'package' => 'growth-500',
        'search' => 'Growth Investor',
    ]));

    $response->assertOk();
    $response->assertSee('Shareholders');
    $response->assertSee('Status breakdown');
    $response->assertSee('Miner distribution');
    $response->assertSee('Growth Investor');
    $response->assertSee('Growth 500');
    $response->assertDontSee('Basic Investor');
});

test('admin can filter shareholders by unlocked reward cap', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $rewardCapInvestor = createStrongRewardCapShareholder('growth-500');

    $basicInvestor = User::factory()->create([
        'name' => 'Basic Investor',
        'email' => 'basic-filter@example.com',
        'email_verified_at' => now(),
    ]);

    $miner = Miner::query()->where('slug', 'alpha-one')->firstOrFail();
    $basicPackage = InvestmentPackage::query()->where('slug', 'starter-100')->firstOrFail();

    UserInvestment::query()->create([
        'user_id' => $basicInvestor->id,
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

    $response = $this->actingAs($admin)->get(route('dashboard.shareholders', [
        'reward_cap' => 'growth',
    ]));

    $response->assertOk();
    $response->assertSee($rewardCapInvestor->name);
    $response->assertSee('6% cap');
    $response->assertDontSee('basic-filter@example.com');
});

