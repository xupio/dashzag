@extends('layout.master')

@push('plugin-scripts')
  <script src="{{ asset('build/plugins/apexcharts/apexcharts.min.js') }}"></script>
@endpush

@section('content')
<div class="row">
  <div class="col-12 grid-margin">
    <div class="card overflow-hidden">
      <div class="position-relative" style="background: linear-gradient(135deg, #eef3ff 0%, #dfe9ff 100%); min-height: 210px;">
        <div class="position-absolute top-0 start-0 w-100 h-100" style="background: radial-gradient(circle at top right, rgba(39, 70, 144, 0.14), transparent 38%), radial-gradient(circle at bottom left, rgba(101, 113, 255, 0.16), transparent 42%);"></div>
        <div class="position-relative p-4 p-md-5 d-flex justify-content-between align-items-end flex-wrap gap-4" style="min-height: 210px;">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center border border-3 border-white shadow-sm text-white fw-bold" style="width: 84px; height: 84px; background: linear-gradient(135deg, #274690 0%, #6571ff 100%); font-size: 28px;">
              {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div>
              <div class="text-uppercase text-secondary small mb-2">Investor Profile</div>
              <h2 class="mb-1 text-dark">{{ $user->name }}</h2>
              <div class="text-secondary">{{ $user->email }}</div>
              <div class="d-flex gap-3 flex-wrap mt-3 text-secondary small">
                <span><i data-lucide="badge-dollar-sign" class="icon-sm me-1"></i>{{ $displayTierName }}</span>
                <span><i data-lucide="calendar-days" class="icon-sm me-1"></i>Member since {{ optional($user->created_at)->format('M d, Y') }}</span>
                <span><i data-lucide="mail-check" class="icon-sm me-1"></i>{{ $user->hasVerifiedEmail() ? 'Verified investor' : 'Verification pending' }}</span>
              </div>
            </div>
          </div>
          <div class="d-flex gap-2 flex-wrap">
            <a href="{{ $backTarget ?? route('dashboard') }}" class="btn btn-outline-primary btn-icon-text">
              <i data-lucide="layout-dashboard" class="btn-icon-prepend"></i> {{ $backLabel ?? 'Back to overview' }}
            </a>
            @if ($viewer->is($user))
              <a href="{{ route('dashboard.profile') }}" class="btn btn-primary btn-icon-text">
                <i data-lucide="user-circle" class="btn-icon-prepend"></i> Open my profile
              </a>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-6 col-xl-3">
    <div class="card h-100"><div class="card-body"><div class="text-secondary small mb-1">Total invested</div><h3 class="mb-1">${{ number_format($totalInvested, 2) }}</h3><div class="text-secondary small">Across active mining positions</div></div></div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="card h-100"><div class="card-body"><div class="text-secondary small mb-1">Expected monthly earnings</div><h3 class="mb-1">${{ number_format($expectedMonthlyEarnings, 2) }}</h3><div class="text-secondary small">Projected active return</div></div></div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="card h-100"><div class="card-body"><div class="text-secondary small mb-1">Active investments</div><h3 class="mb-1">{{ $activeInvestments->count() }}</h3><div class="text-secondary small">Open positions on the platform</div></div></div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="card h-100"><div class="card-body"><div class="text-secondary small mb-1">Team bonus rate</div><h3 class="mb-1">{{ number_format((float) $teamBonusRate * 100, 2) }}%</h3><div class="text-secondary small">Network-driven bonus layer</div></div></div>
  </div>
</div>

<div class="row">
  <div class="col-xl-5 grid-margin stretch-card">
    <div class="card w-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
          <div>
            <h6 class="card-title mb-1">Capital allocation</h6>
            <p class="text-secondary mb-0">How this investor distributes active capital across miners.</p>
          </div>
          <span class="badge bg-light text-dark">{{ $activeInvestments->count() }} active</span>
        </div>
        <div id="investorAllocationChart"></div>
      </div>
    </div>
  </div>
  <div class="col-xl-7 grid-margin stretch-card">
    <div class="card w-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
          <div>
            <h6 class="card-title mb-1">Active positions</h6>
            <p class="text-secondary mb-0">Visible miner positions and the packages currently active for this investor.</p>
          </div>
          <span class="badge bg-light text-dark">Investor view</span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Miner</th>
                <th>Package</th>
                <th>Shares</th>
                <th>Capital</th>
                <th>Return rate</th>
                <th>Subscribed</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($activeInvestments as $investment)
                <tr>
                  <td>
                    <div class="fw-semibold">{{ $investment->miner?->name ?? 'Unknown miner' }}</div>
                    <div class="text-secondary small">{{ $investment->miner?->status ?? '—' }}</div>
                  </td>
                  <td>{{ $investment->package?->name ?? '—' }}</td>
                  <td>{{ number_format((int) $investment->shares_owned) }}</td>
                  <td>${{ number_format((float) $investment->amount, 2) }}</td>
                  <td>{{ number_format(((float) $investment->monthly_return_rate + (float) $investment->level_bonus_rate + (float) $investment->team_bonus_rate) * 100, 2) }}%</td>
                  <td>{{ $investment->subscribed_at?->format('M d, Y') ?? '—' }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center text-secondary py-4">This investor does not have active positions yet.</td>
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

@push('custom-scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (!window.ApexCharts) {
      return;
    }

    const labels = @json($investorAllocationLabels);
    const series = @json($investorAllocationSeries);
    const allocationElement = document.querySelector('#investorAllocationChart');

    if (allocationElement) {
      new ApexCharts(allocationElement, {
        chart: {
          type: 'donut',
          height: 320,
          toolbar: { show: false },
        },
        labels: labels.length ? labels : ['No active capital'],
        series: series.length ? series : [1],
        colors: ['#6571ff', '#05a34a', '#00acc1', '#fbbc06', '#ff3366'],
        stroke: { width: 0 },
        legend: { position: 'bottom' },
        dataLabels: { enabled: false },
        tooltip: {
          y: {
            formatter: function (value) {
              return '$' + Number(value).toFixed(2);
            }
          }
        },
        plotOptions: {
          pie: {
            donut: {
              size: '68%',
            },
          },
        },
        noData: {
          text: 'No active investments yet',
        },
      }).render();
    }
  });
</script>
@endpush

