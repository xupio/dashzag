<?php

use App\Models\User;
use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('admin can view reward settings page', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard.rewards'))
        ->assertOk()
        ->assertSee('Reward Settings')
        ->assertSee('Free Starter mission');
});

test('admin can update reward settings', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $this->actingAs($admin)
        ->post(route('dashboard.rewards.update'), [
            'free_starter_verified_invites_required' => 15,
            'free_starter_direct_basic_required' => 2,
            'referral_registration_reward' => 40,
            'referral_subscription_reward_rate' => 0.06,
            'team_direct_subscription_reward_rate' => 0.04,
            'team_indirect_subscription_reward_rate' => 0.02,
            'invitation_bonus_after_10_rate' => 0.0040,
            'invitation_bonus_after_20_rate' => 0.0080,
            'invitation_bonus_after_50_rate' => 0.0160,
            'team_bonus_after_1_investor_rate' => 0.0030,
            'team_bonus_after_3_investor_rate' => 0.0060,
            'team_bonus_after_5_investor_rate' => 0.0120,
            'team_level_3_subscription_reward_rate' => 0.0060,
            'team_level_4_subscription_reward_rate' => 0.0030,
            'team_level_5_subscription_reward_rate' => 0.0015,
        ])
        ->assertRedirect(route('dashboard.rewards'));

    expect(MiningPlatform::rewardSetting('free_starter_verified_invites_required'))->toBe('15');
    expect(MiningPlatform::rewardSetting('referral_registration_reward'))->toBe('40');
    expect(MiningPlatform::rewardSetting('team_direct_subscription_reward_rate'))->toBe('0.04');
    expect(MiningPlatform::rewardSetting('team_level_5_subscription_reward_rate'))->toBe('0.0015');
});

test('non admin cannot access reward settings page', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'user',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard.rewards'))
        ->assertForbidden();
});

