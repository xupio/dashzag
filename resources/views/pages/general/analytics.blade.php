@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h4 class="mb-1">Admin Analytics</h4>
        <p class="text-secondary mb-0">Business overview of investments, payout exposure, wallet liabilities, referral performance, and miner traction.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('dashboard.analytics.export') }}" class="btn btn-outline-success btn-icon-text">
          <i data-lucide="download" class="btn-icon-prepend"></i> Export CSV
        </a>
        <a href="{{ route('dashboard.users') }}" class="btn btn-outline-primary btn-icon-text">
          <i data-lucide="users-round" class="btn-icon-prepend"></i> Manage users
        </a>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-md-4 col-xl-2 grid-margin stretch-card"><div class="card"><div class="card-body"><p class="text-secondary mb-1">Total invested</p><h5 class="mb-0">${{ number_format($totalInvested, 2) }}</h5></div></div></div>
  <div class="col-md-4 col-xl-2 grid-margin stretch-card"><div class="card"><div class="card-body"><p class="text-secondary mb-1">Shares sold</p><h5 class="mb-0">{{ number_format($totalSharesSold) }}</h5></div></div></div>
  <div class="col-md-4 col-xl-2 grid-margin stretch-card"><div class="card"><div class="card-body"><p class="text-secondary mb-1">Shareholders</p><h5 class="mb-0">{{ $activeShareholders }}</h5></div></div></div>
  <div class="col-md-4 col-xl-2 grid-margin stretch-card"><div class="card"><div class="card-body"><p class="text-secondary mb-1">Available liability</p><h5 class="mb-0">${{ number_format($totalAvailableLiability, 2) }}</h5></div></div></div>
  <div class="col-md-4 col-xl-2 grid-margin stretch-card"><div class="card"><div class="card-body"><p class="text-secondary mb-1">Pending payouts</p><h5 class="mb-0">${{ number_format($totalPendingPayouts, 2) }}</h5></div></div></div>
  <div class="col-md-4 col-xl-2 grid-margin stretch-card"><div class="card"><div class="card-body"><p class="text-secondary mb-1">Paid out</p><h5 class="mb-0">${{ number_format($totalPaidOut, 2) }}</h5></div></div></div>
</div>

<div class="row mb-4">
  <div class="col-xl-6 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h5 class="mb-3">Top investors</h5>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead><tr><th>User</th><th>Total invested</th><th>Shares</th></tr></thead>
            <tbody>
              @foreach ($topInvestors as $user)
                <tr>
                  <td>{{ $user->name }}<div class="text-secondary small">{{ $user->email }}</div></td>
                  <td>${{ number_format((float) $user->investments->where('status', 'active')->sum('amount'), 2) }}</td>
                  <td>{{ number_format((int) $user->investments->where('status', 'active')->sum('shares_owned')) }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-6 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h5 class="mb-3">Top referrers</h5>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead><tr><th>User</th><th>Registered friends</th><th>Referral rewards</th></tr></thead>
            <tbody>
              @foreach ($topReferrers as $user)
                <tr>
                  <td>{{ $user->name }}<div class="text-secondary small">{{ $user->email }}</div></td>
                  <td>{{ $user->friendInvitations->whereNotNull('registered_at')->count() }}</td>
                  <td>${{ number_format((float) $user->earnings->whereIn('source', ['referral_registration', 'referral_subscription'])->sum('amount'), 2) }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h5 class="mb-1">Miner performance breakdown</h5>
            <p class="text-secondary mb-0">See which mining unit is attracting capital, selling shares, and converting investors fastest.</p>
          </div>
          <span class="badge bg-primary">{{ $miners->count() }} miners tracked</span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Miner</th>
                <th>Status</th>
                <th>Active investors</th>
                <th>Capital</th>
                <th>Shares sold</th>
                <th>Utilization</th>
                <th>Packages</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($miners as $trackedMiner)
                @php
                  $activeInvestments = $trackedMiner->investments->where('status', 'active');
                  $activeUsers = $activeInvestments->pluck('user_id')->unique()->count();
                  $capital = (float) $activeInvestments->sum('amount');
                  $soldShares = (int) $activeInvestments->sum('shares_owned');
                  $utilization = $trackedMiner->total_shares > 0 ? min(($soldShares / $trackedMiner->total_shares) * 100, 100) : 0;
                @endphp
                <tr>
                  <td>
                    <div class="fw-semibold">{{ $trackedMiner->name }}</div>
                    <div class="text-secondary small">{{ $trackedMiner->slug }}</div>
                  </td>
                  <td><span class="badge bg-light text-dark text-capitalize">{{ $trackedMiner->status }}</span></td>
                  <td>{{ $activeUsers }}</td>
                  <td>${{ number_format($capital, 2) }}</td>
                  <td>{{ number_format($soldShares) }} / {{ number_format($trackedMiner->total_shares) }}</td>
                  <td style="min-width: 180px;">
                    <div class="fw-semibold mb-1">{{ number_format($utilization, 2) }}%</div>
                    <div class="progress" style="height: 8px;">
                      <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $utilization }}%"></div>
                    </div>
                  </td>
                  <td>{{ $trackedMiner->packages->count() }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        @php
          $mlmTotalRewards = (float) $mlmRewardBreakdown->sum('overall_total');
        @endphp
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h5 class="mb-1">MLM payout breakdown</h5>
            <p class="text-secondary mb-0">Track how much the network engine is paying across reward levels 1 through 5.</p>
          </div>
          <span class="badge bg-dark">{{ $mlmRewardBreakdown->sum('count') }} rewards</span>
        </div>
        <div class="row g-3 mb-4">
          @foreach ($mlmRewardBreakdown as $rewardLevel)
            @php
              $shareOfPool = $mlmTotalRewards > 0 ? ($rewardLevel['overall_total'] / $mlmTotalRewards) * 100 : 0;
            @endphp
            <div class="col-md-6 col-xl">
              <div class="border rounded p-3 h-100 bg-light">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                  <span class="badge bg-primary">Level {{ $rewardLevel['level'] }}</span>
                  <span class="text-secondary small">{{ number_format($shareOfPool, 1) }}%</span>
                </div>
                <div class="fw-semibold mb-1">${{ number_format($rewardLevel['overall_total'], 2) }}</div>
                <div class="text-secondary small mb-2">{{ $rewardLevel['count'] }} reward entries</div>
                <div class="progress" style="height: 8px;">
                  <div class="progress-bar bg-primary" role="progressbar" style="width: {{ min($shareOfPool, 100) }}%"></div>
                </div>
              </div>
            </div>
          @endforeach
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Level</th>
                <th>Reward source</th>
                <th>Entries</th>
                <th>Available</th>
                <th>Paid</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($mlmRewardBreakdown as $rewardLevel)
                <tr>
                  <td><span class="badge bg-light text-dark">Level {{ $rewardLevel['level'] }}</span></td>
                  <td>{{ str($rewardLevel['source'])->replace('_', ' ')->title() }}</td>
                  <td>{{ $rewardLevel['count'] }}</td>
                  <td>${{ number_format($rewardLevel['available_total'], 2) }}</td>
                  <td>${{ number_format($rewardLevel['paid_total'], 2) }}</td>
                  <td class="fw-semibold">${{ number_format($rewardLevel['overall_total'], 2) }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-xl-6 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h5 class="mb-3">Package performance</h5>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead><tr><th>Package</th><th>Investments</th><th>Capital</th><th>Shares sold</th></tr></thead>
            <tbody>
              @foreach ($packages as $package)
                <tr>
                  <td>{{ $package->name }}</td>
                  <td>{{ $package->investments->where('status', 'active')->count() }}</td>
                  <td>${{ number_format((float) $package->investments->where('status', 'active')->sum('amount'), 2) }}</td>
                  <td>{{ number_format((int) $package->investments->where('status', 'active')->sum('shares_owned')) }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-6 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h5 class="mb-3">Selected miner summary</h5>
        @if ($miner)
          <div class="border rounded p-3 mb-3 bg-light"><div class="text-secondary small">Miner</div><div class="fw-semibold">{{ $miner->name }}</div></div>
          <div class="border rounded p-3 mb-3 bg-light"><div class="text-secondary small">Share capacity</div><div class="fw-semibold">{{ number_format($miner->total_shares) }}</div></div>
          <div class="border rounded p-3 mb-3 bg-light"><div class="text-secondary small">Daily output</div><div class="fw-semibold">${{ number_format((float) $miner->daily_output_usd, 2) }}</div></div>
          <div class="border rounded p-3 bg-light"><div class="text-secondary small">Base monthly return</div><div class="fw-semibold">{{ number_format((float) $miner->base_monthly_return_rate * 100, 2) }}%</div></div>
        @else
          <p class="text-secondary mb-0">No miner data available.</p>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection



