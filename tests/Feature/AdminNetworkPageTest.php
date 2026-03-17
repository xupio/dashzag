<?php

use App\Models\FriendInvitation;
use App\Models\InvestmentPackage;
use App\Models\Miner;
use App\Models\User;
use App\Models\UserInvestment;
use App\Models\UserLevel;
use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

function createRewardCapNetworkUser(string $name = 'Reward Cap Leader', string $email = 'rewardcapleader@example.com'): User
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
            'email' => 'network-cap-'.$index.'@example.test',
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

test('admin can view the network admin page', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $sponsor = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $branchHead = User::factory()->create([
        'email_verified_at' => now(),
        'sponsor_user_id' => $sponsor->id,
        'name' => 'Branch Head',
        'email' => 'branch@example.com',
    ]);

    User::factory()->create([
        'email_verified_at' => now(),
        'sponsor_user_id' => $branchHead->id,
        'name' => 'Downline User',
        'email' => 'downline@example.com',
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.network-admin'));

    $response->assertOk();
    $response->assertSee('Network Admin');
    $response->assertSee('Visual sponsor tree');
    $response->assertSee('Branch Head');
    $response->assertSee('downline@example.com');
    $response->assertSee('Click any node for branch details');
});

test('non admin cannot view the network admin page', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'user',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard.network-admin'))
        ->assertForbidden();
});

test('admin can focus the network tree on a selected branch', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $rootA = User::factory()->create([
        'email_verified_at' => now(),
        'name' => 'Root Alpha',
        'email' => 'rootalpha@example.com',
    ]);

    $rootB = User::factory()->create([
        'email_verified_at' => now(),
        'name' => 'Root Beta',
        'email' => 'rootbeta@example.com',
    ]);

    User::factory()->create([
        'email_verified_at' => now(),
        'sponsor_user_id' => $rootA->id,
        'name' => 'Alpha Child',
        'email' => 'alphachild@example.com',
    ]);

    User::factory()->create([
        'email_verified_at' => now(),
        'sponsor_user_id' => $rootB->id,
        'name' => 'Beta Child',
        'email' => 'betachild@example.com',
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.network-admin', [
        'tree_focus' => $rootA->id,
        'tree_depth' => 3,
    ]));

    $response->assertOk();
    $response->assertSee('Focused on');
    $response->assertSee('Root Alpha');
    $response->assertSee('The chart now shows only this sponsor branch.');
    $response->assertSee('Depth 3');
});

test('admin can export the focused network branch', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $root = User::factory()->create([
        'email_verified_at' => now(),
        'name' => 'Export Root',
        'email' => 'exportroot@example.com',
    ]);

    User::factory()->create([
        'email_verified_at' => now(),
        'sponsor_user_id' => $root->id,
        'name' => 'Export Child',
        'email' => 'exportchild@example.com',
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.network-admin.export', [
        'tree_focus' => $root->id,
        'tree_depth' => 3,
    ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    $csv = $response->streamedContent();
    expect($csv)->toContain('Focused branch');
    expect($csv)->toContain('exportroot@example.com');
    expect($csv)->toContain('Export Child');
});

test('admin can open a printable focused network branch summary', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $root = User::factory()->create([
        'email_verified_at' => now(),
        'name' => 'Printable Root',
        'email' => 'printableroot@example.com',
    ]);

    User::factory()->create([
        'email_verified_at' => now(),
        'sponsor_user_id' => $root->id,
        'name' => 'Printable Child',
        'email' => 'printablechild@example.com',
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.network-admin.print', [
        'tree_focus' => $root->id,
        'tree_depth' => 3,
    ]));

    $response->assertOk();
    $response->assertSee('Branch Summary');
    $response->assertSee('Network Admin Branch View');
    $response->assertSee('printableroot@example.com');
    $response->assertSee('Printable Child');
});

test('network admin shows unlocked reward caps for strong branch leaders', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $leader = createRewardCapNetworkUser();

    $response = $this->actingAs($admin)->get(route('dashboard.network-admin', [
        'tree_focus' => $leader->id,
        'tree_depth' => 3,
    ]));

    $response->assertOk();
    $response->assertSee($leader->name);
    $response->assertSee('6% cap');
    $response->assertSee('7% cap');
});

