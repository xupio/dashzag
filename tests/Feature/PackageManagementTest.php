<?php

use App\Models\InvestmentPackage;
use App\Models\Miner;
use App\Models\User;
use App\Support\MiningPlatform;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('admin can create an investment package for a miner', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $miner = Miner::where('slug', 'beta-flux')->firstOrFail();

    $response = $this->actingAs($admin)->post(route('dashboard.packages.store'), [
        'miner_id' => $miner->id,
        'name' => 'Beta Elite 1500',
        'slug' => 'beta-elite-1500',
        'price' => 1500,
        'shares_count' => 18,
        'units_limit' => 18,
        'monthly_return_rate' => 0.1025,
        'display_order' => 4,
        'is_active' => 1,
    ]);

    $response->assertRedirect(route('dashboard.packages'));

    $package = InvestmentPackage::where('slug', 'beta-elite-1500')->first();

    expect($package)->not->toBeNull();
    expect($package->miner_id)->toBe($miner->id);
    expect($package->name)->toBe('Beta Elite 1500');
    expect((float) $package->price)->toBe(1500.0);
});

test('admin can archive an investment package', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $package = InvestmentPackage::where('slug', 'growth-500')->firstOrFail();

    $response = $this->actingAs($admin)->post(route('dashboard.packages.archive', $package));

    $response->assertRedirect(route('dashboard.packages'));

    expect($package->fresh()->is_active)->toBeFalse();
});

test('admin can delete an unused investment package', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $package = InvestmentPackage::create([
        'miner_id' => Miner::where('slug', 'alpha-one')->firstOrFail()->id,
        'name' => 'Disposable Package',
        'slug' => 'disposable-package',
        'price' => 222,
        'shares_count' => 2,
        'units_limit' => 2,
        'monthly_return_rate' => 0.0810,
        'display_order' => 99,
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->post(route('dashboard.packages.delete', $package));

    $response->assertRedirect(route('dashboard.packages'));

    expect(InvestmentPackage::where('slug', 'disposable-package')->exists())->toBeFalse();
});

test('admin can update investment package settings', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $package = InvestmentPackage::where('slug', 'growth-500')->firstOrFail();

    $response = $this->actingAs($admin)->post(route('dashboard.packages.update', $package), [
        'name' => 'Growth 750',
        'price' => 750,
        'shares_count' => 7,
        'units_limit' => 7,
        'monthly_return_rate' => 0.0950,
        'display_order' => 2,
        'is_active' => 1,
    ]);

    $response->assertRedirect(route('dashboard.packages'));

    $package->refresh();

    expect($package->name)->toBe('Growth 750');
    expect((float) $package->price)->toBe(750.0);
    expect((int) $package->shares_count)->toBe(7);
});

test('package with investments cannot be deleted', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $investor = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'user',
    ]);

    $this->actingAs($investor)->post(route('dashboard.buy-shares.subscribe'), [
        'package' => 'growth-500',
    ]);

    $package = InvestmentPackage::where('slug', 'growth-500')->firstOrFail();

    $response = $this->actingAs($admin)->post(route('dashboard.packages.delete', $package));

    $response->assertRedirect(route('dashboard.packages'));
    $response->assertSessionHas('packages_error');

    expect($package->fresh())->not->toBeNull();
});

test('non admin user cannot update investment packages', function () {
    User::factory()->admin()->create(['email_verified_at' => now()]);
    $user = User::factory()->create(['email_verified_at' => now()]);
    $package = InvestmentPackage::where('slug', 'growth-500')->firstOrFail();
    $miner = Miner::where('slug', 'alpha-one')->firstOrFail();

    $this->actingAs($user)->post(route('dashboard.packages.update', $package), [
        'name' => 'Blocked Package',
        'price' => 750,
        'shares_count' => 7,
        'units_limit' => 7,
        'monthly_return_rate' => 0.0950,
        'display_order' => 2,
    ])->assertForbidden();

    $this->actingAs($user)->post(route('dashboard.packages.store'), [
        'miner_id' => $miner->id,
        'name' => 'Blocked New Package',
        'slug' => 'blocked-new-package',
        'price' => 250,
        'shares_count' => 2,
        'units_limit' => 2,
        'monthly_return_rate' => 0.08,
        'display_order' => 4,
    ])->assertForbidden();

    $this->actingAs($user)->post(route('dashboard.packages.archive', $package))->assertForbidden();
    $this->actingAs($user)->post(route('dashboard.packages.delete', $package))->assertForbidden();
});

