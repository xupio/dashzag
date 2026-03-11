@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h4 class="mb-1">Operations</h4>
        <p class="text-secondary mb-0">Review payout requests and move them from pending to approved and paid with audit details.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('dashboard.operations.export') }}" class="btn btn-outline-success btn-icon-text">
          <i data-lucide="download" class="btn-icon-prepend"></i> Export CSV
        </a>
        <a href="{{ route('dashboard.wallet') }}" class="btn btn-outline-primary btn-icon-text">
          <i data-lucide="wallet" class="btn-icon-prepend"></i> Back to wallet
        </a>
      </div>
    </div>
  </div>
</div>

@if (session('operations_success'))
  <div class="alert alert-success">{{ session('operations_success') }}</div>
@endif

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
                  <th>Amount</th>
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
                    <td>${{ number_format((float) $request->amount, 2) }}</td>
                    <td>{{ str($request->method)->replace('_', ' ')->title() }}</td>
                    <td>{{ $request->destination }}</td>
                    <td><span class="badge {{ $request->status === 'paid' ? 'bg-primary' : ($request->status === 'approved' ? 'bg-info' : 'bg-warning text-dark') }}">{{ str($request->status)->title() }}</span></td>
                    <td style="min-width: 250px;">
                      <div class="text-secondary small">Linked earnings: ${{ number_format((float) $request->earnings->sum('amount'), 2) }}</div>
                      <div class="text-secondary small">Approved: {{ $request->approved_at?->format('M d, Y h:i A') ?? '—' }}</div>
                      <div class="text-secondary small">Paid: {{ $request->processed_at?->format('M d, Y h:i A') ?? '—' }}</div>
                      <div class="text-secondary small">Reference: {{ $request->transaction_reference ?: '—' }}</div>
                      <div class="text-secondary small">Admin notes: {{ $request->admin_notes ?: '—' }}</div>
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

