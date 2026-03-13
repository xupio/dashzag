<?php

use App\Models\InvestmentOrder;
use App\Models\InvestmentPackage;
use App\Models\User;
use App\Support\MiningPlatform;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    MiningPlatform::ensureDefaults();
});

function pendingInvestmentOrderFor(User $user, string $reference = 'TX-PENDING-001', string $status = 'pending'): InvestmentOrder
{
    $package = InvestmentPackage::query()->where('slug', 'growth-500')->with('miner')->firstOrFail();

    return InvestmentOrder::create([
        'user_id' => $user->id,
        'miner_id' => $package->miner_id,
        'package_id' => $package->id,
        'amount' => $package->price,
        'shares_owned' => $package->shares_count,
        'payment_method' => 'btc_transfer',
        'payment_reference' => $reference,
        'status' => $status,
        'submitted_at' => now(),
        'rejected_at' => $status === 'rejected' ? now() : null,
        'approved_at' => $status === 'approved' ? now() : null,
        'cancelled_at' => $status === 'cancelled' ? now() : null,
    ]);
}

test('user can view filtered investment order history and cancel a pending order', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $pendingOrder = pendingInvestmentOrderFor($user, 'TX-HISTORY-PENDING', 'pending');
    pendingInvestmentOrderFor($user, 'TX-HISTORY-REJECTED', 'rejected');

    $this->actingAs($user)
        ->get(route('dashboard.investment-orders', ['status' => 'pending']))
        ->assertOk()
        ->assertSee('TX-HISTORY-PENDING')
        ->assertDontSee('TX-HISTORY-REJECTED');

    $this->actingAs($user)
        ->post(route('dashboard.investment-orders.cancel', $pendingOrder))
        ->assertRedirect(route('dashboard.investment-orders'));

    expect($pendingOrder->fresh()->status)->toBe('cancelled');
    expect($pendingOrder->fresh()->cancelled_at)->not->toBeNull();
});

test('user cannot cancel a non pending investment order', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $approvedOrder = pendingInvestmentOrderFor($user, 'TX-APPROVED-LOCKED', 'approved');

    $this->actingAs($user)
        ->from(route('dashboard.investment-orders'))
        ->post(route('dashboard.investment-orders.cancel', $approvedOrder))
        ->assertRedirect(route('dashboard.investment-orders'))
        ->assertSessionHasErrors('cancel');

    expect($approvedOrder->fresh()->status)->toBe('approved');
});

test('admin can filter operations investment queue by status and search term', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $firstUser = User::factory()->create(['email_verified_at' => now(), 'name' => 'Alpha Investor']);
    $secondUser = User::factory()->create(['email_verified_at' => now(), 'name' => 'Beta Investor']);

    pendingInvestmentOrderFor($firstUser, 'FILTER-PENDING-100', 'pending');
    pendingInvestmentOrderFor($secondUser, 'FILTER-REJECTED-200', 'rejected');

    $this->actingAs($admin)
        ->get(route('dashboard.operations', ['investment_status' => 'pending', 'investment_search' => 'Alpha']))
        ->assertOk()
        ->assertSee('FILTER-PENDING-100')
        ->assertDontSee('FILTER-REJECTED-200');
});

test('admin can export filtered investment orders csv', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $matchingUser = User::factory()->create(['email_verified_at' => now(), 'name' => 'Gamma Investor']);
    $otherUser = User::factory()->create(['email_verified_at' => now(), 'name' => 'Delta Investor']);

    pendingInvestmentOrderFor($matchingUser, 'EXPORT-MATCH-001', 'pending');
    pendingInvestmentOrderFor($otherUser, 'EXPORT-OTHER-002', 'approved');

    $response = $this->actingAs($admin)
        ->get(route('dashboard.operations.investment-orders.export', [
            'investment_status' => 'pending',
            'investment_search' => 'Gamma',
        ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('EXPORT-MATCH-001');
    expect($csv)->not->toContain('EXPORT-OTHER-002');
});
test('admin can bulk approve only pending investment orders that already have proof', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $proofUser = User::factory()->create(['email_verified_at' => now()]);
    $missingProofUser = User::factory()->create(['email_verified_at' => now()]);

    $approvableOrder = pendingInvestmentOrderFor($proofUser, 'BULK-APPROVE-001', 'pending');
    $approvableOrder->forceFill([
        'payment_proof_path' => 'investment-proofs/bulk-approve-proof.pdf',
        'payment_proof_original_name' => 'bulk-approve-proof.pdf',
        'proof_uploaded_at' => now(),
    ])->save();

    $pendingWithoutProof = pendingInvestmentOrderFor($missingProofUser, 'BULK-APPROVE-002', 'pending');

    $this->actingAs($admin)
        ->post(route('dashboard.operations.investment-orders.bulk'), [
            'action' => 'approve',
            'order_ids' => [$approvableOrder->id, $pendingWithoutProof->id],
        ])
        ->assertRedirect(route('dashboard.operations'));

    expect($approvableOrder->fresh()->status)->toBe('approved');
    expect($pendingWithoutProof->fresh()->status)->toBe('pending');
});

test('admin can bulk reject pending investment orders with one shared note', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
    ]);

    $firstUser = User::factory()->create(['email_verified_at' => now()]);
    $secondUser = User::factory()->create(['email_verified_at' => now()]);
    $approvedUser = User::factory()->create(['email_verified_at' => now()]);

    $firstPending = pendingInvestmentOrderFor($firstUser, 'BULK-REJECT-001', 'pending');
    $secondPending = pendingInvestmentOrderFor($secondUser, 'BULK-REJECT-002', 'pending');
    $approvedOrder = pendingInvestmentOrderFor($approvedUser, 'BULK-REJECT-003', 'approved');

    $this->actingAs($admin)
        ->post(route('dashboard.operations.investment-orders.bulk'), [
            'action' => 'reject',
            'admin_notes' => 'Bulk review rejected these references.',
            'order_ids' => [$firstPending->id, $secondPending->id, $approvedOrder->id],
        ])
        ->assertRedirect(route('dashboard.operations'));

    expect($firstPending->fresh()->status)->toBe('rejected');
    expect($secondPending->fresh()->status)->toBe('rejected');
    expect($firstPending->fresh()->admin_notes)->toBe('Bulk review rejected these references.');
    expect($approvedOrder->fresh()->status)->toBe('approved');
});

