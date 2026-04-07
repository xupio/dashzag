<?php

use App\Models\FriendInvitation;
use App\Models\PayoutRequest;
use App\Models\Earning;
use App\Models\InvestmentPackage;
use App\Models\Miner;
use App\Models\User;
use App\Models\UserInvestment;
use App\Models\UserLevel;
use App\Support\MiningPlatform;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

function createStrongRewardCapUser(string $name = 'Reward Cap User', string $email = 'reward-cap-user@example.com'): User
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
            'email' => 'users-cap-'.$index.'@example.test',
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

test('admin can view users page', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.users'));

    $response->assertOk();
    $response->assertSee('Users');
});

test('admin can update another user role', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'user',
    ]);

    $response = $this->actingAs($admin)->post(route('dashboard.users.role', $user), [
        'role' => 'admin',
    ]);

    $response->assertRedirect(route('dashboard.users'));

    $user->refresh();

    expect($user->role)->toBe('admin');
});

test('non admin user cannot access users page', function () {
    User::factory()->admin()->create(['email_verified_at' => now()]);
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)->get(route('dashboard.users'))->assertForbidden();
});


test('admin can filter users by role and search', function () {
    $admin = User::factory()->admin()->create([
        'name' => 'Main Admin',
        'email_verified_at' => now(),
    ]);

    User::factory()->create([
        'name' => 'Growth Shareholder',
        'email' => 'growth@example.com',
        'email_verified_at' => now(),
        'account_type' => 'shareholder',
        'role' => 'user',
    ]);

    User::factory()->admin()->create([
        'name' => 'Hidden Admin',
        'email' => 'hidden-admin@example.com',
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.users', [
        'role' => 'user',
        'search' => 'Growth',
    ]));

    $response->assertOk();
    $response->assertSee('Growth Shareholder');
    $response->assertSee('Verification');
    $response->assertDontSee('Hidden Admin');
});

test('admin can export filtered users as csv', function () {
    $admin = User::factory()->admin()->create([
        'name' => 'Main Admin',
        'email_verified_at' => now(),
    ]);

    User::factory()->create([
        'name' => 'Growth Shareholder',
        'email' => 'growth@example.com',
        'email_verified_at' => now(),
        'account_type' => 'shareholder',
        'role' => 'user',
    ]);

    User::factory()->admin()->create([
        'name' => 'Hidden Admin',
        'email' => 'hidden-admin@example.com',
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.users.export', [
        'role' => 'user',
        'account_type' => 'shareholder',
        'verification' => 'verified',
        'search' => 'Growth',
    ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('Filter');
    expect($csv)->toContain('Growth');
    expect($csv)->toContain('shareholder');
    expect($csv)->toContain('Name');
    expect($csv)->toContain('Growth Shareholder');
    expect($csv)->not->toContain('Hidden Admin');
});

test('admin can filter users by unlocked reward cap', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $rewardCapUser = createStrongRewardCapUser('Reward Cap User', 'reward-cap-users@example.com');

    User::factory()->create([
        'name' => 'Plain Shareholder',
        'email' => 'plain-shareholder@example.com',
        'email_verified_at' => now(),
        'account_type' => 'shareholder',
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.users', [
        'reward_cap' => 'growth',
    ]));

    $response->assertOk();
    $response->assertSee($rewardCapUser->name);
    $response->assertSee('6% cap');
    $response->assertDontSee('Plain Shareholder');
});

test('admin users page shows investor audit snapshot details', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $miner = Miner::query()->where('slug', 'alpha-one')->firstOrFail();
    $package = InvestmentPackage::query()->where('slug', 'scale-1000')->firstOrFail();

    $user = User::factory()->create([
        'name' => 'Audit Investor',
        'email' => 'audit-investor@example.com',
        'email_verified_at' => now(),
        'account_type' => 'shareholder',
    ]);

    $investment = UserInvestment::query()->create([
        'user_id' => $user->id,
        'miner_id' => $miner->id,
        'package_id' => $package->id,
        'amount' => 1000,
        'shares_owned' => 10,
        'monthly_return_rate' => $package->monthly_return_rate,
        'level_bonus_rate' => 0,
        'team_bonus_rate' => 0,
        'status' => 'active',
        'subscribed_at' => now()->subDays(10),
    ]);

    Earning::query()->create([
        'user_id' => $user->id,
        'investment_id' => $investment->id,
        'earned_on' => now()->toDateString(),
        'amount' => 12.5,
        'source' => 'mining_daily_share',
        'status' => 'available',
        'notes' => 'Available audit earning.',
    ]);

    Earning::query()->create([
        'user_id' => $user->id,
        'investment_id' => $investment->id,
        'earned_on' => now()->toDateString(),
        'amount' => 8.75,
        'source' => 'mining_daily_share',
        'status' => 'pending',
        'notes' => 'Locked audit earning.',
    ]);

    Earning::query()->create([
        'user_id' => $user->id,
        'investment_id' => $investment->id,
        'earned_on' => now()->subDay()->toDateString(),
        'amount' => 45,
        'source' => 'mining_return',
        'status' => 'paid',
        'notes' => 'Paid audit earning.',
    ]);

    PayoutRequest::query()->create([
        'user_id' => $user->id,
        'amount' => 45,
        'fee_amount' => 0,
        'net_amount' => 45,
        'fee_rate' => 0,
        'method' => 'btc_wallet',
        'destination' => 'bc1auditwallet',
        'status' => 'paid',
        'requested_at' => now()->subDay(),
        'approved_at' => now()->subDay(),
        'processed_at' => now()->subHours(12),
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.users', [
        'search' => 'audit-investor@example.com',
    ]));

    $response->assertOk();
    $response->assertSee('Audit snapshot');
    $response->assertSee('Subscribed:');
    $response->assertSee('Next unlock:');
    $response->assertSee('Days left:');
    $response->assertSee('Projected:');
    $response->assertSee('Available:');
    $response->assertSee('Locked:');
    $response->assertSee('Paid:');
    $response->assertSee('KYC:');
    $response->assertSee('$12.50');
    $response->assertSee('$8.75');
    $response->assertSee('$45.00');
    $response->assertSee('Open audit');
    $response->assertSee('Open investor report');
});

test('admin can filter users with locked balances and upcoming unlocks', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $miner = Miner::query()->where('slug', 'alpha-one')->firstOrFail();
    $package = InvestmentPackage::query()->where('slug', 'growth-500')->firstOrFail();

    $lockedUser = User::factory()->create([
        'name' => 'Locked User',
        'email' => 'locked-user@example.com',
        'email_verified_at' => now(),
        'account_type' => 'shareholder',
    ]);

    $lockedInvestment = UserInvestment::query()->create([
        'user_id' => $lockedUser->id,
        'miner_id' => $miner->id,
        'package_id' => $package->id,
        'amount' => 500,
        'shares_owned' => 5,
        'monthly_return_rate' => $package->monthly_return_rate,
        'level_bonus_rate' => 0,
        'team_bonus_rate' => 0,
        'status' => 'active',
        'subscribed_at' => now()->subDays(25),
    ]);

    Earning::query()->create([
        'user_id' => $lockedUser->id,
        'investment_id' => $lockedInvestment->id,
        'earned_on' => now()->toDateString(),
        'amount' => 6.5,
        'source' => 'mining_daily_share',
        'status' => 'pending',
        'notes' => 'Locked test earning.',
    ]);

    $matureUser = User::factory()->create([
        'name' => 'Mature User',
        'email' => 'mature-user@example.com',
        'email_verified_at' => now(),
        'account_type' => 'shareholder',
    ]);

    UserInvestment::query()->create([
        'user_id' => $matureUser->id,
        'miner_id' => $miner->id,
        'package_id' => $package->id,
        'amount' => 500,
        'shares_owned' => 5,
        'monthly_return_rate' => $package->monthly_return_rate,
        'level_bonus_rate' => 0,
        'team_bonus_rate' => 0,
        'status' => 'active',
        'subscribed_at' => now()->subDays(45),
    ]);

    $lockedResponse = $this->actingAs($admin)->get(route('dashboard.users', [
        'audit_filter' => 'locked_balance',
    ]));

    $lockedResponse->assertOk();
    $lockedResponse->assertSee('Locked User');
    $lockedResponse->assertDontSee('Mature User');

    $unlockingSoonResponse = $this->actingAs($admin)->get(route('dashboard.users', [
        'audit_filter' => 'unlocking_soon',
    ]));

    $unlockingSoonResponse->assertOk();
    $unlockingSoonResponse->assertSee('Locked User');
    $unlockingSoonResponse->assertDontSee('Mature User');
});

test('admin users export includes audit columns and respects audit filters', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $miner = Miner::query()->where('slug', 'alpha-one')->firstOrFail();
    $package = InvestmentPackage::query()->where('slug', 'growth-500')->firstOrFail();

    $lockedUser = User::factory()->create([
        'name' => 'Locked Export User',
        'email' => 'locked-export@example.com',
        'email_verified_at' => now(),
        'account_type' => 'shareholder',
    ]);

    $investment = UserInvestment::query()->create([
        'user_id' => $lockedUser->id,
        'miner_id' => $miner->id,
        'package_id' => $package->id,
        'amount' => 500,
        'shares_owned' => 5,
        'monthly_return_rate' => $package->monthly_return_rate,
        'level_bonus_rate' => 0,
        'team_bonus_rate' => 0,
        'status' => 'active',
        'subscribed_at' => now()->subDays(25),
    ]);

    Earning::query()->create([
        'user_id' => $lockedUser->id,
        'investment_id' => $investment->id,
        'earned_on' => now()->toDateString(),
        'amount' => 9.50,
        'source' => 'mining_daily_share',
        'status' => 'pending',
        'notes' => 'Locked export earning.',
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.users.export', [
        'audit_filter' => 'locked_balance',
        'search' => 'locked-export@example.com',
    ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('Locked Balance');
    expect($csv)->toContain('Paid Earnings');
    expect($csv)->toContain('First Paid Subscription');
    expect($csv)->toContain('Next Unlock');
    expect($csv)->toContain('Days To Unlock');
    expect($csv)->toContain('Last Paid Payout');
    expect($csv)->toContain('"Audit filter",locked_balance');
    expect($csv)->toContain('locked-export@example.com');
    expect($csv)->toContain('9.50');
});

test('admin can approve pending kyc from users page', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    Storage::disk('public')->put('kyc-proofs/pending-proof.pdf', 'pending-proof');

    $user = User::factory()->create([
        'name' => 'Pending Kyc User',
        'email' => 'pending-kyc@example.com',
        'email_verified_at' => now(),
        'kyc_status' => 'pending',
        'kyc_proof_path' => 'kyc-proofs/pending-proof.pdf',
        'kyc_proof_original_name' => 'pending-proof.pdf',
        'kyc_submitted_at' => now()->subHour(),
    ]);

    $response = $this->actingAs($admin)->post(route('dashboard.users.kyc.approve', $user), [
        'kyc_admin_notes' => 'Checked legal responsibility proof.',
    ]);

    $response->assertRedirect(route('dashboard.users', ['search' => $user->email]));

    $user->refresh();

    expect($user->kyc_status)->toBe('approved');
    expect($user->kyc_reviewer_user_id)->toBe($admin->id);
    expect($user->kyc_reviewed_at)->not->toBeNull();
});

test('admin users page shows kyc queue cards and can filter pending kyc users', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    User::factory()->create([
        'name' => 'Pending Queue User',
        'email' => 'pending-queue@example.com',
        'email_verified_at' => now(),
        'kyc_status' => 'pending',
        'kyc_submitted_at' => now()->subHour(),
    ]);

    User::factory()->create([
        'name' => 'Rejected Queue User',
        'email' => 'rejected-queue@example.com',
        'email_verified_at' => now(),
        'kyc_status' => 'rejected',
        'kyc_reviewed_at' => now()->subHour(),
    ]);

    User::factory()->create([
        'name' => 'Missing Queue User',
        'email' => 'missing-queue@example.com',
        'email_verified_at' => now(),
        'kyc_status' => 'not_submitted',
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.users'));

    $response->assertOk();
    $response->assertSee('Pending KYC');
    $response->assertSee('Rejected KYC');
    $response->assertSee('No KYC Uploaded');

    $filteredResponse = $this->actingAs($admin)->get(route('dashboard.users', [
        'kyc_status' => 'pending',
    ]));

    $filteredResponse->assertOk();
    $filteredResponse->assertSee('Pending Queue User');
    $filteredResponse->assertDontSee('Rejected Queue User');
    $filteredResponse->assertDontSee('Missing Queue User');
});

