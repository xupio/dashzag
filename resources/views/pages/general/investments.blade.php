@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h4 class="mb-1">My Investments</h4>
        <p class="text-secondary mb-0">Review every mining package you bought, your owned shares, expected return, and current status.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('general.sell-products') }}" class="btn btn-primary btn-icon-text">
          <i data-lucide="shopping-cart" class="btn-icon-prepend"></i> Buy more shares
        </a>
        <a href="{{ route('dashboard.wallet') }}" class="btn btn-outline-primary btn-icon-text">
          <i data-lucide="wallet" class="btn-icon-prepend"></i> Open wallet
        </a>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-md-3 grid-margin stretch-card"><div class="card"><div class="card-body"><p class="text-secondary mb-1">Active investments</p><h4 class="mb-0">{{ $activeInvestments->count() }}</h4></div></div></div>
  <div class="col-md-3 grid-margin stretch-card"><div class="card"><div class="card-body"><p class="text-secondary mb-1">Total invested</p><h4 class="mb-0">${{ number_format($totalInvested, 2) }}</h4></div></div></div>
  <div class="col-md-3 grid-margin stretch-card"><div class="card"><div class="card-body"><p class="text-secondary mb-1">Owned shares</p><h4 class="mb-0">{{ number_format($totalSharesOwned) }}</h4></div></div></div>
  <div class="col-md-3 grid-margin stretch-card"><div class="card"><div class="card-body"><p class="text-secondary mb-1">Available earnings</p><h4 class="mb-0">${{ number_format($availableEarnings, 2) }}</h4></div></div></div>
</div>

<div class="row mb-4">
  <div class="col-12 stretch-card">
    <div class="card">
      <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
          <h5 class="mb-1">Expected monthly return</h5>
          <p class="text-secondary mb-0">This is based on your active investment amounts plus your current level and team bonus rates.</p>
        </div>
        <div class="display-6 fw-semibold text-primary">${{ number_format($expectedMonthlyEarnings, 2) }}</div>
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
            <h5 class="mb-1">Investment history</h5>
            <p class="text-secondary mb-0">Every package you subscribed to across the mining platform.</p>
          </div>
          <span class="badge bg-primary">{{ $investments->count() }} investments</span>
        </div>

        @if ($investments->isEmpty())
          <div class="text-center py-5">
            <h5 class="mb-2">No investments yet</h5>
            <p class="text-secondary mb-3">You have not subscribed to any mining package yet.</p>
            <a href="{{ route('general.sell-products') }}" class="btn btn-primary">Start investing</a>
          </div>
        @else
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>Package</th>
                  <th>Miner</th>
                  <th>Amount</th>
                  <th>Shares</th>
                  <th>Return rate</th>
                  <th>Status</th>
                  <th>Subscribed</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($investments as $investment)
                  <tr>
                    <td>
                      <div class="fw-semibold">{{ $investment->package?->name ?? 'Package removed' }}</div>
                      <div class="text-secondary small">Level bonus {{ number_format((float) $investment->level_bonus_rate * 100, 2) }}% | Team bonus {{ number_format((float) $investment->team_bonus_rate * 100, 2) }}%</div>
                    </td>
                    <td>{{ $investment->miner?->name ?? '—' }}</td>
                    <td>${{ number_format((float) $investment->amount, 2) }}</td>
                    <td>{{ number_format((int) $investment->shares_owned) }}</td>
                    <td>{{ number_format(((float) $investment->monthly_return_rate + (float) $investment->level_bonus_rate + (float) $investment->team_bonus_rate) * 100, 2) }}%</td>
                    <td><span class="badge {{ $investment->status === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ str($investment->status)->title() }}</span></td>
                    <td>{{ $investment->subscribed_at?->format('M d, Y h:i A') }}</td>
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
