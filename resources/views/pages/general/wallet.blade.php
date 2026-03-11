@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h4 class="mb-1">Wallet</h4>
        <p class="text-secondary mb-0">Monitor available balance, pending returns, and every mining-related earning entry.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-success btn-icon-text" data-bs-toggle="modal" data-bs-target="#payoutRequestModal">
          <i data-lucide="landmark" class="btn-icon-prepend"></i> Request payout
        </button>
        <form method="POST" action="{{ route('dashboard.wallet.generate') }}">
          @csrf
          <button type="submit" class="btn btn-primary btn-icon-text">
            <i data-lucide="coins" class="btn-icon-prepend"></i> Generate monthly earnings
          </button>
        </form>
        @if ($user->isAdmin())
          <a href="{{ route('dashboard.operations') }}" class="btn btn-outline-secondary btn-icon-text">
            <i data-lucide="briefcase-business" class="btn-icon-prepend"></i> Operations
          </a>
        @endif
        <a href="{{ route('dashboard') }}" class="btn btn-outline-primary btn-icon-text">
          <i data-lucide="layout-dashboard" class="btn-icon-prepend"></i> Dashboard
        </a>
      </div>
    </div>
  </div>
</div>

@if (session('wallet_success'))
  <div class="alert alert-success">{{ session('wallet_success') }}</div>
@endif

<div class="row mb-4">
  <div class="col-md-3 grid-margin stretch-card"><div class="card"><div class="card-body"><p class="text-secondary mb-1">Available balance</p><h4 class="mb-0">${{ number_format($wallet['available'], 2) }}</h4></div></div></div>
  <div class="col-md-3 grid-margin stretch-card"><div class="card"><div class="card-body"><p class="text-secondary mb-1">Pending balance</p><h4 class="mb-0">${{ number_format($wallet['pending'], 2) }}</h4></div></div></div>
  <div class="col-md-3 grid-margin stretch-card"><div class="card"><div class="card-body"><p class="text-secondary mb-1">Paid out</p><h4 class="mb-0">${{ number_format($wallet['paid'], 2) }}</h4></div></div></div>
  <div class="col-md-3 grid-margin stretch-card"><div class="card"><div class="card-body"><p class="text-secondary mb-1">Total earnings</p><h4 class="mb-0">${{ number_format($wallet['total'], 2) }}</h4></div></div></div>
</div>

<div class="row">
  <div class="col-xl-4 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h5 class="mb-3">Wallet summary</h5>
        <div class="border rounded p-3 mb-3 bg-light"><div class="text-secondary small">Current level</div><div class="fw-semibold">{{ $user->userLevel?->name ?? 'Starter' }}</div></div>
        <div class="border rounded p-3 mb-3 bg-light"><div class="text-secondary small">Active investments</div><div class="fw-semibold">{{ $activeInvestments->count() }}</div></div>
        <div class="border rounded p-3 mb-3 bg-light"><div class="text-secondary small">Expected monthly earnings</div><div class="fw-semibold">${{ number_format($expectedMonthlyEarnings, 2) }}</div></div>
        <div class="border rounded p-3 mb-3 bg-light"><div class="text-secondary small">Payout requests</div><div class="fw-semibold">{{ $payoutRequests->count() }}</div></div>
        <div class="alert alert-light border mb-0">
          Use the generate button once per month to create mining returns, then submit a payout request from your available balance.
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-8 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h5 class="mb-1">Earnings history</h5>
            <p class="text-secondary mb-0">Every projected, referral, or generated wallet transaction for this user.</p>
          </div>
          <span class="badge bg-primary">{{ $earnings->count() }} entries</span>
        </div>
        @if ($earnings->isEmpty())
          <div class="text-center py-5">
            <h5 class="mb-2">No wallet entries yet</h5>
            <p class="text-secondary mb-0">Buy a package first, then generate monthly earnings to populate the wallet.</p>
          </div>
        @else
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Source</th>
                  <th>Investment</th>
                  <th>Status</th>
                  <th>Amount</th>
                  <th>Notes</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($earnings as $earning)
                  <tr>
                    <td>{{ $earning->earned_on?->format('M d, Y') }}</td>
                    <td>{{ str($earning->source)->replace('_', ' ')->title() }}</td>
                    <td>{{ $earning->investment?->package?->name ?? '—' }}</td>
                    <td>
                      <span class="badge {{ $earning->status === 'available' ? 'bg-success' : ($earning->status === 'paid' ? 'bg-primary' : 'bg-warning text-dark') }}">
                        {{ str($earning->status)->replace('_', ' ')->title() }}
                      </span>
                    </td>
                    <td>${{ number_format((float) $earning->amount, 2) }}</td>
                    <td>{{ $earning->notes ?: '—' }}</td>
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
            <h5 class="mb-1">Payout requests</h5>
            <p class="text-secondary mb-0">Submitted withdrawal requests and their current processing state.</p>
          </div>
          <span class="badge bg-success">{{ $payoutRequests->count() }} requests</span>
        </div>
        @if ($payoutRequests->isEmpty())
          <p class="text-secondary mb-0">No payout requests submitted yet.</p>
        @else
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>Requested</th>
                  <th>Amount</th>
                  <th>Method</th>
                  <th>Destination</th>
                  <th>Status</th>
                  <th>Audit</th>
                  <th>Notes</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($payoutRequests as $request)
                  <tr>
                    <td>{{ $request->requested_at?->format('M d, Y h:i A') }}</td>
                    <td>${{ number_format((float) $request->amount, 2) }}</td>
                    <td>{{ str($request->method)->replace('_', ' ')->title() }}</td>
                    <td>{{ $request->destination }}</td>
                    <td><span class="badge {{ $request->status === 'paid' ? 'bg-primary' : ($request->status === 'approved' ? 'bg-info' : 'bg-warning text-dark') }}">{{ str($request->status)->title() }}</span></td>
                    <td>
                      <div class="text-secondary small">Approved: {{ $request->approved_at?->format('M d, Y h:i A') ?? '—' }}</div>
                      <div class="text-secondary small">Paid: {{ $request->processed_at?->format('M d, Y h:i A') ?? '—' }}</div>
                      <div class="text-secondary small">Reference: {{ $request->transaction_reference ?: '—' }}</div>
                    </td>
                    <td>
                      <div>{{ $request->notes ?: '—' }}</div>
                      <div class="text-secondary small mt-1">Admin: {{ $request->admin_notes ?: '—' }}</div>
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

<div class="modal fade" id="payoutRequestModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('dashboard.wallet.request') }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Request payout</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Amount</label>
            <input type="number" step="0.01" min="1" max="{{ number_format($wallet['available'], 2, '.', '') }}" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" required>
            @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="mb-3">
            <label class="form-label">Method</label>
            <select name="method" class="form-select @error('method') is-invalid @enderror" required>
              <option value="btc_wallet" @selected(old('method') === 'btc_wallet')>BTC Wallet</option>
              <option value="usdt_wallet" @selected(old('method') === 'usdt_wallet')>USDT Wallet</option>
              <option value="bank_transfer" @selected(old('method') === 'bank_transfer')>Bank Transfer</option>
            </select>
            @error('method')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="mb-3">
            <label class="form-label">Destination</label>
            <input type="text" name="destination" class="form-control @error('destination') is-invalid @enderror" value="{{ old('destination') }}" required>
            @error('destination')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="mb-0">
            <label class="form-label">Notes</label>
            <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Submit request</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('custom-scripts')
  @if ($errors->has('amount') || $errors->has('method') || $errors->has('destination') || $errors->has('notes'))
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        var modalElement = document.getElementById('payoutRequestModal');
        if (!modalElement || !window.bootstrap) {
          return;
        }

        new bootstrap.Modal(modalElement).show();
      });
    </script>
  @endif
@endpush
