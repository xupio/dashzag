@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h4 class="mb-1">Shareholders</h4>
        <p class="text-secondary mb-0">Review live ownership by investor, miner, package, and investment status.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('dashboard.shareholders.export', array_filter(['miner' => $selectedMiner, 'status' => $selectedStatus, 'package' => $selectedPackage, 'search' => $search])) }}" class="btn btn-outline-success btn-icon-text">
          <i data-lucide="download" class="btn-icon-prepend"></i> Export CSV
        </a>
        <a href="{{ route('dashboard.analytics') }}" class="btn btn-outline-primary btn-icon-text">
          <i data-lucide="bar-chart-3" class="btn-icon-prepend"></i> Back to analytics
        </a>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-12 stretch-card">
    <div class="card">
      <div class="card-body">
        <form method="GET" action="{{ route('dashboard.shareholders') }}" class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label">Search</label>
            <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Investor, email, miner, package">
          </div>
          <div class="col-md-3">
            <label class="form-label">Miner</label>
            <select name="miner" class="form-select">
              <option value="">All miners</option>
              @foreach ($miners as $miner)
                <option value="{{ $miner->slug }}" @selected($selectedMiner === $miner->slug)>{{ $miner->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Package</label>
            <select name="package" class="form-select">
              <option value="">All packages</option>
              @foreach ($packages as $package)
                <option value="{{ $package->slug }}" @selected($selectedPackage === $package->slug)>{{ $package->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="">All statuses</option>
              @foreach (['active', 'pending', 'closed'] as $status)
                <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Apply</button>
            <a href="{{ route('dashboard.shareholders') }}" class="btn btn-outline-secondary">Reset</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-md-4 grid-margin stretch-card"><div class="card"><div class="card-body"><p class="text-secondary mb-1">Visible investments</p><h5 class="mb-0">{{ $investments->count() }}</h5></div></div></div>
  <div class="col-md-4 grid-margin stretch-card"><div class="card"><div class="card-body"><p class="text-secondary mb-1">Visible shareholders</p><h5 class="mb-0">{{ $investments->pluck('user_id')->unique()->count() }}</h5></div></div></div>
  <div class="col-md-4 grid-margin stretch-card"><div class="card"><div class="card-body"><p class="text-secondary mb-1">Visible capital</p><h5 class="mb-0">${{ number_format((float) $investments->sum('amount'), 2) }}</h5></div></div></div>
</div>

<div class="row mb-4">
  <div class="col-lg-5 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h5 class="mb-1">Status breakdown</h5>
            <p class="text-secondary mb-0">Quick filter view of visible shareholder status types.</p>
          </div>
        </div>
        <div class="row g-3">
          @foreach ($statusBreakdown as $statusKey => $count)
            <div class="col-md-4">
              <a href="{{ route('dashboard.shareholders', array_filter(['search' => $search, 'miner' => $selectedMiner, 'package' => $selectedPackage, 'status' => $statusKey])) }}" class="text-decoration-none">
                <div class="border rounded p-3 h-100 {{ $selectedStatus === $statusKey ? 'border-primary bg-primary-subtle' : 'bg-light' }}">
                  <div class="text-secondary small text-capitalize">{{ $statusKey }}</div>
                  <div class="fw-semibold fs-4 text-dark">{{ $count }}</div>
                </div>
              </a>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-7 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h5 class="mb-1">Miner distribution</h5>
            <p class="text-secondary mb-0">How the visible shareholder rows are spread across miners.</p>
          </div>
        </div>
        @if ($minerBreakdown->isEmpty())
          <p class="text-secondary mb-0">No miner distribution data in the current view.</p>
        @else
          <div class="row g-3">
            @foreach ($minerBreakdown as $minerKey => $breakdown)
              <div class="col-md-6 col-xl-4">
                <a href="{{ route('dashboard.shareholders', array_filter(['search' => $search, 'status' => $selectedStatus, 'package' => $selectedPackage, 'miner' => $minerKey !== 'unknown' ? $minerKey : null])) }}" class="text-decoration-none">
                  <div class="border rounded p-3 h-100 {{ $selectedMiner === $minerKey ? 'border-primary bg-primary-subtle' : 'bg-light' }}">
                    <div class="text-secondary small">{{ $breakdown['name'] }}</div>
                    <div class="fw-semibold fs-4 text-dark">{{ $breakdown['count'] }}</div>
                    <div class="text-secondary small">visible rows</div>
                  </div>
                </a>
              </div>
            @endforeach
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
            <h5 class="mb-1">Ownership report</h5>
            <p class="text-secondary mb-0">Detailed investor rows across all miners and packages.</p>
          </div>
          <span class="badge bg-primary">{{ $investments->count() }} records</span>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Investor</th>
                <th>Miner</th>
                <th>Package</th>
                <th>Amount</th>
                <th>Shares</th>
                <th>Return rate</th>
                <th>Status</th>
                <th>Subscribed</th>
                <th class="text-end">Profile</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($investments as $investment)
                <tr>
                  <td>
                    <div class="fw-semibold">{{ $investment->user?->name }}</div>
                    <div class="text-secondary small">{{ $investment->user?->email }}</div>
                  </td>
                  <td>{{ $investment->miner?->name ?? '—' }}</td>
                  <td>{{ $investment->package?->name ?? '—' }}</td>
                  <td>${{ number_format((float) $investment->amount, 2) }}</td>
                  <td>{{ number_format((int) $investment->shares_owned) }}</td>
                  <td>{{ number_format(((float) $investment->monthly_return_rate + (float) $investment->level_bonus_rate) * 100, 2) }}%</td>
                  <td><span class="badge bg-light text-dark text-capitalize">{{ $investment->status }}</span></td>
                  <td>{{ $investment->subscribed_at?->format('M d, Y H:i') }}</td>
                  <td class="text-end">
                    @if ($investment->user)
                      <a href="{{ route('dashboard.investors.show', [$investment->user, 'from' => 'shareholders']) }}" class="btn btn-outline-primary btn-sm">Open profile</a>
                    @else
                      <span class="text-secondary">—</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="9" class="text-center text-secondary py-4">No shareholder records match the current filters.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
