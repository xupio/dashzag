<?php

use App\Models\Earning;
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
    $response->assertSee('Daily miner report');
});

test('investments page shows subscribed package history and daily earnings section', function () {
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

    $investment = $user->fresh()->investments()->firstOrFail();

    Earning::create([
        'user_id' => $user->id,
        'investment_id' => $investment->id,
        'earned_on' => now()->toDateString(),
        'amount' => 12.50,
        'source' => 'mining_daily_share',
        'status' => 'available',
        'notes' => 'Daily miner distribution test row.',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard.investments'));

    $response->assertOk();
    $response->assertSee('Growth 500');
    $response->assertSee('Alpha One');
    $response->assertSee('Earnings activity');
    $response->assertSee('Live miner payout tracking');
    $response->assertSee('How this pays you');
    $response->assertSee('12.50');
});

test('investments page can filter earnings activity by source', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'shareholder',
    ]);

    foreach ([
        ['amount' => 12.50, 'source' => 'mining_daily_share', 'notes' => 'Daily miner distribution test row.'],
        ['amount' => 18.00, 'source' => 'mining_return', 'notes' => 'Monthly return test row.'],
        ['amount' => 25.00, 'source' => 'referral_registration', 'notes' => 'Referral reward test row.'],
    ] as $earning) {
        Earning::create([
            'user_id' => $user->id,
            'investment_id' => null,
            'earned_on' => now()->toDateString(),
            'status' => 'available',
        ] + $earning);
    }

    $response = $this->actingAs($user)->get(route('dashboard.investments', ['source' => 'monthly_return']));

    $response->assertOk();
    $response->assertSee('Monthly return');
    $response->assertSee('Monthly return test row.');
    $response->assertDontSee('Daily miner distribution test row.');
    $response->assertDontSee('Referral reward test row.');
});


