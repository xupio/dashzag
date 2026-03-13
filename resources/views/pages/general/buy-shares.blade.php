@extends('layout.master')

@section('content')
@php
  $starterPackage = $starterPackage ?? \App\Support\MiningPlatform::freeStarterPackage();
  $starterProgress = $starterProgress ?? \App\Support\MiningPlatform::starterUpgradeProgress($user);
  $displayTierName = $user->account_type === 'starter'
    ? ($user->investments->firstWhere('package.slug', \App\Support\MiningPlatform::FREE_STARTER_PACKAGE_SLUG)?->package?->name ?? 'Free Starter')
    : $level->name;
  $proofUploadOrder = $pendingInvestmentOrder ?? $rejectedInvestmentOrder;
  $paymentMethods = collect($paymentMethods ?? [])->values();
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin gap-3">
  <div>
    <h4 class="mb-1">Buy {{ $miner->name }} Shares</h4>
    <p class="text-secondary mb-0">Choose a package, submit your payment reference, and complete the proof upload after the transfer.</p>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a href="{{ route('dashboard') }}?miner={{ $miner->slug }}" class="btn btn-outline-primary btn-sm">Back to overview</a>
    <a href="{{ route('dashboard.investment-orders') }}" class="btn btn-outline-secondary btn-sm">Order history</a>
  </div>
</div>

@if (session('subscription_success'))
  <div class="alert alert-success d-flex align-items-center justify-content-between" role="alert">
    <span>{{ session('subscription_success') }}</span>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
@endif

@if ($errors->any())
  <div class="alert alert-danger">Please review the payment form and try again.</div>
@endif

<div class="row mb-4">
  <div class="col-xl-8">
    <div class="card h-100">
      <div class="card-body d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
          <h5 class="mb-1">Purchase summary</h5>
          <p class="text-secondary mb-2">This page is focused on the purchase flow only. Personal progress remains in your profile and miner-wide metrics remain in the overview.</p>
          <div class="text-secondary small">Current level: <span class="fw-semibold text-dark">{{ $displayTierName }}</span></div>
          <div class="text-secondary small">Current package on this miner: <span class="fw-semibold text-dark">{{ $activeInvestment?->package?->name ?? 'No package yet' }}</span></div>
          <div class="text-secondary small">Level bonus: {{ number_format((float) $level->bonus_rate * 100, 2) }}% | Team bonus: {{ number_format((float) \App\Support\MiningPlatform::teamBonusRate($user) * 100, 2) }}%</div>
        </div>
        <div class="row g-3 flex-grow-1" style="max-width: 420px;">
          <div class="col-6">
            <div class="border rounded p-3 h-100">
              <div class="text-secondary small">Share price</div>
              <div class="fw-semibold fs-5">${{ number_format((float) $miner->share_price, 2) }}</div>
            </div>
          </div>
          <div class="col-6">
            <div class="border rounded p-3 h-100">
              <div class="text-secondary small">Shares available</div>
              <div class="fw-semibold fs-5">{{ number_format($availableShares) }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="card h-100 border border-success">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
          <div>
            <p class="text-secondary mb-1">Starter path</p>
            <h5 class="mb-1">{{ $starterPackage?->name ?? 'Free Starter' }}</h5>
          </div>
          <span class="badge bg-success">Free</span>
        </div>
        <p class="text-secondary small mb-3">Your free starter mission stays visible here to support the upgrade path while you browse paid packages.</p>
        <div class="small text-secondary mb-2">Verified invites: {{ $starterProgress['verified_invites'] }} / {{ $starterProgress['required_verified_invites'] }}</div>
        <div class="progress mb-3" style="height: 8px;"><div class="progress-bar bg-primary" style="width: {{ min(($starterProgress['verified_invites'] / max($starterProgress['required_verified_invites'], 1)) * 100, 100) }}%"></div></div>
        <div class="small text-secondary mb-2">Direct Basic 100 subscribers: {{ $starterProgress['direct_basic_subscribers'] }} / {{ $starterProgress['required_direct_basic_subscribers'] }}</div>
        <div class="progress mb-3" style="height: 8px;"><div class="progress-bar bg-success" style="width: {{ min(($starterProgress['direct_basic_subscribers'] / max($starterProgress['required_direct_basic_subscribers'], 1)) * 100, 100) }}%"></div></div>
        <div class="alert {{ $starterProgress['has_unlocked_basic'] ? 'alert-success' : 'alert-light border' }} mb-0 small">
          {{ $starterProgress['has_unlocked_basic'] ? 'Basic 100 is already unlocked on your account.' : 'Keep building your network to unlock the first paid package for free.' }}
        </div>
      </div>
    </div>
  </div>
</div>

@if (($miners ?? collect())->count() > 1)
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div>
            <h6 class="mb-1">Choose miner</h6>
            <p class="text-secondary mb-0">Each miner has its own share pool, packages, and projected return profile.</p>
          </div>
          <div class="d-flex flex-wrap gap-2">
            @foreach ($miners as $networkMiner)
              <a href="{{ route('dashboard.buy-shares') }}?miner={{ $networkMiner->slug }}" class="btn {{ $networkMiner->id === $miner->id ? 'btn-primary' : 'btn-outline-primary' }} btn-sm">
                {{ $networkMiner->name }}
              </a>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>
@endif

@if ($pendingInvestmentOrder)
  <div class="alert alert-warning border d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <span>Pending review: {{ $pendingInvestmentOrder->package?->name }} submitted on {{ $pendingInvestmentOrder->submitted_at?->format('M d, Y h:i A') }}.</span>
    <a href="{{ route('dashboard.investment-orders', ['status' => 'pending']) }}" class="btn btn-sm btn-outline-dark">Open pending orders</a>
  </div>
@endif

@if ($rejectedInvestmentOrder)
  <div class="alert alert-danger border d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <span>Last payment rejected for {{ $rejectedInvestmentOrder->package?->name }} on {{ $rejectedInvestmentOrder->rejected_at?->format('M d, Y h:i A') }}. {{ $rejectedInvestmentOrder->admin_notes ?: 'Please review your payment reference and submit again.' }}</span>
    <a href="{{ route('dashboard.investment-orders', ['status' => 'rejected']) }}" class="btn btn-sm btn-outline-dark">Open rejected orders</a>
  </div>
@endif

@if ($proofUploadOrder)
  <div class="row mb-4">
    <div class="col-12">
      <div class="card border {{ $proofUploadOrder->payment_proof_path ? 'border-success' : 'border-info' }}">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div>
            <h5 class="mb-1">Upload payment proof after transfer</h5>
            <p class="text-secondary mb-1">Order: {{ $proofUploadOrder->package?->name }} | Method: {{ str_replace('_', ' ', $proofUploadOrder->payment_method) }} | Reference: {{ $proofUploadOrder->payment_reference }}</p>
            <p class="text-secondary mb-0">
              @if ($proofUploadOrder->payment_proof_path)
                Current proof: {{ $proofUploadOrder->payment_proof_original_name }} uploaded on {{ $proofUploadOrder->proof_uploaded_at?->format('M d, Y h:i A') }}.
              @else
                Complete the transfer first, then upload the payment screenshot or PDF receipt here for admin review.
              @endif
            </p>
          </div>
          <div class="d-flex gap-2 flex-wrap align-items-center">
            @if ($proofUploadOrder->payment_proof_path)
              <a href="{{ route('investment-orders.proof-file', $proofUploadOrder) }}" class="btn btn-sm btn-outline-primary" target="_blank">View proof</a>
            @endif
            <form method="POST" action="{{ route('dashboard.buy-shares.proof', $proofUploadOrder) }}" enctype="multipart/form-data" class="d-flex gap-2 flex-wrap align-items-center">
              @csrf
              <input type="file" name="payment_proof" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.pdf" required>
              <button type="submit" class="btn btn-sm btn-primary">{{ $proofUploadOrder->payment_proof_path ? 'Replace proof' : 'Upload proof' }}</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
@endif

@php
  $_currentOrder = $pendingInvestmentOrder ?? $rejectedInvestmentOrder;
  $_timelineState = $activeInvestment
    ? 'approved'
    : ($_currentOrder
        ? ($_currentOrder->rejected_at
            ? 'rejected'
            : ($_currentOrder->payment_proof_path ? 'proof_uploaded' : 'submitted'))
        : null);
@endphp

@if ($_timelineState)
  <div class="row mb-4">
    <div class="col-12">
      <div class="card border-0 bg-light">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
              <h5 class="mb-1">Order status timeline</h5>
              <p class="text-secondary mb-0">Track the current investment order from submission to final review.</p>
            </div>
            <span class="badge bg-dark-subtle text-dark text-uppercase">{{ str_replace('_', ' ', $_timelineState) }}</span>
          </div>
          <div class="row g-3">
            <div class="col-md-3">
              <div class="border rounded p-3 h-100 {{ in_array($_timelineState, ['submitted', 'proof_uploaded', 'approved', 'rejected'], true) ? 'border-primary bg-primary-subtle' : 'bg-white' }}">
                <div class="fw-semibold mb-1">1. Submitted</div>
                <div class="text-secondary small">Payment method selected and reference sent to the platform.</div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="border rounded p-3 h-100 {{ in_array($_timelineState, ['proof_uploaded', 'approved'], true) ? 'border-info bg-info-subtle' : 'bg-white' }}">
                <div class="fw-semibold mb-1">2. Proof uploaded</div>
                <div class="text-secondary small">Receipt or transfer screenshot added for the admin team.</div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="border rounded p-3 h-100 {{ $_timelineState === 'rejected' ? 'border-danger bg-danger-subtle' : ($_timelineState === 'approved' ? 'border-success bg-success-subtle' : 'bg-white') }}">
                <div class="fw-semibold mb-1">3. Admin review</div>
                <div class="text-secondary small">Operations checks the reference, proof, and payment details.</div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="border rounded p-3 h-100 {{ $_timelineState === 'approved' ? 'border-success bg-success-subtle' : ($_timelineState === 'rejected' ? 'border-danger bg-danger-subtle' : 'bg-white') }}">
                <div class="fw-semibold mb-1">4. Final result</div>
                <div class="text-secondary small">{{ $_timelineState === 'rejected' ? 'Rejected. Review the admin note and submit again.' : 'Approved orders activate your package and earnings.' }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endif

<div class="row mb-3">
  <div class="col-12">
    <div class="alert alert-info border d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span>More verified invitations and more investing referrals increase the bonus rate on your own paid investments.</span>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('dashboard.network') }}" class="btn btn-sm btn-outline-primary">Open my network</a>
        <a href="{{ route('dashboard.profile') }}" class="btn btn-sm btn-outline-dark">Open personal profile</a>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-12">
    <div class="card border-0 bg-light">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
          <div>
            <h5 class="mb-1">How package returns are calculated</h5>
            <p class="text-secondary mb-0">Package returns follow the current base rate on {{ $miner->name }} plus the package uplift shown below.</p>
          </div>
          <span class="badge bg-primary-subtle text-primary fs-6">
            Current miner base: {{ number_format((float) $miner->base_monthly_return_rate * 100, 2) }}%
          </span>
        </div>
        <div class="row g-3">
          @foreach ($packages as $package)
            @php
              $packageIsStarter = (float) $package->price <= 0 || (int) $package->shares_count <= 0;
              $rateBonus = $packageIsStarter ? 0 : round((float) $package->monthly_return_rate - (float) $miner->base_monthly_return_rate, 4);
            @endphp
            <div class="col-md-4">
              <div class="border rounded p-3 h-100 bg-white">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                  <div>
                    <div class="fw-semibold">{{ $package->name }}</div>
                    <div class="text-secondary small">{{ $package->shares_count }} shares</div>
                  </div>
                  <span class="badge {{ $packageIsStarter ? 'bg-secondary-subtle text-secondary' : 'bg-success-subtle text-success' }}">
                    {{ $packageIsStarter ? 'Fixed' : ($rateBonus >= 0 ? '+' : '').number_format($rateBonus * 100, 2).'%' }}
                  </span>
                </div>
                <div class="text-secondary small">Projected return on this package</div>
                <div class="fw-bold fs-5">{{ number_format((float) $package->monthly_return_rate * 100, 2) }}%</div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  @foreach ($packages as $index => $package)
    @php
      $accent = ['primary', 'success', 'warning'][$index] ?? 'primary';
      $icon = ['award', 'trending-up', 'briefcase'][$index] ?? 'award';
      $isCurrent = $activeInvestment?->package_id === $package->id;
      $isPending = $pendingInvestmentOrder?->package_id === $package->id;
      $isUnlockTarget = $package->slug === \App\Support\MiningPlatform::BASIC_UPGRADE_PACKAGE_SLUG;
    @endphp
    <div class="col-md-4 grid-margin stretch-card">
      <div class="card h-100 {{ $isCurrent ? 'border border-' . $accent : '' }}">
        <div class="card-body d-flex flex-column">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
              <h4 class="mb-1">{{ $package->name }}</h4>
              <p class="text-secondary mb-0">{{ $package->shares_count }} shares in {{ $miner->name }}</p>
            </div>
            @if ($isCurrent)
              <span class="badge bg-{{ $accent }}">Active</span>
            @elseif ($isPending)
              <span class="badge bg-warning text-dark">Pending</span>
            @elseif ($isUnlockTarget)
              <span class="badge bg-success">Unlockable</span>
            @endif
          </div>
          <i data-lucide="{{ $icon }}" class="text-{{ $accent }} icon-xxl d-block mx-auto my-3"></i>
          <h2 class="text-center mb-1">${{ number_format((float) $package->price, 0) }}</h2>
          <p class="text-secondary text-center mb-4">{{ $isUnlockTarget ? 'Buy now or unlock through referrals' : 'One-time share purchase' }}</p>

          <div class="border rounded p-3 bg-light mb-4">
            <div class="d-flex justify-content-between mb-2">
              <span class="text-secondary">Monthly return</span>
              <span class="fw-semibold">{{ number_format((float) $package->monthly_return_rate * 100, 2) }}%</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span class="text-secondary">Equivalent units</span>
              <span class="fw-semibold">{{ $package->units_limit }}</span>
            </div>
            <div class="d-flex justify-content-between">
              <span class="text-secondary">Bonus eligible</span>
              <span class="fw-semibold">Yes</span>
            </div>
          </div>

          <form method="POST" action="{{ route('dashboard.buy-shares.subscribe') }}" class="d-grid gap-2 mt-auto">
            @csrf
            <input type="hidden" name="package" value="{{ $package->slug }}">
            <select name="payment_method" class="form-select form-select-sm" data-payment-method-select required>
              <option value="">Select payment method</option>
              @foreach ($paymentMethods as $paymentMethod)
                <option value="{{ $paymentMethod['key'] }}">{{ $paymentMethod['label'] }}</option>
              @endforeach
            </select>
            <div class="border rounded p-2 bg-light small" data-payment-method-panel>
              <div class="text-secondary">Select a payment method to see the destination details and transfer instructions.</div>
              <div class="small text-success mt-2 d-none" data-payment-copy-feedback>Copied to clipboard.</div>
            </div>
            <div class="border rounded p-3 bg-white small">
              <div class="fw-semibold mb-2">Payment completed checklist</div>
              <div class="text-secondary d-flex align-items-start gap-2 mb-2">
                <span class="badge bg-primary-subtle text-primary mt-1">1</span>
                <span>Select a payment method and review the destination details carefully.</span>
              </div>
              <div class="text-secondary d-flex align-items-start gap-2 mb-2">
                <span class="badge bg-primary-subtle text-primary mt-1">2</span>
                <span>Send the transfer and keep the transaction hash or bank reference.</span>
              </div>
              <div class="text-secondary d-flex align-items-start gap-2 mb-2">
                <span class="badge bg-primary-subtle text-primary mt-1">3</span>
                <span>Submit the payment reference here, then upload payment proof after the transfer.</span>
              </div>
              <div class="text-secondary d-flex align-items-start gap-2">
                <span class="badge bg-primary-subtle text-primary mt-1">4</span>
                <span>Wait for admin review. You will be notified once the order is approved or rejected.</span>
              </div>
            </div>
            <input type="text" name="payment_reference" class="form-control form-control-sm" data-payment-reference-input placeholder="Transaction hash or payment reference" required>
            <textarea name="notes" rows="2" class="form-control form-control-sm" placeholder="Optional payment notes"></textarea>
            <button class="btn btn-{{ $accent }}" type="submit" {{ $isPending ? 'disabled' : '' }}>{{ $isPending ? 'Pending approval' : ($isCurrent ? 'Buy again' : 'Submit payment') }}</button>
          </form>
        </div>
      </div>
    </div>
  @endforeach
</div>

@if ($activeInvestment)
  <div class="row mt-2">
    <div class="col-12">
      <div class="card">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div>
            <h5 class="mb-1">Latest package on {{ $miner->name }}</h5>
            <p class="text-secondary mb-0">{{ $activeInvestment->package?->name }} | {{ $activeInvestment->shares_owned }} shares | {{ number_format(((float) $activeInvestment->monthly_return_rate + (float) $activeInvestment->level_bonus_rate + (float) $activeInvestment->team_bonus_rate) * 100, 2) }}% total monthly return target</p>
          </div>
          <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('dashboard') }}?miner={{ $miner->slug }}" class="btn btn-outline-primary">View overview</a>
            <a href="{{ route('dashboard.investment-orders') }}" class="btn btn-outline-secondary">Order history</a>
          </div>
        </div>
      </div>
    </div>
  </div>
@endif
@endsection



@push('custom-scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const paymentMethods = @json($paymentMethods->values());
        const paymentMethodByKey = Object.fromEntries(paymentMethods.map((method) => [method.key, method]));
        const cryptoMethods = ['btc_transfer', 'usdt_transfer'];

        const renderMethod = (select) => {
            const wrapper = select.closest('form');
            const panel = wrapper?.querySelector('[data-payment-method-panel]');
            const referenceInput = wrapper?.querySelector('[data-payment-reference-input]');

            if (!panel || !referenceInput) {
                return;
            }

            const method = paymentMethodByKey[select.value] ?? null;

            if (!method) {
                panel.innerHTML = '<span class="text-muted">Select a payment method to see transfer details and next steps.</span>';
                referenceInput.placeholder = 'Transaction hash or payment reference';
                return;
            }

            const destination = method.destination || 'Destination details will be shared by the team.';
            const instructions = method.instruction || 'Follow the payment instructions from the admin team.';
            const isCrypto = cryptoMethods.includes(method.key);
            const qrUrl = isCrypto
                ? `https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=${encodeURIComponent(destination)}`
                : null;

            panel.innerHTML = `
                <div class="d-flex flex-column gap-3">
                    <div>
                        <div class="fw-semibold text-dark">${method.label}</div>
                        <div class="text-muted small">Use these details to complete your transfer.</div>
                    </div>
                    <div class="d-flex flex-column flex-md-row gap-3 align-items-start">
                        <div class="flex-grow-1">
                            <div class="text-muted text-uppercase small mb-1">Send payment to</div>
                            <div class="fw-semibold text-dark mb-2" data-payment-destination>${destination}</div>
                            <button type="button" class="btn btn-sm btn-outline-primary mb-3" data-copy-payment-destination>
                                Copy details
                            </button>
                            <div class="text-success small d-none" data-payment-copy-feedback>Copied to clipboard.</div>
                            <div class="text-muted text-uppercase small mb-1">Instructions</div>
                            <div class="text-muted">${instructions}</div>
                        </div>
                        ${qrUrl ? `
                            <div class="text-center border rounded p-2 bg-white">
                                <div class="text-muted text-uppercase small mb-2">Scan QR</div>
                                <img src="${qrUrl}" alt="${method.label} QR code" class="img-fluid" style="max-width: 160px;">
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;

            referenceInput.placeholder = method.reference_hint || 'Transaction hash or payment reference';
        };

        document.querySelectorAll('[data-payment-method-select]').forEach((select) => {
            renderMethod(select);
            select.addEventListener('change', () => renderMethod(select));
        });

        document.addEventListener('click', async (event) => {
            const button = event.target.closest('[data-copy-payment-destination]');
            if (!button) {
                return;
            }

            const panel = button.closest('[data-payment-method-panel]');
            const destination = panel?.querySelector('[data-payment-destination]')?.textContent?.trim();
            const feedback = panel?.querySelector('[data-payment-copy-feedback]');

            if (!destination) {
                return;
            }

            try {
                await navigator.clipboard.writeText(destination);
                if (feedback) {
                    feedback.classList.remove('d-none');
                    setTimeout(() => feedback.classList.add('d-none'), 1800);
                }
            } catch (error) {
                console.error('Unable to copy payment destination.', error);
            }
        });
    });
</script>
@endpush


