@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h4 class="mb-1">Operations</h4>
        <p class="text-secondary mb-0">Review investment payment submissions and payout requests from one operations queue.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('dashboard.operations.export') }}" class="btn btn-outline-success btn-icon-text">
          <i data-lucide="download" class="btn-icon-prepend"></i> Export payouts CSV
        </a>
        <a href="{{ route('dashboard.operations.investment-orders.export', ['investment_status' => $investmentFilters['status'] ?? 'all', 'investment_search' => $investmentFilters['search'] ?? '', 'investment_payment_method' => $investmentFilters['payment_method'] ?? 'all', 'investment_proof_state' => $investmentFilters['proof_state'] ?? 'all']) }}" class="btn btn-outline-primary btn-icon-text">
          <i data-lucide="file-spreadsheet" class="btn-icon-prepend"></i> Export investment orders CSV
        </a>
      </div>
    </div>
  </div>
</div>

@if (session('operations_success'))
  <div class="alert alert-success">{{ session('operations_success') }}</div>
@endif

@if ($errors->has('approval') || $errors->has('admin_notes') || $errors->has('cancel'))
  <div class="alert alert-danger">{{ $errors->first('approval') ?: ($errors->first('admin_notes') ?: $errors->first('cancel')) }}</div>
@endif

<div class="row mb-4">
  <div class="col-12 stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h5 class="mb-1">Investment payment queue</h5>
            <p class="text-secondary mb-0">Approve, reject, export, and search the investment orders before activating mining shares.</p>
          </div>
          <div class="d-flex flex-wrap gap-2">
            @php
              $counts = $investmentOrderCounts ?? ['all' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0, 'cancelled' => 0];
              $activeStatus = $investmentFilters['status'] ?? 'all';
              $activePaymentMethod = $investmentFilters['payment_method'] ?? 'all';
              $paymentMethodCounts = $investmentPaymentMethodCounts ?? collect(['all' => 0, 'btc_transfer' => 0, 'usdt_transfer' => 0, 'bank_transfer' => 0]);
              $activeProofState = $investmentFilters['proof_state'] ?? 'all';
              $proofStateCounts = $investmentProofStateCounts ?? collect(['all' => 0, 'proof_needed' => 0, 'proof_uploaded' => 0]);
              $activeRiskState = $investmentFilters['risk_state'] ?? 'all';
              $riskStateCounts = $investmentRiskStateCounts ?? collect(['all' => 0, 'high_risk' => 0]);
              $activeRiskFocus = request()->query('investment_risk_focus', 'all');
              $riskBreakdown = collect([
                'missing_proof' => $investmentOrders->where('status', 'pending')->whereNull('payment_proof_path')->count(),
                'bank_without_notes' => $investmentOrders->where('payment_method', 'bank_transfer')->filter(fn ($order) => blank($order->notes))->count(),
                'resubmitted' => $investmentOrders->filter(fn ($order) => filled($order->rejected_at) && $order->status === 'pending')->count(),
                'override_history' => $investmentOrders->filter(fn ($order) => filled($order->approved_at) && filled($order->admin_notes) && blank($order->payment_proof_path))->count(),
              ]);
            @endphp
            @foreach (['all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled'] as $statusKey => $label)
              <span class="badge {{ $activeStatus === $statusKey ? 'bg-primary' : 'bg-light text-dark border' }}">{{ $label }}: {{ $counts[$statusKey] ?? 0 }}</span>
            @endforeach
          </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mb-3">
          @foreach (['all' => 'All methods', 'btc_transfer' => 'BTC', 'usdt_transfer' => 'USDT', 'bank_transfer' => 'Bank'] as $methodKey => $methodLabel)
            <a href="{{ route('dashboard.operations', ['investment_status' => $investmentFilters['status'] ?? 'all', 'investment_search' => $investmentFilters['search'] ?? '', 'investment_payment_method' => $methodKey]) }}" class="btn btn-sm {{ $activePaymentMethod === $methodKey ? 'btn-primary' : 'btn-outline-primary' }}">
              {{ $methodLabel }}: {{ $paymentMethodCounts[$methodKey] ?? 0 }}
            </a>
          @endforeach
        </div>

        <div class="d-flex flex-wrap gap-2 mb-3">
          @foreach (['all' => 'All proof states', 'proof_needed' => 'Proof needed', 'proof_uploaded' => 'Proof uploaded'] as $proofKey => $proofLabel)
            <a href="{{ route('dashboard.operations', ['investment_status' => $investmentFilters['status'] ?? 'all', 'investment_search' => $investmentFilters['search'] ?? '', 'investment_payment_method' => $investmentFilters['payment_method'] ?? 'all', 'investment_proof_state' => $proofKey]) }}" class="btn btn-sm {{ $activeProofState === $proofKey ? 'btn-dark' : 'btn-outline-dark' }}">
              {{ $proofLabel }}: {{ $proofStateCounts[$proofKey] ?? 0 }}
            </a>
          @endforeach
        </div>

        <div class="d-flex flex-wrap gap-2 mb-3">
          @foreach (['all' => 'All risks', 'high_risk' => 'High risk only'] as $riskKey => $riskLabel)
            <a href="{{ route('dashboard.operations', ['investment_status' => $investmentFilters['status'] ?? 'all', 'investment_search' => $investmentFilters['search'] ?? '', 'investment_payment_method' => $investmentFilters['payment_method'] ?? 'all', 'investment_proof_state' => $investmentFilters['proof_state'] ?? 'all', 'investment_risk_state' => $riskKey]) }}" class="btn btn-sm {{ $activeRiskState === $riskKey ? 'btn-danger' : 'btn-outline-danger' }}">
              {{ $riskLabel }}: {{ $riskStateCounts[$riskKey] ?? 0 }}
            </a>
          @endforeach
        </div>

        @if (($investmentRewardCapSummary ?? collect())->isNotEmpty())
          <div class="row g-2 mb-3">
            @foreach ($investmentRewardCapSummary as $capSummary)
              <div class="col-md-4">
                <div class="border rounded p-3 bg-light h-100">
                  <div class="text-secondary small">{{ $capSummary['label'] }}</div>
                  <div class="fw-semibold fs-5">{{ $capSummary['count'] }}</div>
                  <div class="small text-primary">{{ $capSummary['short'] }} investors in this queue</div>
                </div>
              </div>
            @endforeach
          </div>
        @endif
        <form method="GET" action="{{ route('dashboard.operations') }}" class="row g-2 mb-3">
          <div class="col-md-2">
            <input type="text" name="investment_search" class="form-control" placeholder="Search by user, email, package, miner, reference" value="{{ $investmentFilters['search'] ?? '' }}">
          </div>
          <div class="col-md-2">
            <select name="investment_status" class="form-select">
              <option value="all" @selected(($investmentFilters['status'] ?? 'all') === 'all')>All statuses</option>
              <option value="pending" @selected(($investmentFilters['status'] ?? '') === 'pending')>Pending</option>
              <option value="approved" @selected(($investmentFilters['status'] ?? '') === 'approved')>Approved</option>
              <option value="rejected" @selected(($investmentFilters['status'] ?? '') === 'rejected')>Rejected</option>
              <option value="cancelled" @selected(($investmentFilters['status'] ?? '') === 'cancelled')>Cancelled</option>
            </select>
          </div>
          <div class="col-md-3">
            <select name="investment_payment_method" class="form-select">
              <option value="all" @selected(($investmentFilters['payment_method'] ?? 'all') === 'all')>All methods</option>
              <option value="btc_transfer" @selected(($investmentFilters['payment_method'] ?? '') === 'btc_transfer')>BTC</option>
              <option value="usdt_transfer" @selected(($investmentFilters['payment_method'] ?? '') === 'usdt_transfer')>USDT</option>
              <option value="bank_transfer" @selected(($investmentFilters['payment_method'] ?? '') === 'bank_transfer')>Bank</option>
            </select>
          </div>
          <div class="col-md-2">
            <select name="investment_proof_state" class="form-select">
              <option value="all" @selected(($investmentFilters['proof_state'] ?? 'all') === 'all')>All proof states</option>
              <option value="proof_needed" @selected(($investmentFilters['proof_state'] ?? '') === 'proof_needed')>Proof needed</option>
              <option value="proof_uploaded" @selected(($investmentFilters['proof_state'] ?? '') === 'proof_uploaded')>Proof uploaded</option>
            </select>
          </div>
          <div class="col-md-2">
            <select name="investment_risk_state" class="form-select">
              <option value="all" @selected(($investmentFilters['risk_state'] ?? 'all') === 'all')>All risks</option>
              <option value="high_risk" @selected(($investmentFilters['risk_state'] ?? '') === 'high_risk')>High risk only</option>
            </select>
          </div>
          <div class="col-md-2 d-flex gap-2">
          <div class="col-md-2">
            <select name="investment_risk_focus" class="form-select">
              <option value="all" @selected(request()->query('investment_risk_focus', 'all') === 'all')>All risk details</option>
              <option value="missing_proof" @selected(request()->query('investment_risk_focus') === 'missing_proof')>Missing proof</option>
              <option value="bank_without_notes" @selected(request()->query('investment_risk_focus') === 'bank_without_notes')>Bank without notes</option>
              <option value="resubmitted" @selected(request()->query('investment_risk_focus') === 'resubmitted')>Resubmitted</option>
              <option value="override_history" @selected(request()->query('investment_risk_focus') === 'override_history')>Override history</option>
            </select>
          </div>
            <button type="submit" class="btn btn-primary">Apply filters</button>
            <a href="{{ route('dashboard.operations') }}" class="btn btn-outline-secondary">Reset</a>
          </div>
        </form>

        @if ($investmentOrders->isEmpty())
          <p class="text-secondary mb-0">No investment orders match the current filter.</p>
        @else
          @if (($investmentPaymentMethodSummaries ?? collect())->isNotEmpty())
            <div class="alert alert-light border mb-3">
              <div class="fw-semibold mb-2">Bulk review reminders</div>
              <div class="row g-2">
                @foreach ($investmentPaymentMethodSummaries as $methodSummary)
                  <div class="col-md-4">
                    <div class="border rounded p-3 h-100 bg-white">
                      <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <span class="fw-semibold text-capitalize">{{ $methodSummary['label'] }}</span>
                        <span class="badge bg-light text-dark border">{{ $methodSummary['count'] }}</span>
                      </div>
                      <div class="text-secondary small">{{ $methodSummary['note'] ?: 'Follow the configured review checklist for this payment method.' }}</div>
                    </div>
                  </div>
                @endforeach
              </div>
            </div>
          @endif
          <form id="bulkInvestmentOrderForm" method="POST" action="{{ route('dashboard.operations.investment-orders.bulk') }}" class="row g-2 mb-3">
            @csrf
            <div class="col-md-3">
              <select name="action" class="form-select" required>
                <option value="">Bulk action</option>
                <option value="approve">Approve selected with proof</option>
                <option value="reject">Reject selected</option>
              </select>
            </div>
            <div class="col-md-6">
              <input type="text" name="admin_notes" class="form-control" placeholder="Required for bulk rejection; optional for approval" value="{{ old('admin_notes') }}">
            </div>
            <div class="col-md-3 d-flex gap-2">
              <button type="submit" class="btn btn-primary">Run bulk action</button>
            </div>
            <div class="col-12">
              <p class="text-secondary small mb-0">Bulk approval only processes pending orders that already have proof uploaded. Non-pending or proof-missing rows are skipped automatically.</p>
            </div>
          </form>

          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th style="width: 42px;">
                    <input type="checkbox" class="form-check-input" onchange="document.querySelectorAll('.bulk-investment-order').forEach((checkbox) => { if (!checkbox.disabled) { checkbox.checked = this.checked; } });">
                  </th>
                  <th>User</th>
                  <th>Package</th>
                  <th>Payment</th>
                  <th>Submitted</th>
                  <th>Status</th>
                  <th>Review</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($investmentOrders as $order)
                  @php
                    $proofExtension = strtolower(pathinfo($order->payment_proof_original_name ?? $order->payment_proof_path ?? '', PATHINFO_EXTENSION));
                    $proofIsPdf = $proofExtension === 'pdf';
                    $proofUrl = $order->payment_proof_path ? route('investment-orders.proof-file', $order) : null;
                    $proofModalId = 'paymentProofModal'.$order->id;
                    $statusBadge = match ($order->status) {
                      'approved' => 'bg-success',
                      'rejected' => 'bg-danger',
                      'cancelled' => 'bg-secondary',
                      default => 'bg-warning text-dark',
                    };
                    $riskFlags = collect();

                    if ($order->status === 'pending' && blank($order->payment_proof_path)) {
                      $riskFlags->push(['label' => 'Missing proof', 'class' => 'bg-danger-subtle text-danger border border-danger-subtle']);
                    }

                    if ($order->payment_method === 'bank_transfer' && blank($order->notes)) {
                      $riskFlags->push(['label' => 'No bank notes', 'class' => 'bg-warning-subtle text-warning border border-warning-subtle']);
                    }

                    if ($order->rejected_at && $order->status === 'pending') {
                      $riskFlags->push(['label' => 'Resubmitted', 'class' => 'bg-info-subtle text-info border border-info-subtle']);
                    }

                    if (filled($order->approved_at) && filled($order->admin_notes) && blank($order->payment_proof_path)) {
                      $riskFlags->push(['label' => 'Override approval history', 'class' => 'bg-dark-subtle text-dark border']);
                    }
                  @endphp
                  <tr>
                    <td>
                      <input type="checkbox" name="order_ids[]" value="{{ $order->id }}" form="bulkInvestmentOrderForm" class="form-check-input bulk-investment-order" {{ $order->status === 'pending' ? '' : 'disabled' }}>
                    </td>
                    <td>
                      <div class="fw-semibold">{{ $order->user?->name }}</div>
                      <div class="text-secondary small">{{ $order->user?->email }}</div>
                      @php($rewardCaps = $investmentOrderRewardCaps[$order->id] ?? [])
                      @if (! empty($rewardCaps))
                        <div class="d-flex flex-wrap gap-1 mt-2">
                          @foreach ($rewardCaps as $cap)
                            <span class="badge bg-info-subtle text-info border border-info-subtle">{{ $cap['short'] }}</span>
                          @endforeach
                        </div>
                      @endif
                    </td>
                    <td>
                      <div class="fw-semibold">{{ $order->package?->name }}</div>
                      <div class="text-secondary small">{{ $order->miner?->name }} | {{ $order->shares_owned }} shares | ${{ number_format((float) $order->amount, 2) }}</div>
                    </td>
                    <td>
                      <div class="fw-semibold text-capitalize d-flex align-items-center gap-2">
                        @if (
                          ($activeRiskFocus === 'missing_proof' && $order->status === 'pending' && blank($order->payment_proof_path))
                          || ($activeRiskFocus === 'bank_without_notes' && $order->payment_method === 'bank_transfer' && blank($order->notes))
                          || ($activeRiskFocus === 'resubmitted' && filled($order->rejected_at) && $order->status === 'pending')
                          || ($activeRiskFocus === 'override_history' && filled($order->approved_at) && filled($order->admin_notes) && blank($order->payment_proof_path))
                        )
                          <i data-lucide="alert-triangle" class="icon-sm text-danger"></i>
                        @endif
                        <span>{{ str_replace('_', ' ', $order->payment_method) }}</span>
                      </div>
                      <div class="text-secondary small">Reference: {{ $order->payment_reference }}</div>
                      @if ($riskFlags->isNotEmpty())
                        <div class="mt-2 d-flex flex-wrap gap-2">
                          @foreach ($riskFlags as $riskFlag)
                            <span class="badge {{ $riskFlag['class'] }}">{{ $riskFlag['label'] }}</span>
                          @endforeach
                        </div>
                      @endif
                      <div class="mt-2 d-flex gap-2 flex-wrap align-items-center">
                        <span class="badge {{ $order->payment_proof_path ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning border border-warning-subtle' }}">
                          {{ $order->payment_proof_path ? 'Proof uploaded' : 'Missing proof' }}
                        </span>
                        @if ($proofUrl)
                          <button type="button" class="btn btn-xs btn-outline-primary" data-bs-toggle="modal" data-bs-target="#{{ $proofModalId }}">Preview proof</button>
                          <a href="{{ $proofUrl }}" target="_blank" class="btn btn-xs btn-outline-secondary">Open file</a>
                        @endif
                      </div>
                      <div class="text-secondary small mt-2">{{ $order->notes ?: 'No user notes' }}</div>
                      @if (filled($investmentPaymentMethodReviews[$order->payment_method] ?? null))
                        <div class="alert alert-light border small mt-2 mb-0">
                          <div class="fw-semibold mb-1">Admin review note</div>
                          <div class="text-secondary">{{ $investmentPaymentMethodReviews[$order->payment_method] }}</div>
                        </div>
                      @endif

                      @if ($proofUrl)
                        <div class="modal fade" id="{{ $proofModalId }}" tabindex="-1" aria-hidden="true">
                          <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                            <div class="modal-content">
                              <div class="modal-header">
                                <div>
                                  <h5 class="modal-title mb-1">Payment proof preview</h5>
                                  <div class="text-secondary small">{{ $order->payment_proof_original_name ?? 'Uploaded proof' }}</div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                              </div>
                              <div class="modal-body">
                                @if ($proofIsPdf)
                                  <iframe src="{{ $proofUrl }}" title="Payment proof preview" class="w-100 border rounded" style="min-height: 70vh;"></iframe>
                                @else
                                  <img src="{{ $proofUrl }}" alt="Payment proof preview" class="img-fluid rounded border w-100">
                                @endif
                              </div>
                              <div class="modal-footer justify-content-between">
                                <div class="text-secondary small">Reference: {{ $order->payment_reference }}</div>
                                <a href="{{ $proofUrl }}" target="_blank" class="btn btn-primary">Open in new tab</a>
                              </div>
                            </div>
                          </div>
                        </div>
                      @endif
                    </td>
                    <td>{{ $order->submitted_at?->format('M d, Y h:i A') }}</td>
                    <td>
                      <span class="badge {{ $statusBadge }}">{{ str($order->status)->title() }}</span>
                    </td>
                    <td>
                      <div class="text-secondary small">Reviewed by: {{ $order->approver?->name ?? '-' }}</div>
                      <div class="text-secondary small">Approved at: {{ $order->approved_at?->format('M d, Y h:i A') ?? '-' }}</div>
                      <div class="text-secondary small">Rejected at: {{ $order->rejected_at?->format('M d, Y h:i A') ?? '-' }}</div>
                      <div class="text-secondary small">Cancelled at: {{ $order->cancelled_at?->format('M d, Y h:i A') ?? '-' }}</div>
                      <div class="text-secondary small">Admin notes: {{ $order->admin_notes ?: '-' }}</div>
                    </td>
                    <td class="text-end">
                      @if ($order->status === 'pending')
                        <div class="d-flex flex-column gap-2 align-items-end">
                          @if ($order->payment_proof_path)
                            <form method="POST" action="{{ route('dashboard.operations.investment-orders.approve', $order) }}">
                              @csrf
                              <button type="submit" class="btn btn-sm btn-primary">Approve payment</button>
                            </form>
                          @else
                            <button type="button" class="btn btn-sm btn-primary" disabled>Approve payment</button>
                            <div class="small text-warning text-end">Upload required before standard approval.</div>
                            <form method="POST" action="{{ route('dashboard.operations.investment-orders.approve', $order) }}" class="d-flex flex-column gap-2 align-items-end w-100">
                              @csrf
                              <input type="hidden" name="allow_without_proof" value="1">
                              <textarea name="admin_notes" rows="2" class="form-control form-control-sm" placeholder="Override reason is required" required>{{ old('admin_notes') }}</textarea>
                              <button type="submit" class="btn btn-sm btn-outline-warning">Approve without proof</button>
                            </form>
                          @endif
                          <form method="POST" action="{{ route('dashboard.operations.investment-orders.reject', $order) }}" class="d-flex flex-column gap-2 align-items-end w-100">
                            @csrf
                            <textarea name="admin_notes" rows="2" class="form-control form-control-sm" placeholder="Rejection reason is required" required>{{ old('admin_notes') }}</textarea>
                            <button type="submit" class="btn btn-sm btn-outline-danger">Reject payment</button>
                          </form>
                        </div>
                      @else
                        <span class="text-secondary small">No further action</span>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12 stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h5 class="mb-1">Payout operations queue</h5>
            <p class="text-secondary mb-0">Handle every payout request from the current mining platform users.</p>
          </div>
          <span class="badge bg-primary">{{ $payoutRequests->count() }} requests</span>
        </div>

        @if ($payoutRequests->isEmpty())
          <p class="text-secondary mb-0">No payout requests waiting for operations.</p>
        @else
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>User</th>
                  <th>Requested</th>
                  <th>Amounts</th>
                  <th>Method</th>
                  <th>Destination</th>
                  <th>Status</th>
                  <th>Audit trail</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($payoutRequests as $request)
                  <tr>
                    <td>
                      <div class="fw-semibold">{{ $request->user?->name }}</div>
                      <div class="text-secondary small">{{ $request->user?->email }}</div>
                    </td>
                    <td>{{ $request->requested_at?->format('M d, Y h:i A') }}</td>
                    <td>
                      <div class="fw-semibold">Gross: ${{ number_format((float) $request->amount, 2) }}</div>
                      <div class="text-secondary small">Fee: ${{ number_format((float) $request->fee_amount, 2) }}</div>
                      <div class="text-secondary small">Net: ${{ number_format((float) $request->net_amount, 2) }}</div>
                    </td>
                    <td>{{ \App\Support\MiningPlatform::payoutMethodLabel($request->method) }}</td>
                    <td>{{ $request->destination }}</td>
                    <td><span class="badge {{ $request->status === 'paid' ? 'bg-primary' : ($request->status === 'approved' ? 'bg-info' : 'bg-warning text-dark') }}">{{ str($request->status)->title() }}</span></td>
                    <td style="min-width: 250px;">
                      <div class="text-secondary small">Linked earnings: ${{ number_format((float) $request->earnings->sum('amount'), 2) }}</div>
                      <div class="text-secondary small">Approved: {{ $request->approved_at?->format('M d, Y h:i A') ?? '-' }}</div>
                      <div class="text-secondary small">Paid: {{ $request->processed_at?->format('M d, Y h:i A') ?? '-' }}</div>
                      <div class="text-secondary small">Reference: {{ $request->transaction_reference ?: '-' }}</div>
                      <div class="text-secondary small">Admin notes: {{ $request->admin_notes ?: '-' }}</div>
                    </td>
                    <td style="min-width: 280px;">
                      <div class="d-flex flex-column gap-2">
                        @if ($request->status === 'pending')
                          <form method="POST" action="{{ route('dashboard.operations.payouts.approve', $request) }}" class="d-flex flex-column gap-2">
                            @csrf
                            <textarea name="admin_notes" rows="2" class="form-control form-control-sm" placeholder="Approval notes (optional)">{{ old('admin_notes') }}</textarea>
                            <button type="submit" class="btn btn-sm btn-outline-info align-self-start">Approve</button>
                          </form>
                        @endif
                        @if (in_array($request->status, ['pending', 'approved'], true))
                          <form method="POST" action="{{ route('dashboard.operations.payouts.pay', $request) }}" class="d-flex flex-column gap-2">
                            @csrf
                            <input type="text" name="transaction_reference" class="form-control form-control-sm" placeholder="Transaction reference" value="{{ old('transaction_reference') }}">
                            <textarea name="admin_notes" rows="2" class="form-control form-control-sm" placeholder="Payment notes (optional)">{{ old('admin_notes') }}</textarea>
                            <button type="submit" class="btn btn-sm btn-primary align-self-start">Mark paid</button>
                          </form>
                        @endif
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<div class="row mt-4">
  <div class="col-12 stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h5 class="mb-1">Admin security activity</h5>
            <p class="text-secondary mb-0">Recent admin payout and investment decisions are logged here with action details and timing.</p>
          </div>
          <span class="badge bg-dark">{{ ($adminActivityLogs ?? collect())->count() }} recent actions</span>
        </div>

        <form method="GET" action="{{ route('dashboard.operations') }}" class="row g-2 mb-3">
          <input type="hidden" name="investment_search" value="{{ $investmentFilters['search'] ?? '' }}">
          <input type="hidden" name="investment_status" value="{{ $investmentFilters['status'] ?? 'all' }}">
          <input type="hidden" name="investment_payment_method" value="{{ $investmentFilters['payment_method'] ?? 'all' }}">
          <input type="hidden" name="investment_proof_state" value="{{ $investmentFilters['proof_state'] ?? 'all' }}">
          <input type="hidden" name="investment_risk_state" value="{{ $investmentFilters['risk_state'] ?? 'all' }}">
          <div class="col-md-4">
            <select name="activity_action" class="form-select">
              <option value="all" @selected(($activityFilters['action'] ?? 'all') === 'all')>All admin actions</option>
              <option value="investment.approve" @selected(($activityFilters['action'] ?? '') === 'investment.approve')>Investment approved</option>
              <option value="investment.approve_without_proof" @selected(($activityFilters['action'] ?? '') === 'investment.approve_without_proof')>Investment override approved</option>
              <option value="investment.reject" @selected(($activityFilters['action'] ?? '') === 'investment.reject')>Investment rejected</option>
              <option value="payout.approve" @selected(($activityFilters['action'] ?? '') === 'payout.approve')>Payout approved</option>
              <option value="payout.pay" @selected(($activityFilters['action'] ?? '') === 'payout.pay')>Payout paid</option>
            </select>
          </div>
          <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Filter log</button>
            <a href="{{ route('dashboard.operations') }}" class="btn btn-outline-secondary">Reset</a>
          </div>
        </form>

        @if (($adminActivityLogs ?? collect())->isEmpty())
          <p class="text-secondary mb-0">No admin activity has been logged yet.</p>
        @else
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>Action</th>
                  <th>Summary</th>
                  <th>Admin</th>
                  <th>Details</th>
                  <th>When</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($adminActivityLogs as $log)
                  @php
                    $details = collect($log->details ?? []);
                  @endphp
                  <tr>
                    <td>
                      <span class="badge bg-light text-dark border">{{ str($log->action)->replace('.', ' ')->title() }}</span>
                    </td>
                    <td>
                      <div class="fw-semibold">{{ $log->summary }}</div>
                      <div class="text-secondary small">Subject: {{ class_basename((string) $log->subject_type) ?: 'N/A' }} {{ $log->subject_id ? '#'.$log->subject_id : '' }}</div>
                    </td>
                    <td>
                      <div class="fw-semibold">{{ $log->admin?->name ?? 'Unknown admin' }}</div>
                      <div class="text-secondary small">{{ $log->admin?->email ?? 'No email recorded' }}</div>
                    </td>
                    <td style="min-width: 280px;">
                      <div class="text-secondary small">IP: {{ $log->ip_address ?: '-' }}</div>
                      @if ($details->isNotEmpty())
                        @foreach ($details as $key => $value)
                          <div class="text-secondary small">
                            <span class="fw-semibold text-dark">{{ str($key)->replace('_', ' ')->title() }}:</span>
                            {{ is_bool($value) ? ($value ? 'Yes' : 'No') : ($value === null || $value === '' ? '-' : $value) }}
                          </div>
                        @endforeach
                      @else
                        <div class="text-secondary small">No extra details</div>
                      @endif
                    </td>
                    <td>
                      <div class="fw-semibold">{{ $log->created_at?->format('M d, Y h:i A') }}</div>
                      <div class="text-secondary small">{{ \Illuminate\Support\Str::limit((string) $log->user_agent, 70) ?: 'No user agent recorded' }}</div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

