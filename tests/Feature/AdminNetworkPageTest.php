<?php

use App\Models\FriendInvitation;
use App\Models\InvestmentPackage;
use App\Models\Miner;
use App\Models\ReferralCoachingNote;
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

test('network admin shows referral growth overview and top referrers', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $leader = User::factory()->create([
        'email_verified_at' => now(),
        'name' => 'Referral Leader',
        'email' => 'referral.leader@example.com',
    ]);

    FriendInvitation::create([
        'user_id' => $leader->id,
        'name' => 'Invited One',
        'email' => 'invited.one@example.com',
        'verified_at' => now(),
        'registered_at' => now(),
    ]);

    FriendInvitation::create([
        'user_id' => $leader->id,
        'name' => 'Invited Two',
        'email' => 'invited.two@example.com',
        'verified_at' => now(),
    ]);

    $convertedUser = User::factory()->create([
        'email_verified_at' => now(),
        'email' => 'invited.one@example.com',
    ]);

    $miner = Miner::query()->where('slug', 'alpha-one')->firstOrFail();
    $package = InvestmentPackage::query()->where('slug', 'starter-100')->firstOrFail();

    UserInvestment::create([
        'user_id' => $convertedUser->id,
        'miner_id' => $miner->id,
        'package_id' => $package->id,
        'amount' => 100,
        'shares_owned' => 1,
        'monthly_return_rate' => 0,
        'level_bonus_rate' => 0,
        'team_bonus_rate' => 0,
        'status' => 'active',
        'subscribed_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.network-admin'));

    $response->assertOk();
    $response->assertSee('Referral growth overview');
    $response->assertSee('Top referrer');
    $response->assertSee('Referral Leader');
    $response->assertSee('Active investor conversions');
    $response->assertSee('Recommended action');
    $response->assertSee('Healthy conversion flow');
});

test('network admin can filter referrers who need coaching', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $needsCoaching = User::factory()->create([
        'email_verified_at' => now(),
        'name' => 'Needs Coaching',
        'email' => 'needs.coaching@example.com',
    ]);

    foreach (range(1, 3) as $index) {
        FriendInvitation::create([
            'user_id' => $needsCoaching->id,
            'name' => 'Pending Invite '.$index,
            'email' => 'pending-invite-'.$index.'@example.com',
            'verified_at' => now(),
        ]);
    }

    $healthyReferrer = User::factory()->create([
        'email_verified_at' => now(),
        'name' => 'Healthy Referrer',
        'email' => 'healthy.referrer@example.com',
    ]);

    FriendInvitation::create([
        'user_id' => $healthyReferrer->id,
        'name' => 'Converted Invite',
        'email' => 'converted.invite@example.com',
        'verified_at' => now(),
        'registered_at' => now(),
    ]);

    $convertedUser = User::factory()->create([
        'email_verified_at' => now(),
        'email' => 'converted.invite@example.com',
    ]);

    $miner = Miner::query()->where('slug', 'alpha-one')->firstOrFail();
    $package = InvestmentPackage::query()->where('slug', 'starter-100')->firstOrFail();

    UserInvestment::create([
        'user_id' => $convertedUser->id,
        'miner_id' => $miner->id,
        'package_id' => $package->id,
        'amount' => 100,
        'shares_owned' => 1,
        'monthly_return_rate' => 0,
        'level_bonus_rate' => 0,
        'team_bonus_rate' => 0,
        'status' => 'active',
        'subscribed_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.network-admin', [
        'referral_filter' => 'needs_coaching',
    ]));

    $response->assertOk();
    $response->assertSee('Needs coaching');
    $response->assertSee('Needs Coaching');
    $response->assertSee('needs.coaching@example.com');
    $response->assertSee('All referrers');
    $response->assertSee('Follow up manually');
});

test('network admin can export the referral coaching list', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $needsCoaching = User::factory()->create([
        'email_verified_at' => now(),
        'name' => 'Coaching Export',
        'email' => 'coaching.export@example.com',
    ]);

    foreach (range(1, 3) as $index) {
        FriendInvitation::create([
            'user_id' => $needsCoaching->id,
            'name' => 'Export Invite '.$index,
            'email' => 'export-invite-'.$index.'@example.com',
            'verified_at' => now(),
        ]);
    }

    $response = $this->actingAs($admin)->get(route('dashboard.network-admin.referral-coaching-export'));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('Coaching Export');
    expect($csv)->toContain('coaching.export@example.com');
    expect($csv)->toContain('Follow up manually');
});

test('network admin can save a referral coaching note', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $referrer = User::factory()->create([
        'email_verified_at' => now(),
        'name' => 'Track Me',
        'email' => 'track.me@example.com',
    ]);

    $response = $this->actingAs($admin)->post(route('dashboard.network-admin.referral-coaching.update', $referrer), [
        'status' => 'contacted',
        'note' => 'Reached out with a better onboarding explanation.',
        'referral_filter' => 'needs_coaching',
    ]);

    $response->assertRedirect(route('dashboard.network-admin', [
        'referral_filter' => 'needs_coaching',
    ]));

    $this->assertDatabaseHas('referral_coaching_notes', [
        'user_id' => $referrer->id,
        'admin_user_id' => $admin->id,
        'status' => 'contacted',
        'note' => 'Reached out with a better onboarding explanation.',
    ]);

    expect(ReferralCoachingNote::where('user_id', $referrer->id)->first())->not->toBeNull();
});

test('network admin shows coaching freshness summary cards', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $contactedUser = User::factory()->create([
        'email_verified_at' => now(),
    ]);
    ReferralCoachingNote::create([
        'user_id' => $contactedUser->id,
        'admin_user_id' => $admin->id,
        'status' => 'contacted',
        'note' => 'Fresh outreach.',
        'updated_at' => now()->subDay(),
        'created_at' => now()->subDay(),
    ]);

    $waitingUser = User::factory()->create([
        'email_verified_at' => now(),
    ]);
    ReferralCoachingNote::create([
        'user_id' => $waitingUser->id,
        'admin_user_id' => $admin->id,
        'status' => 'waiting',
        'note' => 'Waiting for reply.',
        'updated_at' => now()->subDays(2),
        'created_at' => now()->subDays(2),
    ]);

    $staleUser = User::factory()->create([
        'email_verified_at' => now(),
    ]);
    ReferralCoachingNote::create([
        'user_id' => $staleUser->id,
        'admin_user_id' => $admin->id,
        'status' => 'open',
        'note' => 'Needs another follow-up.',
        'updated_at' => now()->subDays(10),
        'created_at' => now()->subDays(10),
    ]);

    $improvedUser = User::factory()->create([
        'email_verified_at' => now(),
    ]);
    ReferralCoachingNote::create([
        'user_id' => $improvedUser->id,
        'admin_user_id' => $admin->id,
        'status' => 'improved',
        'note' => 'Conversions improved.',
        'updated_at' => now()->subDay(),
        'created_at' => now()->subDay(),
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.network-admin'));

    $response->assertOk();
    $response->assertSee('Contacted recently');
    $response->assertSee('Stale follow-up');
    $response->assertSee('Improved');
    $response->assertSee('Waiting');
});

test('network admin can sort referral coaching cases by urgency', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $staleCase = User::factory()->create([
        'email_verified_at' => now(),
        'name' => 'Stale First',
        'email' => 'stale.first@example.com',
    ]);

    foreach (range(1, 4) as $index) {
        FriendInvitation::create([
            'user_id' => $staleCase->id,
            'name' => 'Stale Invite '.$index,
            'email' => 'stale-invite-'.$index.'@example.com',
            'verified_at' => now(),
        ]);
    }

    $staleNote = ReferralCoachingNote::create([
        'user_id' => $staleCase->id,
        'admin_user_id' => $admin->id,
        'status' => 'open',
        'note' => 'Old untouched case.',
    ]);
    $staleNote->forceFill([
        'created_at' => now()->subDays(10),
        'updated_at' => now()->subDays(10),
    ])->saveQuietly();

    $freshCase = User::factory()->create([
        'email_verified_at' => now(),
        'name' => 'Fresh Second',
        'email' => 'fresh.second@example.com',
    ]);

    foreach (range(1, 4) as $index) {
        FriendInvitation::create([
            'user_id' => $freshCase->id,
            'name' => 'Fresh Invite '.$index,
            'email' => 'fresh-invite-'.$index.'@example.com',
            'verified_at' => now(),
        ]);
    }

    $freshNote = ReferralCoachingNote::create([
        'user_id' => $freshCase->id,
        'admin_user_id' => $admin->id,
        'status' => 'contacted',
        'note' => 'Fresh follow-up.',
    ]);
    $freshNote->forceFill([
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ])->saveQuietly();

    $response = $this->actingAs($admin)->get(route('dashboard.network-admin', [
        'referral_filter' => 'needs_coaching',
        'referral_sort' => 'urgency',
    ]));

    $response->assertOk();
    $response->assertSee('Urgency first');

    $content = $response->getContent();
    $referralOverviewSection = str($content)
        ->between('Referral growth overview', 'Visual sponsor tree')
        ->toString();

    expect(strpos($referralOverviewSection, 'Stale First'))->toBeLessThan(strpos($referralOverviewSection, 'Fresh Second'));
});

