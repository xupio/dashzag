<?php

use App\Models\InvestmentOrder;
use App\Models\User;
use App\Support\MiningPlatform;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    MiningPlatform::ensureDefaults();
});

test('verified user can view investments page', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'user',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard.investments'));

    $response->assertOk();
    $response->assertSee('My Investments');
});

test('investments page shows subscribed package history', function () {
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
        'payment_reference' => 'TX-INVESTMENTS-001',
    ])->assertRedirect(route('dashboard.buy-shares', ['miner' => 'alpha-one']));

    $order = InvestmentOrder::query()->firstOrFail();

    $this->actingAs($user)->post(route('dashboard.buy-shares.proof', $order), [
        'payment_proof' => UploadedFile::fake()->create('investments-proof.pdf', 120, 'application/pdf'),
    ])->assertRedirect(route('dashboard.buy-shares', ['miner' => 'alpha-one']));

    $this->actingAs($admin)
        ->post(route('dashboard.operations.investment-orders.approve', $order->fresh()))
        ->assertRedirect(route('dashboard.operations'));

    $response = $this->actingAs($user)->get(route('dashboard.investments'));

    $response->assertOk();
    $response->assertSee('Growth 500');
    $response->assertSee('Alpha One');
});
