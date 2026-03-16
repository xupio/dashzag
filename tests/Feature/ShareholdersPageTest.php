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

