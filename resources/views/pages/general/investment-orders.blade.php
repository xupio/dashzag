@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h4 class="mb-1">Investment Orders</h4>
        <p class="text-secondary mb-0">Track each payment review, upload proof, and cancel pending orders before they are reviewed.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('dashboard.buy-shares') }}" class="btn btn-primary btn-icon-text">
          <i data-lucide="badge-dollar-sign" class="btn-icon-prepend"></i> Buy shares
        </a>
        <a href="{{ route('dashboard.investments') }}" class="btn btn-outline-secondary btn-icon-text">
          <i data-lucide="chart-column" class="btn-icon-prepend"></i> Investments
        </a>
      </div>
    </div>
  </div>
</div>

@if (session('orders_success'))
  <div class="alert alert-success">{{ session('orders_success') }}</div>
@endif

@if ($errors->has('cancel'))
  <div class="alert alert-danger">{{ $errors->first('cancel') }}</div>
@endif

<div class="row mb-4">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex flex-wrap gap-2 mb-3">
          @foreach (['all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled'] as $statusKey => $label)
            <a href="{{ route('dashboard.investment-orders', ['status' => $statusKey]) }}" class="btn {{ $activeStatus === $statusKey ? 'btn-primary' : 'btn-outline-primary' }} btn-sm">
              {{ $label }} ({{ $orderCounts[$statusKey] ?? 0 }})
            </a>
          @endforeach
        </div>

        @if ($orders->isEmpty())
          <p class="text-secondary mb-0">No investment orders match this filter yet.</p>
        @else
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>Package</th>
                  <th>Payment</th>
                  <th>Submitted</th>
                  <th>Status</th>
                  <th>Review</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($orders as $order)
                  @php
                    $statusBadge = match ($order->status) {
                      'approved' => 'bg-success',
                      'rejected' => 'bg-danger',
                      'cancelled' => 'bg-secondary',
                      default => 'bg-warning text-dark',
                    };
                  @endphp
                  <tr>
                    <td>
                      <div class="fw-semibold">{{ $order->package?->name }}</div>
                      <div class="text-secondary small">{{ $order->miner?->name }} | {{ $order->shares_owned }} shares | ${{ number_format((float) $order->amount, 2) }}</div>
                    </td>
                    <td>
                      <div class="fw-semibold text-capitalize">{{ str_replace('_', ' ', $order->payment_method) }}</div>
                      <div class="text-secondary small">Reference: {{ $order->payment_reference }}</div>
                      <div class="text-secondary small mt-1">Proof: {{ $order->payment_proof_path ? ($order->payment_proof_original_name ?? 'Uploaded') : 'Not uploaded yet' }}</div>
                    </td>
                    <td>{{ $order->submitted_at?->format('M d, Y h:i A') }}</td>
                    <td><span class="badge {{ $statusBadge }}">{{ str($order->status)->title() }}</span></td>
                    <td>
                      <div class="text-secondary small">Reviewed by: {{ $order->approver?->name ?? '-' }}</div>
                      <div class="text-secondary small">Approved at: {{ $order->approved_at?->format('M d, Y h:i A') ?? '-' }}</div>
                      <div class="text-secondary small">Rejected at: {{ $order->rejected_at?->format('M d, Y h:i A') ?? '-' }}</div>
                      <div class="text-secondary small">Cancelled at: {{ $order->cancelled_at?->format('M d, Y h:i A') ?? '-' }}</div>
                      <div class="text-secondary small">Notes: {{ $order->admin_notes ?: ($order->notes ?: '-') }}</div>
                    </td>
                    <td class="text-end">
                      <div class="d-flex flex-column gap-2 align-items-end">
                        @if ($order->payment_proof_path)
                          <a href="{{ route('investment-orders.proof-file', $order) }}" target="_blank" class="btn btn-sm btn-outline-primary">View proof</a>
                        @endif

                        @if (in_array($order->status, ['pending', 'rejected'], true))
                          <a href="{{ route('dashboard.buy-shares', ['miner' => $order->miner?->slug]) }}" class="btn btn-sm btn-outline-secondary">Open payment page</a>
                        @endif

                        @if ($order->status === 'pending')
                          <form method="POST" action="{{ route('dashboard.investment-orders.cancel', $order) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger">Cancel pending order</button>
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


