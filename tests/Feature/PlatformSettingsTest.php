<?php

use App\Models\InvestmentPackage;
use App\Models\Miner;
use App\Models\User;
use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('admin can view platform settings page', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard.settings'))
        ->assertOk()
        ->assertSee('Platform Settings')
        ->assertSee('New miner defaults');
});

test('admin can update platform settings and miner creation uses them', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $this->actingAs($admin)
        ->post(route('dashboard.settings.update'), [
            'new_miner_total_shares' => 2222,
            'new_miner_share_price' => 150,
            'new_miner_daily_output_usd' => 2400,
            'new_miner_monthly_output_usd' => 72000,
            'new_miner_base_monthly_return_rate' => 0.0950,
            'launch_package_name' => 'Kickoff',
            'launch_package_shares_count' => 2,
            'launch_package_units_limit' => 2,
            'launch_package_price_multiplier' => 1.5,
            'launch_package_rate_bonus' => 0.0010,
            'growth_package_name' => 'Builder',
            'growth_package_shares_count' => 6,
            'growth_package_units_limit' => 6,
            'growth_package_price_multiplier' => 4,
            'growth_package_rate_bonus' => 0.0060,
            'scale_package_name' => 'Empire',
            'scale_package_shares_count' => 12,
            'scale_package_units_limit' => 12,
            'scale_package_price_multiplier' => 8,
            'scale_package_rate_bonus' => 0.0120,
        ])
        ->assertRedirect(route('dashboard.settings'));

    expect(MiningPlatform::platformSetting('new_miner_total_shares'))->toBe('2222');
    expect(MiningPlatform::platformSetting('launch_package_name'))->toBe('Kickoff');
    expect(MiningPlatform::platformSetting('scale_package_price_multiplier'))->toBe('8');

    $this->actingAs($admin)
        ->post(route('dashboard.miners.store'), [
            'name' => 'Gamma Forge',
            'slug' => 'gamma-forge',
            'description' => 'Test miner built from platform defaults.',
            'total_shares' => 2222,
            'share_price' => 150,
            'daily_output_usd' => 2400,
            'monthly_output_usd' => 72000,
            'base_monthly_return_rate' => 0.0950,
            'status' => 'active',
        ])
        ->assertRedirect(route('dashboard.miners'));

    $miner = Miner::where('slug', 'gamma-forge')->firstOrFail();
    $packages = InvestmentPackage::where('miner_id', $miner->id)->orderBy('display_order')->get();

    expect($packages)->toHaveCount(3);
    expect($packages[0]->name)->toBe('Kickoff 225');
    expect((int) $packages[0]->shares_count)->toBe(2);
    expect((int) $packages[1]->shares_count)->toBe(6);
    expect($packages[2]->name)->toBe('Empire 1200');
    expect((float) $packages[2]->monthly_return_rate)->toBe(0.1070);
});

test('non admin cannot access platform settings page', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'user',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard.settings'))
        ->assertForbidden();
});
