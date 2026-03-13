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

test('verified user submits a package payment for admin approval', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'user',
    ]);

    $response = $this->actingAs($user)->post(route('dashboard.buy-shares.subscribe'), [
        'package' => 'growth-500',
        'payment_method' => 'btc_transfer',
        'payment_reference' => 'TX-123456',
        'notes' => 'Sent from test wallet',
    ]);

    $response->assertRedirect(route('dashboard.buy-shares', ['miner' => 'alpha-one']));

    $user->refresh();

    expect($user->account_type)->toBe('user');
    expect($user->shareholder)->toBeNull();
    expect($user->investments)->toHaveCount(0);
    expect(InvestmentOrder::query()->count())->toBe(1);

    $order = InvestmentOrder::query()->first();
    expect($order->status)->toBe('pending');
    expect($order->package->slug)->toBe('growth-500');
    expect($order->payment_method)->toBe('btc_transfer');
    expect($order->payment_reference)->toBe('TX-123456');
    expect($order->payment_proof_path)->toBeNull();
    expect($order->payment_proof_original_name)->toBeNull();
    expect($order->proof_uploaded_at)->toBeNull();
    Storage::disk('public')->assertDirectoryEmpty('investment-proofs');
});

test('user can upload payment proof after submitting the investment order', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'account_type' => 'user',
    ]);

    $this->actingAs($user)->post(route('dashboard.buy-shares.subscribe'), [
        'package' => 'growth-500',
        'payment_method' => 'btc_transfer',
        'payment_reference' => 'TX-PROOF-001',
    ]);

    $order = InvestmentOrder::query()->firstOrFail();

    $response = $this->actingAs($user)->post(route('dashboard.buy-shares.proof', $order), [
        'payment_proof' => UploadedFile::fake()->create('receipt.pdf', 120, 'application/pdf'),
    ]);

    $response->assertRedirect(route('dashboard.buy-shares', ['miner' => 'alpha-one']));

    $order->refresh();

    expect($order->payment_proof_path)->not->toBeNull();
    expect($order->payment_proof_original_name)->toBe('receipt.pdf');
    expect($order->proof_uploaded_at)->not->toBeNull();
    expect($user->fresh()->notifications->pluck('data.subject'))->toContain('Payment proof uploaded');
    Storage::disk('public')->assertExists($order->payment_proof_path);
});

test('admin can approve an investment order and activate shareholder package', function () {
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
        'payment_reference' => 'TX-APPROVE-001',
    ]);

    $this->actingAs($user)->post(route('dashboard.buy-shares.proof', InvestmentOrder::query()->firstOrFail()), [
        'payment_proof' => UploadedFile::fake()->create('approval-receipt.pdf', 120, 'application/pdf'),
    ]);

    $order = InvestmentOrder::query()->firstOrFail();

    $response = $this->actingAs($admin)
        ->post(route('dashboard.operations.investment-orders.approve', $order));

    $response->assertRedirect(route('dashboard.operations'));

    $order->refresh();
    $user->refresh();
    $user->load(['shareholder', 'investments.package', 'userLevel', 'notifications']);

    expect($order->status)->toBe('approved');
    expect($order->approved_by_id)->toBe($admin->id);
    expect($user->account_type)->toBe('shareholder');
    expect($user->shareholder)->not->toBeNull();
    expect($user->shareholder->package_name)->toBe('Growth 500');
    expect($user->shareholder->status)->toBe('active');
    expect($user->investments)->toHaveCount(1);
    expect($user->investments->first()->package->slug)->toBe('growth-500');
    expect((int) $user->investments->first()->shares_owned)->toBe(5);
    expect($user->userLevel)->not->toBeNull();
    expect($user->notifications->pluck('data.subject'))->toContain('Investment subscription activated');
});

test('admin can reject an investment order and leave it inactive', function () {
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
        'payment_method' => 'usdt_transfer',
        'payment_reference' => 'TX-REJECT-001',
    ]);

    $order = InvestmentOrder::query()->firstOrFail();

    $response = $this->actingAs($admin)
        ->post(route('dashboard.operations.investment-orders.reject', $order), [
            'admin_notes' => 'Reference did not match the payment record.',
        ]);

    $response->assertRedirect(route('dashboard.operations'));

    $order->refresh();
    $user->refresh();
    $user->load(['shareholder', 'investments', 'notifications']);

    expect($order->status)->toBe('rejected');
    expect($order->approved_by_id)->toBe($admin->id);
    expect($order->admin_notes)->toBe('Reference did not match the payment record.');
    expect($order->rejected_at)->not->toBeNull();
    expect($user->account_type)->toBe('user');
    expect($user->shareholder)->toBeNull();
    expect($user->investments)->toHaveCount(0);
    expect($user->notifications->pluck('data.subject'))->toContain('Investment payment rejected');
});

test('admin operations page shows proof preview controls for uploaded investment receipts', function () {
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
        'payment_reference' => 'TX-PREVIEW-001',
    ]);

    $order = InvestmentOrder::query()->firstOrFail();

    $this->actingAs($user)->post(route('dashboard.buy-shares.proof', $order), [
        'payment_proof' => UploadedFile::fake()->create('receipt.pdf', 120, 'application/pdf'),
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard.operations'));

    $response->assertOk();
    $response->assertSee('Preview proof');
    $response->assertSee('Payment proof preview');
    $response->assertSee('Open in new tab');
});

test('admin must provide rejection notes when rejecting an investment order', function () {
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
        'payment_reference' => 'TX-REJECT-REQUIRED',
    ]);

    $order = InvestmentOrder::query()->firstOrFail();

    $response = $this->actingAs($admin)
        ->from(route('dashboard.operations'))
        ->post(route('dashboard.operations.investment-orders.reject', $order), [
            'admin_notes' => '',
        ]);

    $response->assertRedirect(route('dashboard.operations'));
    $response->assertSessionHasErrors('admin_notes');

    expect($order->fresh()->status)->toBe('pending');
});

test('admin cannot approve an investment order without proof unless override is used', function () {
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
        'payment_reference' => 'TX-NO-PROOF-001',
    ]);

    $order = InvestmentOrder::query()->firstOrFail();

    $response = $this->actingAs($admin)
        ->from(route('dashboard.operations'))
        ->post(route('dashboard.operations.investment-orders.approve', $order));

    $response->assertRedirect(route('dashboard.operations'));
    $response->assertSessionHasErrors('approval');
    expect($order->fresh()->status)->toBe('pending');
});

test('admin can approve an investment order without proof using override notes', function () {
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
        'payment_reference' => 'TX-OVERRIDE-001',
    ]);

    $order = InvestmentOrder::query()->firstOrFail();

    $response = $this->actingAs($admin)
        ->post(route('dashboard.operations.investment-orders.approve', $order), [
            'allow_without_proof' => 1,
            'admin_notes' => 'Bank desk confirmed the transfer manually.',
        ]);

    $response->assertRedirect(route('dashboard.operations'));
    expect($order->fresh()->status)->toBe('approved');
    expect($order->fresh()->admin_notes)->toBe('Bank desk confirmed the transfer manually.');
    expect($user->fresh()->notifications->pluck('data.subject'))->toContain('Investment approved without proof override');
});

test('authorized users can open the secure payment proof file route', function () {
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
        'payment_reference' => 'TX-FILE-001',
    ]);

    $order = InvestmentOrder::query()->firstOrFail();

    $this->actingAs($user)->post(route('dashboard.buy-shares.proof', $order), [
        'payment_proof' => UploadedFile::fake()->create('proof.pdf', 120, 'application/pdf'),
    ]);

    $this->actingAs($user)
        ->get(route('investment-orders.proof-file', $order))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('investment-orders.proof-file', $order))
        ->assertOk();
});

