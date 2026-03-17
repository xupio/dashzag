<?php

use App\Models\Earning;
use App\Models\FriendInvitation;
use App\Models\InvestmentOrder;
use App\Models\User;
use App\Notifications\ActivityFeedNotification;
use App\Support\MiningPlatform;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    MiningPlatform::ensureDefaults();
});

test('verified user can view network page', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard.network'));

    $response->assertOk();
    $response->assertSee('My Network');
    $response->assertSee('Team power leaderboard');
    $response->assertSee('Monthly branch champion');
    $response->assertSee('Open Hall of Fame');
});

test('network page shows direct team and team rewards', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $inviter = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    FriendInvitation::create([
        'user_id' => $inviter->id,
        'name' => 'Buyer Friend',
        'email' => 'networkbuyer@example.com',
        'verified_at' => now(),
        'registered_at' => now(),
    ]);

    $buyer = User::factory()->create([
        'name' => 'Buyer Friend',
        'email' => 'networkbuyer@example.com',
        'email_verified_at' => now(),
        'account_type' => 'user',
        'sponsor_user_id' => $inviter->id,
    ]);

    $this->actingAs($buyer)->post(route('dashboard.buy-shares.subscribe'), [
        'package' => 'growth-500',
        'payment_method' => 'btc_transfer',
        'payment_reference' => 'TX-NETWORK-001',
    ])->assertRedirect(route('dashboard.buy-shares', ['miner' => 'alpha-one']));

    $order = InvestmentOrder::query()->firstOrFail();

    $this->actingAs($buyer)->post(route('dashboard.buy-shares.proof', $order), [
        'payment_proof' => UploadedFile::fake()->create('network-proof.pdf', 120, 'application/pdf'),
    ])->assertRedirect(route('dashboard.buy-shares', ['miner' => 'alpha-one']));

    $this->actingAs($admin)
        ->post(route('dashboard.operations.investment-orders.approve', $order->fresh()))
        ->assertRedirect(route('dashboard.operations'));

    $response = $this->actingAs($inviter)->get(route('dashboard.network'));

    $response->assertOk();
    $response->assertSee('Buyer Friend');
    $response->assertSee('Level 1 Team Bonus');
    $response->assertSee('Level 1');
    $response->assertSee('Direct team');
    $response->assertSee('$15.00');
});

test('invitation pipeline marks invited email as active investor even without sponsor link', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $inviter = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    FriendInvitation::create([
        'user_id' => $inviter->id,
        'name' => 'Legacy Investor',
        'email' => 'legacy-investor@example.com',
        'verified_at' => now(),
        'registered_at' => now(),
    ]);

    $legacyInvestor = User::factory()->create([
        'name' => 'Legacy Investor',
        'email' => 'legacy-investor@example.com',
        'email_verified_at' => now(),
        'account_type' => 'user',
        'sponsor_user_id' => null,
    ]);

    $this->actingAs($legacyInvestor)->post(route('dashboard.buy-shares.subscribe'), [
        'package' => 'growth-500',
        'payment_method' => 'btc_transfer',
        'payment_reference' => 'TX-LEGACY-001',
    ])->assertRedirect(route('dashboard.buy-shares', ['miner' => 'alpha-one']));

    $order = InvestmentOrder::query()->latest('id')->firstOrFail();

    $this->actingAs($legacyInvestor)->post(route('dashboard.buy-shares.proof', $order), [
        'payment_proof' => UploadedFile::fake()->create('legacy-proof.pdf', 120, 'application/pdf'),
    ])->assertRedirect(route('dashboard.buy-shares', ['miner' => 'alpha-one']));

    $this->actingAs($admin)
        ->post(route('dashboard.operations.investment-orders.approve', $order->fresh()))
        ->assertRedirect(route('dashboard.operations'));

    $response = $this->actingAs($inviter)->get(route('dashboard.network'));

    $response->assertOk();
    $response->assertSee('Legacy Investor');
    $response->assertSee('Yes');
});

test('network page can filter reward ledger and invitation pipeline', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    FriendInvitation::create([
        'user_id' => $user->id,
        'name' => 'Direct Investor',
        'email' => 'direct-investor@example.com',
        'verified_at' => now(),
        'registered_at' => now(),
    ]);

    FriendInvitation::create([
        'user_id' => $user->id,
        'name' => 'Pending Friend',
        'email' => 'pending-friend@example.com',
    ]);

    Earning::create([
        'user_id' => $user->id,
        'investment_id' => null,
        'earned_on' => now()->toDateString(),
        'amount' => 25,
        'source' => 'referral_registration',
        'status' => 'available',
        'notes' => 'Direct reward row.',
    ]);

    Earning::create([
        'user_id' => $user->id,
        'investment_id' => null,
        'earned_on' => now()->toDateString(),
        'amount' => 7.5,
        'source' => 'team_level_3_bonus',
        'status' => 'available',
        'notes' => 'MLM reward row.',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard.network', [
        'reward_filter' => 'direct',
        'pipeline_filter' => 'pending',
    ]));

    $response->assertOk();
    $response->assertSee('Direct reward row.');
    $response->assertDontSee('MLM reward row.');
    $response->assertSee('Pending Friend');
    $response->assertDontSee('Direct Investor');
});

test('network page shows unlocked reward cap badges on branch members', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $branchMember = User::factory()->create([
        'email_verified_at' => now(),
        'sponsor_user_id' => $user->id,
        'account_type' => 'shareholder',
    ]);

    $miner = \App\Models\Miner::query()->where('slug', 'alpha-one')->firstOrFail();
    $package = \App\Models\InvestmentPackage::query()->where('slug', 'growth-500')->firstOrFail();

    \App\Models\UserInvestment::query()->create([
        'user_id' => $branchMember->id,
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

    $branchMember->notify(new ActivityFeedNotification([
        'event_key' => 'profile_power_reward_cap',
        'reward_cap_tier' => 'growth',
        'category' => 'milestone',
        'status' => 'success',
        'subject' => 'Growth 500 full reward cap unlocked',
        'message' => 'You unlocked the full 6.00% profile power reward cap for Growth 500.',
    ]));

    $response = $this->actingAs($user)->get(route('dashboard.network'));

    $response->assertOk();
    $response->assertSee('6% cap');
});


