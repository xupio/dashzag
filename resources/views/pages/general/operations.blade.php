@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h4 class="mb-1">Operations</h4>
        <p class="text-secondary mb-0">Review payout requests and move them from pending to approved and paid.</p>
      </div>
      <a href="{{ route('dashboard.wallet') }}" class="btn btn-outline-primary btn-icon-text">
        <i data-lucide="wallet" class="btn-icon-prepend"></i> Back to wallet
      </a>
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
                  <th>Linked earnings</th>
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
                    <td>${{ number_format((float) $request->earnings->sum('amount'), 2) }}</td>
                    <td>
                      <div class="d-flex gap-2 flex-wrap">
                        @if ($request->status === 'pending')
                          <form method="POST" action="{{ route('dashboard.operations.payouts.approve', $request) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-info">Approve</button>
                          </form>
                        @endif
                        @if (in_array($request->status, ['pending', 'approved'], true))
                          <form method="POST" action="{{ route('dashboard.operations.payouts.pay', $request) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-primary">Mark paid</button>
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
