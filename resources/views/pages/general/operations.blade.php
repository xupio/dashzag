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
        <a href="{{ route('dashboard.operations.investment-orders.export', ['investment_status' => $investmentFilters['status'] ?? 'all', 'investment_search' => $investmentFilters['search'] ?? '']) }}" class="btn btn-outline-primary btn-icon-text">
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
            @endphp
            @foreach (['all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled'] as $statusKey => $label)
              <span class="badge {{ $activeStatus === $statusKey ? 'bg-primary' : 'bg-light text-dark border' }}">{{ $label }}: {{ $counts[$statusKey] ?? 0 }}</span>
            @endforeach
          </div>
        </div>

        <form method="GET" action="{{ route('dashboard.operations') }}" class="row g-2 mb-3">
          <div class="col-md-4">
            <input type="text" name="investment_search" class="form-control" placeholder="Search by user, email, package, miner, reference" value="{{ $investmentFilters['search'] ?? '' }}">
          </div>
          <div class="col-md-3">
            <select name="investment_status" class="form-select">
              <option value="all" @selected(($investmentFilters['status'] ?? 'all') === 'all')>All statuses</option>
              <option value="pending" @selected(($investmentFilters['status'] ?? '') === 'pending')>Pending</option>
              <option value="approved" @selected(($investmentFilters['status'] ?? '') === 'approved')>Approved</option>
              <option value="rejected" @selected(($investmentFilters['status'] ?? '') === 'rejected')>Rejected</option>
              <option value="cancelled" @selected(($investmentFilters['status'] ?? '') === 'cancelled')>Cancelled</option>
            </select>
          </div>
          <div class="col-md-5 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Apply filters</button>
            <a href="{{ route('dashboard.operations') }}" class="btn btn-outline-secondary">Reset</a>
          </div>
        </form>

        @if ($investmentOrders->isEmpty())
          <p class="text-secondary mb-0">No investment orders match the current filter.</p>
        @else
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
                  @endphp
                  <tr>
                    <td>
                      <input type="checkbox" name="order_ids[]" value="{{ $order->id }}" form="bulkInvestmentOrderForm" class="form-check-input bulk-investment-order" {{ $order->status === 'pending' ? '' : 'disabled' }}>
                    </td>
                    <td>
                      <div class="fw-semibold">{{ $order->user?->name }}</div>
                      <div class="text-secondary small">{{ $order->user?->email }}</div>
                    </td>
                    <td>
                      <div class="fw-semibold">{{ $order->package?->name }}</div>
                      <div class="text-secondary small">{{ $order->miner?->name }} | {{ $order->shares_owned }} shares | ${{ number_format((float) $order->amount, 2) }}</div>
                    </td>
                    <td>
                      <div class="fw-semibold text-capitalize">{{ str_replace('_', ' ', $order->payment_method) }}</div>
                      <div class="text-secondary small">Reference: {{ $order->payment_reference }}</div>
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
@endsection


