@extends('layout.master')

@section('content')
@php
  $starterPackage = $starterPackage ?? \App\Support\MiningPlatform::freeStarterPackage();
  $starterProgress = $starterProgress ?? \App\Support\MiningPlatform::starterUpgradeProgress($user);
  $displayTierName = $user->account_type === 'starter'
    ? ($user->investments->firstWhere('package.slug', \App\Support\MiningPlatform::FREE_STARTER_PACKAGE_SLUG)?->package?->name ?? 'Free Starter')
    : $level->name;
  $proofUploadOrder = collect([$pendingInvestmentOrder, $rejectedInvestmentOrder])
    ->filter()
    ->first(fn ($order) => ! $order->payment_proof_path);
  $paymentMethods = collect($paymentMethods ?? [])->values();
  $proofUploadOrderData = $proofUploadOrder
    ? [
        'id' => $proofUploadOrder->id,
        'package_slug' => $proofUploadOrder->package?->slug,
        'package_name' => $proofUploadOrder->package?->name,
        'payment_method' => $proofUploadOrder->payment_method,
        'payment_reference' => $proofUploadOrder->payment_reference,
        'proof_file_name' => $proofUploadOrder->payment_proof_original_name,
        'proof_uploaded_at' => $proofUploadOrder->proof_uploaded_at?->format('M d, Y h:i A'),
        'has_proof' => (bool) $proofUploadOrder->payment_proof_path,
        'proof_upload_url' => route('dashboard.buy-shares.proof', $proofUploadOrder),
        'proof_view_url' => $proofUploadOrder->payment_proof_path ? route('investment-orders.proof-file', $proofUploadOrder) : null,
        'status' => $proofUploadOrder->status,
      ]
    : null;
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin gap-3">
  <div>
    <h4 class="mb-1">Buy {{ $miner->name }} Shares</h4>
    <p class="text-secondary mb-0">Choose a package, submit your payment reference, and complete the proof upload after the transfer.</p>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a href="{{ route('dashboard') }}?miner={{ $miner->slug }}" class="btn btn-outline-primary btn-sm">Back to overview</a>
    <a href="{{ route('dashboard.miner-report', ['miner' => $miner->slug]) }}" class="btn btn-outline-secondary btn-sm">Daily miner report</a>
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
            <p class="text-secondary mb-1">Starter onboarding</p>
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

<div class="row mb-4">
  <div class="col-12">
    <div class="card border-0 bg-light">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
          <div>
            <h5 class="mb-1">Client payment flow</h5>
            <p class="text-secondary mb-0">Choose one method, copy the details exactly, send the payment once, then submit the reference and proof for review.</p>
          </div>
          <span class="badge bg-warning text-dark">Manual review enabled</span>
        </div>
        <div class="row g-3">
          <div class="col-md-4">
            <div class="border rounded p-3 h-100 bg-white">
              <div class="fw-semibold mb-1">1. Select the method</div>
              <div class="text-secondary small">Use only one payment method for each package order.</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="border rounded p-3 h-100 bg-white">
              <div class="fw-semibold mb-1">2. Copy and confirm</div>
              <div class="text-secondary small">Use the copy button or QR and verify the destination before sending.</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="border rounded p-3 h-100 bg-white">
              <div class="fw-semibold mb-1">3. Submit the proof</div>
              <div class="text-secondary small">Keep the transfer hash or receipt ready for the admin review step.</div>
            </div>
          </div>
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
      <div class="card top-payment-alert attention-pulse border-0">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div>
            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
              <span class="badge top-payment-alert__badge">Action needed</span>
              <h5 class="mb-0">Continue payment in the popup</h5>
            </div>
            <p class="top-payment-alert__meta mb-1">Order: {{ $proofUploadOrder->package?->name }} | Method: {{ str_replace('_', ' ', $proofUploadOrder->payment_method) }} | Reference: {{ $proofUploadOrder->payment_reference }}</p>
            <p class="top-payment-alert__message mb-0">
              @if ($proofUploadOrder->payment_proof_path)
                Current proof: {{ $proofUploadOrder->payment_proof_original_name }} uploaded on {{ $proofUploadOrder->proof_uploaded_at?->format('M d, Y h:i A') }}.
              @else
                Complete the transfer, then upload the payment screenshot or PDF receipt in the same popup for admin review.
              @endif
            </p>
          </div>
          <div class="d-flex gap-2 flex-wrap align-items-center">
            @if ($proofUploadOrder->payment_proof_path)
              <a href="{{ route('investment-orders.proof-file', $proofUploadOrder) }}" class="btn btn-sm btn-outline-primary" target="_blank">View proof</a>
            @endif
            <button
              type="button"
              class="btn btn-sm btn-dark top-payment-alert__button"
              data-open-purchase-modal
              data-package-slug="{{ $proofUploadOrder->package?->slug }}"
              data-package-name="{{ $proofUploadOrder->package?->name }}"
              data-package-price="{{ number_format((float) $proofUploadOrder->amount, 2) }}"
            >
              {{ $proofUploadOrder->payment_proof_path ? 'Open payment popup' : 'Continue payment in the popup' }}
            </button>
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
      $isFreeStarterPackage = $package->slug === \App\Support\MiningPlatform::FREE_STARTER_PACKAGE_SLUG
        || ((float) $package->price <= 0 && (int) $package->shares_count <= 0);
      $monthlyReturnText = number_format((float) $package->monthly_return_rate * 100, 2).'%';

      if ($package->slug === \App\Support\MiningPlatform::BASIC_UPGRADE_PACKAGE_SLUG) {
        $monthlyReturnText .= ' up to 6.00%';
      }

      if ($package->slug === 'growth-500') {
        $monthlyReturnText .= ' up to 8.00%';
      }

      if ($package->slug === 'scale-1000') {
        $monthlyReturnText .= ' up to 10.00%';
      }
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
              <span class="fw-semibold">{{ $monthlyReturnText }}</span>
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

          @if ($isFreeStarterPackage)
              <div class="d-grid gap-3 mt-auto">
                <div class="alert alert-success mb-0">
                Your free starter access is already active from registration. No payment method is needed for this step.
                </div>
                <div class="border rounded p-3 bg-white small">
                <div class="fw-semibold mb-2">Starter journey</div>
                  <div class="text-secondary d-flex align-items-start gap-2 mb-2">
                    <span class="badge bg-success-subtle text-success mt-1">1</span>
                    <span>Your account already includes the free starter entry point.</span>
                  </div>
                  <div class="text-secondary d-flex align-items-start gap-2 mb-2">
                    <span class="badge bg-primary-subtle text-primary mt-1">2</span>
                    <span>Use the mission card above to track invites and unlock the next paid package.</span>
                  </div>
                  <div class="text-secondary d-flex align-items-start gap-2">
                    <span class="badge bg-primary-subtle text-primary mt-1">3</span>
                    <span>Choose a paid package below only when you are ready to move beyond the free starter path.</span>
                  </div>
                </div>
              <a href="{{ route('dashboard.profile') }}" class="btn btn-outline-{{ $accent }}">View my starter progress</a>
              </div>
          @else
            <div class="d-grid gap-2 mt-auto">
              <div class="border rounded p-3 bg-white small">
                <div class="fw-semibold mb-2">Payment completed checklist</div>
                <div class="text-secondary d-flex align-items-start gap-2 mb-2">
                  <span class="badge bg-primary-subtle text-primary mt-1">1</span>
                  <span>Select a payment method and review the destination details in the popup carefully.</span>
                </div>
                <div class="text-secondary d-flex align-items-start gap-2 mb-2">
                  <span class="badge bg-primary-subtle text-primary mt-1">2</span>
                  <span>Send the transfer and keep the transaction hash or bank reference ready.</span>
                </div>
                <div class="text-secondary d-flex align-items-start gap-2 mb-2">
                  <span class="badge bg-primary-subtle text-primary mt-1">3</span>
                  <span>Stay in the same popup to submit the payment reference and upload proof.</span>
                </div>
                <div class="text-secondary d-flex align-items-start gap-2">
                  <span class="badge bg-primary-subtle text-primary mt-1">4</span>
                  <span>Wait for admin review. You will be notified once the order is approved or rejected.</span>
                </div>
              </div>
              <button
                type="button"
                class="btn btn-{{ $accent }}"
                data-open-purchase-modal
                data-package-slug="{{ $package->slug }}"
                data-package-name="{{ $package->name }}"
                data-package-price="{{ number_format((float) $package->price, 2) }}"
                {{ $isPending ? 'disabled' : '' }}
              >
                {{ $isPending ? 'Pending approval' : ($isCurrent ? 'Buy in popup' : 'Choose method and pay') }}
              </button>
            </div>
          @endif
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

<div class="modal fade" id="purchaseFlowModal" tabindex="-1" aria-labelledby="purchaseFlowModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title" id="purchaseFlowModalLabel">Complete package payment</h5>
          <div class="text-secondary small" data-purchase-modal-subtitle>Choose a payment method, copy the destination, then submit your proof in the same popup.</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="border rounded p-3 bg-light mb-3">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
              <div class="text-secondary text-uppercase small">Selected package</div>
              <div class="fw-semibold" data-purchase-package-name>Package</div>
            </div>
            <div class="text-end">
              <div class="text-secondary text-uppercase small">Amount</div>
              <div class="fw-semibold" data-purchase-package-price>$0.00</div>
            </div>
          </div>
        </div>

        <div class="border rounded p-3 bg-white mb-3 d-none" data-existing-order-summary>
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <div class="fw-semibold">Existing payment order</div>
            <span class="badge bg-info-subtle text-info text-uppercase" data-existing-order-status>Pending</span>
          </div>
          <div class="text-secondary small mb-1">Payment method: <span class="fw-semibold text-dark" data-existing-order-method-label></span></div>
          <div class="text-secondary small mb-1">Reference: <span class="fw-semibold text-dark" data-existing-order-reference></span></div>
          <div class="text-secondary small mb-0" data-existing-order-proof-summary>Upload your proof below to complete the popup flow.</div>
        </div>

        <form method="POST" action="{{ route('dashboard.buy-shares.subscribe') }}" class="d-grid gap-3" data-purchase-order-form>
          @csrf
          <input type="hidden" name="package" value="{{ old('package') }}" data-purchase-package-input>
          <div>
            <label for="purchase_payment_method" class="form-label">Select payment method</label>
            <select name="payment_method" id="purchase_payment_method" class="form-select @error('payment_method') is-invalid @enderror" data-purchase-method-select required>
              <option value="">Select payment method</option>
              @foreach ($paymentMethods as $paymentMethod)
                <option value="{{ $paymentMethod['key'] }}" @selected(old('payment_method') === $paymentMethod['key'])>{{ $paymentMethod['label'] }}</option>
              @endforeach
            </select>
            @error('payment_method')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
          </div>

          <div class="border rounded p-3 bg-light" data-purchase-method-panel>
            <div class="text-secondary">Select a payment method to open the QR code, destination details, and transfer instructions.</div>
            <div class="small text-success mt-2 d-none" data-payment-copy-feedback>Copied to clipboard.</div>
          </div>

          <div>
            <label for="purchase_payment_reference" class="form-label">Payment reference</label>
            <input type="text" id="purchase_payment_reference" name="payment_reference" class="form-control @error('payment_reference') is-invalid @enderror" data-payment-reference-input placeholder="Transaction hash or payment reference" value="{{ old('payment_reference') }}" required>
            @error('payment_reference')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
          </div>

          <div>
            <label for="purchase_notes" class="form-label">Notes</label>
            <textarea id="purchase_notes" name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror" placeholder="Optional payment notes">{{ old('notes') }}</textarea>
            @error('notes')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
          </div>

          <button class="btn btn-primary" type="submit" data-purchase-submit-button>Continue to proof upload</button>
        </form>

        <div class="border-top my-4"></div>

        <div data-proof-upload-section class="{{ $proofUploadOrder ? '' : 'd-none' }}">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
              <h6 class="mb-1">Upload payment proof</h6>
              <p class="text-secondary small mb-0">Stay in this popup and upload the screenshot or PDF receipt after you finish the transfer.</p>
            </div>
            <a href="#" class="btn btn-sm btn-outline-primary d-none" target="_blank" data-proof-view-link>View current proof</a>
          </div>

          <form method="POST" action="{{ $proofUploadOrder ? route('dashboard.buy-shares.proof', $proofUploadOrder) : '#' }}" enctype="multipart/form-data" class="d-grid gap-3" data-proof-upload-form>
            @csrf
            <div class="border rounded p-3 bg-white small" data-proof-status-card>
              <div class="fw-semibold mb-1">Proof status</div>
              <div class="text-secondary" data-proof-status-text>
                @if ($proofUploadOrder?->payment_proof_path)
                  Current proof: {{ $proofUploadOrder->payment_proof_original_name }} uploaded on {{ $proofUploadOrder->proof_uploaded_at?->format('M d, Y h:i A') }}.
                @else
                  No proof uploaded yet. Add the screenshot or PDF receipt to complete the review request.
                @endif
              </div>
            </div>

            <div>
              <label for="purchase_payment_proof" class="form-label">Payment proof file</label>
              <input type="file" id="purchase_payment_proof" name="payment_proof" class="form-control @error('payment_proof') is-invalid @enderror" accept=".jpg,.jpeg,.png,.pdf" data-proof-input>
              <div class="form-text">Accepted files: JPG, PNG, or PDF up to 5 MB.</div>
              @error('payment_proof')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
            </div>

            <button type="submit" class="btn btn-outline-primary" data-proof-submit-button>
              {{ $proofUploadOrder?->payment_proof_path ? 'Replace proof in popup' : 'Upload proof in popup' }}
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection



@push('style')
<style>
  .attention-pulse {
    animation: attentionPulse 1.1s ease-in-out infinite;
    box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.45);
  }

  .top-payment-alert {
    background: linear-gradient(135deg, #fff3c4 0%, #ffd27a 52%, #ffb84d 100%);
    border: 1px solid #f59e0b;
    box-shadow: 0 16px 38px rgba(245, 158, 11, 0.18);
  }

  .top-payment-alert .card-body {
    padding: 1.6rem 1.75rem;
  }

  .top-payment-alert__badge {
    background: rgba(17, 24, 39, 0.9);
    color: #fff7ed;
    font-size: 0.72rem;
    letter-spacing: 0.04em;
    text-transform: uppercase;
  }

  .top-payment-alert__meta {
    color: #7c2d12;
    font-weight: 600;
  }

  .top-payment-alert__message {
    color: #78350f;
  }

  .top-payment-alert__button {
    min-width: 210px;
    background: #111827;
    border-color: #111827;
  }

  .top-payment-alert__button:hover,
  .top-payment-alert__button:focus {
    background: #0f172a;
    border-color: #0f172a;
  }

  @keyframes attentionPulse {
    0% {
      transform: scale(1);
      box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.45);
    }
    50% {
      transform: scale(1.04);
      box-shadow: 0 0 0 10px rgba(245, 158, 11, 0);
    }
    100% {
      transform: scale(1);
      box-shadow: 0 0 0 0 rgba(245, 158, 11, 0);
    }
  }
</style>
@endpush
@push('custom-scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const paymentMethods = @json($paymentMethods->values());
        const paymentMethodByKey = Object.fromEntries(paymentMethods.map((method) => [method.key, method]));
        const proofUploadOrder = @json($proofUploadOrderData);
        const oldPackageSlug = @json(old('package'));
        const hasErrors = @json($errors->any());
        const subscriptionSuccess = @json(session('subscription_success'));
        const cryptoMethods = ['btc_transfer', 'usdt_transfer'];
        const methodMeta = {
            btc_transfer: {
                networkLabel: 'Bitcoin network',
                warning: 'Send BTC only. Do not send any other coin or token to this address.',
                referenceLabel: 'Submit the BTC transaction hash after sending.',
            },
            usdt_transfer: {
                networkLabel: 'USDT selected network',
                warning: 'Send USDT only on the exact network shown by the admin team. Sending on the wrong network can permanently lose funds.',
                referenceLabel: 'Submit the USDT transaction hash after sending.',
            },
            bank_transfer: {
                networkLabel: 'Bank transfer',
                warning: 'Use the exact beneficiary details and keep the transfer receipt for review.',
                referenceLabel: 'Submit the bank reference, receipt number, or SWIFT trace.',
            },
        };
        const modalElement = document.getElementById('purchaseFlowModal');
        const modalInstance = modalElement && window.bootstrap ? new bootstrap.Modal(modalElement) : null;
        const packageNameElement = modalElement?.querySelector('[data-purchase-package-name]');
        const packagePriceElement = modalElement?.querySelector('[data-purchase-package-price]');
        const modalSubtitleElement = modalElement?.querySelector('[data-purchase-modal-subtitle]');
        const packageInput = modalElement?.querySelector('[data-purchase-package-input]');
        const methodSelect = modalElement?.querySelector('[data-purchase-method-select]');
        const methodPanel = modalElement?.querySelector('[data-purchase-method-panel]');
        const referenceInput = modalElement?.querySelector('[data-payment-reference-input]');
        const orderForm = modalElement?.querySelector('[data-purchase-order-form]');
        const proofSection = modalElement?.querySelector('[data-proof-upload-section]');
        const proofForm = modalElement?.querySelector('[data-proof-upload-form]');
        const proofViewLink = modalElement?.querySelector('[data-proof-view-link]');
        const proofStatusText = modalElement?.querySelector('[data-proof-status-text]');
        const proofSubmitButton = modalElement?.querySelector('[data-proof-submit-button]');
        const existingOrderSummary = modalElement?.querySelector('[data-existing-order-summary]');
        const existingOrderStatus = modalElement?.querySelector('[data-existing-order-status]');
        const existingOrderMethodLabel = modalElement?.querySelector('[data-existing-order-method-label]');
        const existingOrderReference = modalElement?.querySelector('[data-existing-order-reference]');
        const existingOrderProofSummary = modalElement?.querySelector('[data-existing-order-proof-summary]');

        let activePackage = null;

        const renderMethod = (methodKey) => {
            if (!methodPanel || !referenceInput) {
                return;
            }

            const method = paymentMethodByKey[methodKey] ?? null;

            if (!method) {
                methodPanel.innerHTML = '<span class="text-muted">Select a payment method to open the QR code, destination details, and transfer instructions.</span><div class="small text-success mt-2 d-none" data-payment-copy-feedback>Copied to clipboard.</div>';
                referenceInput.placeholder = 'Transaction hash or payment reference';
                return;
            }

            const destination = method.destination || 'Destination details will be shared by the team.';
            const instructions = method.instruction || 'Follow the payment instructions from the admin team.';
            const isCrypto = cryptoMethods.includes(method.key);
            const meta = methodMeta[method.key] ?? methodMeta.bank_transfer;
            const qrDataUri = isCrypto ? method.qr_code_data_uri : null;

            methodPanel.innerHTML = `
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                        <div>
                            <div class="fw-semibold text-dark">${method.label}</div>
                            <div class="text-muted small">Use these details to complete your transfer inside this popup.</div>
                        </div>
                        <span class="badge ${isCrypto ? 'bg-warning text-dark' : 'bg-primary-subtle text-primary'}">${meta.networkLabel}</span>
                    </div>
                    <div class="d-flex flex-column flex-md-row gap-3 align-items-start">
                        <div class="flex-grow-1">
                            <div class="text-muted text-uppercase small mb-1">Send payment to</div>
                            <div class="fw-semibold text-dark mb-2 text-break" data-payment-destination>${destination}</div>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-copy-payment-destination>Copy details</button>
                                ${isCrypto ? '<span class="badge bg-success-subtle text-success align-self-center">QR ready</span>' : ''}
                            </div>
                            <div class="alert ${isCrypto ? 'alert-warning' : 'alert-info'} py-2 px-3 small mb-3">${meta.warning}</div>
                            <div class="text-muted text-uppercase small mb-1">Instructions</div>
                            <div class="text-muted mb-3">${instructions}</div>
                            <div class="text-muted text-uppercase small mb-1">What to submit after payment</div>
                            <div class="text-muted">${meta.referenceLabel}</div>
                            <div class="text-success small d-none mt-2" data-payment-copy-feedback>Copied to clipboard.</div>
                        </div>
                        ${qrDataUri ? `
                            <div class="text-center border rounded p-2 bg-white">
                                <div class="text-muted text-uppercase small mb-2">Scan QR</div>
                                <img src="${qrDataUri}" alt="${method.label} QR code" class="img-fluid" style="max-width: 180px;">
                                <div class="text-muted small mt-2">Confirm the destination text matches before sending.</div>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;

            referenceInput.placeholder = method.reference_hint || 'Transaction hash or payment reference';
        };

        const syncOrderState = (packageSlug) => {
            const matchingOrder = proofUploadOrder && proofUploadOrder.package_slug === packageSlug ? proofUploadOrder : null;

            if (!existingOrderSummary || !proofSection || !proofForm) {
                return;
            }

            if (!matchingOrder) {
                existingOrderSummary.classList.add('d-none');
                proofSection.classList.add('d-none');
                orderForm?.classList.remove('d-none');
                proofForm.setAttribute('action', '#');
                if (proofViewLink) {
                    proofViewLink.classList.add('d-none');
                    proofViewLink.setAttribute('href', '#');
                }
                if (proofStatusText) {
                    proofStatusText.textContent = 'No proof uploaded yet. Add the screenshot or PDF receipt to complete the review request.';
                }
                if (proofSubmitButton) {
                    proofSubmitButton.textContent = 'Upload proof in popup';
                }

                return;
            }

            existingOrderSummary.classList.remove('d-none');
            proofSection.classList.remove('d-none');
            orderForm?.classList.add('d-none');

            if (existingOrderStatus) {
                existingOrderStatus.textContent = matchingOrder.status;
            }
            if (existingOrderMethodLabel) {
                existingOrderMethodLabel.textContent = paymentMethodByKey[matchingOrder.payment_method]?.label ?? matchingOrder.payment_method;
            }
            if (existingOrderReference) {
                existingOrderReference.textContent = matchingOrder.payment_reference ?? 'Pending';
            }
            if (existingOrderProofSummary) {
                existingOrderProofSummary.textContent = matchingOrder.has_proof
                    ? `Current proof: ${matchingOrder.proof_file_name} uploaded on ${matchingOrder.proof_uploaded_at}.`
                    : 'No proof uploaded yet. Add the screenshot or PDF receipt below to complete the popup flow.';
            }

            proofForm.setAttribute('action', matchingOrder.proof_upload_url);

            if (proofViewLink) {
                if (matchingOrder.proof_view_url) {
                    proofViewLink.classList.remove('d-none');
                    proofViewLink.setAttribute('href', matchingOrder.proof_view_url);
                } else {
                    proofViewLink.classList.add('d-none');
                    proofViewLink.setAttribute('href', '#');
                }
            }

            if (proofStatusText) {
                proofStatusText.textContent = matchingOrder.has_proof
                    ? `Current proof: ${matchingOrder.proof_file_name} uploaded on ${matchingOrder.proof_uploaded_at}.`
                    : 'No proof uploaded yet. Add the screenshot or PDF receipt to complete the review request.';
            }

            if (proofSubmitButton) {
                proofSubmitButton.textContent = matchingOrder.has_proof ? 'Replace proof in popup' : 'Upload proof in popup';
            }

            if (methodSelect) {
                methodSelect.value = matchingOrder.payment_method ?? '';
            }
            renderMethod(matchingOrder.payment_method ?? '');
            if (referenceInput) {
                referenceInput.value = matchingOrder.payment_reference ?? '';
            }
            if (modalSubtitleElement) {
                modalSubtitleElement.textContent = 'This order is already created. Confirm the destination, scan the QR code, and upload your proof in the same popup.';
            }
        };

        const openPurchaseModal = (trigger) => {
            if (!modalInstance || !packageInput || !packageNameElement || !packagePriceElement) {
                return;
            }

            activePackage = {
                slug: trigger.dataset.packageSlug || oldPackageSlug || proofUploadOrder?.package_slug || '',
                name: trigger.dataset.packageName || proofUploadOrder?.package_name || 'Selected package',
                price: trigger.dataset.packagePrice || '0.00',
            };

            packageInput.value = activePackage.slug;
            packageNameElement.textContent = activePackage.name;
            packagePriceElement.textContent = `$${activePackage.price}`;

            if (modalSubtitleElement) {
                modalSubtitleElement.textContent = 'Choose a payment method, copy the destination, then submit your proof in the same popup.';
            }

            if (methodSelect && !proofUploadOrder) {
                methodSelect.value = methodSelect.value || '';
            }

            syncOrderState(activePackage.slug);

            if (!proofUploadOrder || proofUploadOrder.package_slug !== activePackage.slug) {
                orderForm?.classList.remove('d-none');
                if (methodSelect && oldPackageSlug === activePackage.slug && methodSelect.value) {
                    renderMethod(methodSelect.value);
                } else {
                    renderMethod(methodSelect?.value ?? '');
                }
            }

            modalInstance.show();
        };

        document.querySelectorAll('[data-open-purchase-modal]').forEach((button) => {
            button.addEventListener('click', () => openPurchaseModal(button));
        });

        methodSelect?.addEventListener('change', () => {
            renderMethod(methodSelect.value);
        });

        document.addEventListener('click', async (event) => {
            const button = event.target.closest('[data-copy-payment-destination]');
            if (!button) {
                return;
            }

            const panel = button.closest('[data-purchase-method-panel]') || button.closest('[data-payment-method-panel]');
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

        if (hasErrors && modalInstance) {
            const packageButton = document.querySelector(`[data-open-purchase-modal][data-package-slug="${oldPackageSlug || proofUploadOrder?.package_slug || ''}"]`);
            if (packageButton) {
                openPurchaseModal(packageButton);
            }
        } else if (subscriptionSuccess && proofUploadOrder && modalInstance) {
            const packageButton = document.querySelector(`[data-open-purchase-modal][data-package-slug="${proofUploadOrder.package_slug}"]`);
            if (packageButton) {
                openPurchaseModal(packageButton);
            }
        }
    });
</script>
@endpush


