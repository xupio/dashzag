<?php

use App\Http\Middleware\EnsureSingleDeviceSession;
use App\Models\AdminActivityLog;
use App\Models\Earning;
use App\Models\InvestmentOrder;
use App\Models\PayoutRequest;
use App\Models\User;
use App\Models\UserInvestment;
use App\Notifications\PayoutStatusNotification;
use App\Support\MiningPlatform;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

function walletSettingsPayload(array $overrides = []): array
{
    return array_merge([
        'new_miner_total_shares' => 1000,
        'new_miner_share_price' => 100,
        'new_miner_daily_output_usd' => 1200,
        'new_miner_monthly_output_usd' => 36000,
        'new_miner_base_monthly_return_rate' => 0.0800,
        'launch_package_name' => 'Launch',
        'launch_package_shares_count' => 1,
        'launch_package_units_limit' => 1,
        'launch_package_price_multiplier' => 1,
        'launch_package_rate_bonus' => 0.0000,
        'growth_package_name' => 'Growth',
        'growth_package_shares_count' => 5,
        'growth_package_units_limit' => 5,
        'growth_package_price_multiplier' => 5,
        'growth_package_rate_bonus' => 0.0050,
        'scale_package_name' => 'Scale',
        'scale_package_shares_count' => 10,
        'scale_package_units_limit' => 10,
        'scale_package_price_multiplier' => 10,
        'scale_package_rate_bonus' => 0.0100,
        'payout_btc_wallet_enabled' => 1,
        'payout_btc_wallet_label' => 'BTC Wallet',
        'payout_btc_wallet_placeholder' => 'BTC address',
        'payout_btc_wallet_minimum_amount' => 10,
        'payout_btc_wallet_fixed_fee' => 0,
        'payout_btc_wallet_percentage_fee_rate' => 0,
        'payout_btc_wallet_instruction' => 'Use your BTC address.',
        'payout_btc_wallet_processing_time' => 'Within 24 hours',
        'payout_usdt_wallet_enabled' => 1,
        'payout_usdt_wallet_label' => 'USDT Wallet',
        'payout_usdt_wallet_placeholder' => 'USDT address',
        'payout_usdt_wallet_minimum_amount' => 10,
        'payout_usdt_wallet_fixed_fee' => 0,
        'payout_usdt_wallet_percentage_fee_rate' => 0,
        'payout_usdt_wallet_instruction' => 'Use your USDT address.',
        'payout_usdt_wallet_processing_time' => 'Within 12 hours',
        'payout_bank_transfer_enabled' => 0,
        'payout_bank_transfer_label' => 'Bank Transfer',
        'payout_bank_transfer_placeholder' => 'Bank account details',
        'payout_bank_transfer_minimum_amount' => 100,
        'payout_bank_transfer_fixed_fee' => 15,
        'payout_bank_transfer_percentage_fee_rate' => 0.01,
        'payout_bank_transfer_instruction' => 'Include beneficiary details.',
        'payout_bank_transfer_processing_time' => '2 to 5 business days',
        'payment_btc_transfer_enabled' => 1,
        'payment_btc_transfer_label' => 'BTC Transfer',
        'payment_btc_transfer_destination' => 'btc-destination-wallet',
        'payment_btc_transfer_reference_hint' => 'BTC transaction hash',
        'payment_btc_transfer_instruction' => 'Send BTC and submit the transaction hash.',
        'payment_btc_transfer_admin_review_note' => 'Check BTC transfer details.',
        'payment_usdt_transfer_enabled' => 1,
        'payment_usdt_transfer_label' => 'USDT Transfer',
        'payment_usdt_transfer_destination' => 'usdt-destination-wallet',
        'payment_usdt_transfer_reference_hint' => 'USDT transaction hash',
        'payment_usdt_transfer_instruction' => 'Send USDT and submit the transaction hash.',
        'payment_usdt_transfer_admin_review_note' => 'Check USDT transfer details.',
        'payment_bank_transfer_enabled' => 1,
        'payment_bank_transfer_label' => 'Bank Transfer',
        'payment_bank_transfer_destination' => 'bank-beneficiary-details',
        'payment_bank_transfer_reference_hint' => 'Bank reference',
        'payment_bank_transfer_instruction' => 'Send bank transfer and submit the reference.',
        'payment_bank_transfer_admin_review_note' => 'Check bank transfer details.',
    ], $overrides);
}

function activateGrowthInvestment($test, User $user, ?User $admin = null): void
{
    $admin ??= User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $test->actingAs($user)->post(route('dashboard.buy-shares.subscribe'), [
        'package' => 'growth-500',
        'payment_method' => 'btc_transfer',
        'payment_reference' => 'TX-WALLET-'.str($user->id)->padLeft(4, '0'),
    ])->assertRedirect(route('dashboard.buy-shares', ['miner' => 'alpha-one']));

    $order = InvestmentOrder::query()->latest('id')->firstOrFail();

    $test->actingAs($user)->post(route('dashboard.buy-shares.proof', $order), [
        'payment_proof' => UploadedFile::fake()->create('wallet-proof.pdf', 120, 'application/pdf'),
    ])->assertRedirect(route('dashboard.buy-shares', ['miner' => 'alpha-one']));

    $test->actingAs($admin)
        ->post(route('dashboard.operations.investment-orders.approve', $order->fresh()))
        ->assertRedirect(route('dashboard.operations'));

    UserInvestment::query()
        ->where('user_id', $user->id)
        ->where('status', 'active')
        ->update(['subscribed_at' => now()->subDays(31)]);
}

beforeEach(function () {
    Notification::fake();
    Storage::fake('public');
    $this->withoutMiddleware(EnsureSingleDeviceSession::class);
    MiningPlatform::ensureDefaults();
});

test('verified user can generate monthly wallet earnings from active investments', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'user',
    ]);

    activateGrowthInvestment($this, $user);

    $response = $this->actingAs($user)->post(route('dashboard.wallet.generate'));

    $response->assertRedirect(route('dashboard.wallet'));

    $user->refresh();
    $user->load('earnings');

    expect($user->earnings->where('source', 'mining_return'))->toHaveCount(1);
    expect($user->earnings->where('status', 'available')->sum('amount'))->toBeGreaterThan(0);
});

test('wallet page shows earnings source breakdown', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'shareholder',
    ]);

    foreach ([
        ['amount' => 11.25, 'source' => 'mining_daily_share', 'notes' => 'Daily miner share payout.'],
        ['amount' => 20, 'source' => 'mining_return', 'notes' => 'Monthly mining return.'],
        ['amount' => 25, 'source' => 'referral_registration', 'notes' => 'Referral reward.'],
        ['amount' => 7.5, 'source' => 'team_level_3_bonus', 'notes' => 'MLM reward.'],
    ] as $earning) {
        Earning::create([
            'user_id' => $user->id,
            'investment_id' => null,
            'earned_on' => now()->toDateString(),
            'status' => 'available',
        ] + $earning);
    }

    $response = $this->actingAs($user)->get(route('dashboard.wallet'));

    $response->assertOk();
    $response->assertSee('Earnings source breakdown');
    $response->assertSee('Miner daily share');
    $response->assertSee('Monthly return');
    $response->assertSee('Direct referral rewards');
    $response->assertSee('MLM network rewards');
    $response->assertSee('Daily miner share payout.');
    $response->assertSee('20.00');
    $response->assertSee('25.00');
    $response->assertSee('7.50');
});

test('wallet page explains why mining daily share amounts were credited', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'shareholder',
    ]);

    $miner = \App\Models\Miner::where('slug', 'alpha-one')->firstOrFail();
    $package = $miner->packages()->where('slug', 'scale-1000')->firstOrFail();

    $investment = UserInvestment::create([
        'user_id' => $user->id,
        'miner_id' => $miner->id,
        'package_id' => $package->id,
        'shareholder_id' => null,
        'amount' => 1000,
        'shares_owned' => 10,
        'monthly_return_rate' => $package->monthly_return_rate,
        'level_bonus_rate' => 0,
        'team_bonus_rate' => 0,
        'status' => 'active',
        'subscribed_at' => now(),
    ]);

    Earning::create([
        'user_id' => $user->id,
        'investment_id' => $investment->id,
        'earned_on' => now()->toDateString(),
        'amount' => 1.25,
        'source' => 'mining_daily_share',
        'status' => 'pending',
        'notes' => 'Locked daily miner distribution from Alpha One.',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard.wallet'));

    $response->assertOk();
    $response->assertSee('Why this amount: credited $1.25');
    $response->assertSee('against a daily cap of');
    $response->assertSee('Still locked until the first 30-day cycle finishes.');
});

test('wallet page can filter earnings history by source', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'shareholder',
    ]);

    Earning::create([
        'user_id' => $user->id,
        'investment_id' => null,
        'earned_on' => now()->toDateString(),
        'amount' => 11.25,
        'source' => 'mining_daily_share',
        'status' => 'available',
        'notes' => 'Daily miner share payout.',
    ]);

    Earning::create([
        'user_id' => $user->id,
        'investment_id' => null,
        'earned_on' => now()->toDateString(),
        'amount' => 20,
        'source' => 'mining_return',
        'status' => 'available',
        'notes' => 'Monthly mining return.',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard.wallet', ['source' => 'miner_daily_share']));

    $response->assertOk();
    $response->assertSee('Daily miner share payout.');
    $response->assertDontSee('Monthly mining return.');
});

test('wallet earnings history can be exported as csv with the active source filter', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'shareholder',
    ]);

    Earning::create([
        'user_id' => $user->id,
        'investment_id' => null,
        'earned_on' => now()->toDateString(),
        'amount' => 11.25,
        'source' => 'mining_daily_share',
        'status' => 'available',
        'notes' => 'Daily miner share payout.',
    ]);

    Earning::create([
        'user_id' => $user->id,
        'investment_id' => null,
        'earned_on' => now()->toDateString(),
        'amount' => 20,
        'source' => 'mining_return',
        'status' => 'available',
        'notes' => 'Monthly mining return.',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard.wallet.export', [
        'source' => 'miner_daily_share',
    ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('Section,Label,Value');
    expect($csv)->toContain('Summary,Filter,"Miner daily share"');
    expect($csv)->toContain('Mining Daily Share');
    expect($csv)->toContain('Daily miner share payout.');
    expect($csv)->not->toContain('Monthly mining return.');
});

test('verified user can request a payout from available balance', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'user',
        'kyc_status' => 'approved',
        'kyc_reviewed_at' => now(),
    ]);

    activateGrowthInvestment($this, $user);
    $this->actingAs($user)->post(route('dashboard.wallet.generate'));

    $response = $this->actingAs($user)->post(route('dashboard.wallet.request'), [
        'amount' => 30,
        'method' => 'btc_wallet',
        'destination' => 'bc1-test-wallet-address',
        'notes' => 'First withdrawal request',
    ]);

    $response->assertRedirect(route('dashboard.wallet'));

    $payoutRequest = PayoutRequest::firstOrFail();

    $user->refresh();
    $user->load(['earnings', 'payoutRequests']);

    expect($user->payoutRequests)->toHaveCount(1);
    expect((float) $user->payoutRequests->first()->amount)->toBe(30.0);
    expect((float) $payoutRequest->fee_amount)->toBe(0.0);
    expect((float) $payoutRequest->net_amount)->toBe(30.0);
    expect($user->earnings->where('status', 'payout_pending')->sum('amount'))->toBe(30.0);
});

test('wallet page reflects admin managed payout methods', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)->post(route('dashboard.settings.update'), walletSettingsPayload([
        'payout_btc_wallet_label' => 'Bitcoin Wallet',
        'payout_btc_wallet_placeholder' => 'Paste your BTC address',
        'payout_btc_wallet_fixed_fee' => 2,
        'payout_btc_wallet_percentage_fee_rate' => 0.05,
        'payout_btc_wallet_instruction' => 'Use your main BTC address.',
        'payout_usdt_wallet_label' => 'USDT TRC20',
        'payout_usdt_wallet_placeholder' => 'Paste your TRC20 address',
        'payout_usdt_wallet_fixed_fee' => 1,
        'payout_usdt_wallet_percentage_fee_rate' => 0.02,
        'payout_usdt_wallet_instruction' => 'Use the TRC20 network.',
        'payout_bank_transfer_enabled' => 0,
    ]))->assertRedirect(route('dashboard.settings'));

    $response = $this->actingAs($user)->get(route('dashboard.wallet'));

    $response->assertOk();
    $response->assertSee('Bitcoin Wallet');
    $response->assertSee('USDT TRC20');
    $response->assertDontSee('Bank Transfer');
});

test('user cannot request a payout before kyc approval', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'user',
        'kyc_status' => 'pending',
        'kyc_submitted_at' => now(),
    ]);

    Earning::create([
        'user_id' => $user->id,
        'investment_id' => null,
        'earned_on' => now()->toDateString(),
        'amount' => 30,
        'source' => 'mining_return',
        'status' => 'available',
        'notes' => 'Manual available earning for KYC gate test.',
    ]);

    $response = $this->actingAs($user)->from(route('dashboard.wallet'))->post(route('dashboard.wallet.request'), [
        'amount' => 20,
        'method' => 'btc_wallet',
        'destination' => 'bc1-kyc-gated-wallet',
    ]);

    $response->assertRedirect(route('dashboard.wallet'));
    $response->assertSessionHasErrors('kyc_proof');
    expect(PayoutRequest::count())->toBe(0);
});

test('wallet page prefills saved payout destinations from account settings', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'btc_wallet_address' => 'bc1qsavedwalletdestination',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard.wallet'));

    $response->assertOk();
    $response->assertSee('bc1qsavedwalletdestination', false);
    $response->assertSee('Your saved withdrawal wallet was loaded from your profile. You can still change it for this payout request.');
    $response->assertSee('Your selected wallet');
});

test('payout destination is trimmed before storing the request', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'user',
        'kyc_status' => 'approved',
        'kyc_reviewed_at' => now(),
    ]);

    activateGrowthInvestment($this, $user);
    $this->actingAs($user)->post(route('dashboard.wallet.generate'));

    $this->actingAs($user)->post(route('dashboard.wallet.request'), [
        'amount' => 30,
        'method' => 'btc_wallet',
        'destination' => '  bc1-trimmed-wallet-address  ',
    ])->assertRedirect(route('dashboard.wallet'));

    $payoutRequest = PayoutRequest::latest('id')->firstOrFail();

    expect($payoutRequest->destination)->toBe('bc1-trimmed-wallet-address');
});

test('wallet page explains that only available earnings are withdrawable', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard.wallet'));

    $response->assertOk();
    $response->assertSee('Mining share guide');
    $response->assertSee('Daily share starts from miner performance using hashrate, BTC price, and revenue strength.');
    $response->assertSee('Only your available earnings can be withdrawn from this wallet.');
    $response->assertSee('Package capital, miner assets, and purchased shares cannot be withdrawn here.');
});

test('disabled payout methods cannot be requested', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'user',
        'kyc_status' => 'approved',
        'kyc_reviewed_at' => now(),
    ]);

    $this->actingAs($admin)->post(route('dashboard.settings.update'), walletSettingsPayload([
        'payout_bank_transfer_enabled' => 0,
    ]))->assertRedirect(route('dashboard.settings'));

    activateGrowthInvestment($this, $user, $admin);
    $this->actingAs($user)->post(route('dashboard.wallet.generate'));

    $response = $this->actingAs($user)->from(route('dashboard.wallet'))->post(route('dashboard.wallet.request'), [
        'amount' => 20,
        'method' => 'bank_transfer',
        'destination' => 'AE00123456789',
    ]);

    $response->assertRedirect(route('dashboard.wallet'));
    $response->assertSessionHasErrors('method');
    expect(PayoutRequest::count())->toBe(0);
});

test('payout request stores fee and net amount based on method rules', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'user',
        'kyc_status' => 'approved',
        'kyc_reviewed_at' => now(),
    ]);

    $this->actingAs($admin)->post(route('dashboard.settings.update'), walletSettingsPayload([
        'payout_btc_wallet_fixed_fee' => 2,
        'payout_btc_wallet_percentage_fee_rate' => 0.05,
    ]))->assertRedirect(route('dashboard.settings'));

    activateGrowthInvestment($this, $user, $admin);
    $this->actingAs($user)->post(route('dashboard.wallet.generate'));

    $this->actingAs($user)->post(route('dashboard.wallet.request'), [
        'amount' => 20,
        'method' => 'btc_wallet',
        'destination' => 'bc1-fee-test',
    ])->assertRedirect(route('dashboard.wallet'));

    $payoutRequest = PayoutRequest::latest('id')->firstOrFail();

    expect((float) $payoutRequest->fee_amount)->toBe(3.0);
    expect((float) $payoutRequest->net_amount)->toBe(17.0);
    expect((float) $payoutRequest->fee_rate)->toBe(0.05);
});

test('payout request enforces method minimum amount', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'user',
        'kyc_status' => 'approved',
        'kyc_reviewed_at' => now(),
    ]);

    $this->actingAs($admin)->post(route('dashboard.settings.update'), walletSettingsPayload([
        'payout_btc_wallet_minimum_amount' => 30,
    ]))->assertRedirect(route('dashboard.settings'));

    activateGrowthInvestment($this, $user, $admin);
    $this->actingAs($user)->post(route('dashboard.wallet.generate'));

    $response = $this->actingAs($user)->from(route('dashboard.wallet'))->post(route('dashboard.wallet.request'), [
        'amount' => 20,
        'method' => 'btc_wallet',
        'destination' => 'bc1-min-test',
    ]);

    $response->assertRedirect(route('dashboard.wallet'));
    $response->assertSessionHasErrors('amount');
});

test('users receive payout notifications for submit approve and pay events', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'user',
        'kyc_status' => 'approved',
        'kyc_reviewed_at' => now(),
    ]);

    activateGrowthInvestment($this, $user, $admin);
    $this->actingAs($user)->post(route('dashboard.wallet.generate'));

    $this->actingAs($user)->post(route('dashboard.wallet.request'), [
        'amount' => 30,
        'method' => 'btc_wallet',
        'destination' => 'bc1-notify-test',
    ])->assertRedirect(route('dashboard.wallet'));

    $payoutRequest = PayoutRequest::firstOrFail();

    Notification::assertSentTo($user, PayoutStatusNotification::class, function ($notification, $channels) use ($user) {
        return in_array('database', $channels, true)
            && ($notification->toArray($user)['status'] ?? null) === 'submitted';
    });

    Notification::fake();
    $this->actingAs($admin)->post(route('dashboard.operations.payouts.approve', $payoutRequest), [
        'admin_notes' => 'Reviewed and approved.',
    ])->assertRedirect(route('dashboard.operations'));

    Notification::assertSentTo($user, PayoutStatusNotification::class, function ($notification, $channels) use ($user) {
        return in_array('database', $channels, true)
            && ($notification->toArray($user)['status'] ?? null) === 'approved';
    });

    Notification::fake();
    $this->actingAs($admin)->post(route('dashboard.operations.payouts.pay', $payoutRequest->fresh()), [
        'transaction_reference' => 'TX-NOTIFY-001',
        'admin_notes' => 'Treasury transfer completed.',
    ])->assertRedirect(route('dashboard.operations'));

    Notification::assertSentTo($user, PayoutStatusNotification::class, function ($notification, $channels) use ($user) {
        return in_array('database', $channels, true)
            && ($notification->toArray($user)['status'] ?? null) === 'paid';
    });
});

test('admin can approve and pay payout requests with audit details', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'user',
        'kyc_status' => 'approved',
        'kyc_reviewed_at' => now(),
    ]);

    activateGrowthInvestment($this, $user, $admin);
    $this->actingAs($user)->post(route('dashboard.wallet.generate'));
    $this->actingAs($user)->post(route('dashboard.wallet.request'), [
        'amount' => 30,
        'method' => 'btc_wallet',
        'destination' => 'bc1-test-wallet-address',
    ])->assertRedirect(route('dashboard.wallet'));

    $payoutRequest = PayoutRequest::firstOrFail();

    $this->actingAs($admin)->post(route('dashboard.operations.payouts.approve', $payoutRequest), [
        'admin_notes' => 'Checked wallet ownership and approved.',
    ])->assertRedirect(route('dashboard.operations'));

    $payoutRequest->refresh();
    expect($payoutRequest->status)->toBe('approved');
    expect($payoutRequest->admin_notes)->toBe('Checked wallet ownership and approved.');
    expect($payoutRequest->approved_at)->not->toBeNull();
    expect(AdminActivityLog::query()
        ->where('admin_user_id', $admin->id)
        ->where('action', 'payout.approve')
        ->where('subject_id', $payoutRequest->id)
        ->exists())->toBeTrue();

    $this->actingAs($admin)->post(route('dashboard.operations.payouts.pay', $payoutRequest), [
        'transaction_reference' => 'TX-20260311-0001',
        'admin_notes' => 'Sent manually through treasury wallet.',
    ])->assertRedirect(route('dashboard.operations'));

    $payoutRequest->refresh();
    $user->refresh();
    $user->load('earnings');

    expect($payoutRequest->status)->toBe('paid');
    expect($payoutRequest->transaction_reference)->toBe('TX-20260311-0001');
    expect($payoutRequest->admin_notes)->toBe('Sent manually through treasury wallet.');
    expect($payoutRequest->processed_at)->not->toBeNull();
    expect($user->earnings->where('status', 'paid')->sum('amount'))->toBe(30.0);
    expect(AdminActivityLog::query()
        ->where('admin_user_id', $admin->id)
        ->where('action', 'payout.pay')
        ->where('subject_id', $payoutRequest->id)
        ->exists())->toBeTrue();
});

test('non admin user cannot access operations page', function () {
    User::factory()->admin()->create(['email_verified_at' => now()]);
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)->get(route('dashboard.operations'))->assertForbidden();
});

test('non admin user cannot access miner page', function () {
    User::factory()->admin()->create(['email_verified_at' => now()]);
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)->get(route('dashboard.miner'))->assertForbidden();
});

test('wallet page is available to verified users', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard.wallet'));

    $response->assertOk();
    $response->assertSee('Wallet');
});



