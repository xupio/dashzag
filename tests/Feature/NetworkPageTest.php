<?php

use App\Models\FriendInvitation;
use App\Models\InvestmentOrder;
use App\Models\User;
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
