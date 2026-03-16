@extends('layout.master')

@push('plugin-scripts')
  <script src="{{ asset('build/plugins/apexcharts/apexcharts.min.js') }}"></script>
@endpush

@section('content')
<div class="row">
  <div class="col-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h4 class="mb-1">Daily Miner Report</h4>
        <p class="text-secondary mb-0">Review the miner day by day and see how live performance turns into share earnings.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('dashboard.miner-report.export', ['miner' => $miner->slug]) }}" class="btn btn-outline-success btn-sm">Export CSV</a>
        <a href="{{ route('dashboard.miner-report.print', ['miner' => $miner->slug]) }}" class="btn btn-outline-dark btn-sm">Print Report</a>
        <a href="{{ route('dashboard') }}?miner={{ $miner->slug }}" class="btn btn-outline-primary btn-sm">Back to overview</a>
        <a href="{{ route('dashboard.buy-shares') }}?miner={{ $miner->slug }}" class="btn btn-primary btn-sm">Buy shares</a>
      </div>
    </div>
  </div>
</div>

@if (($miners ?? collect())->count() > 1)
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div>
            <h6 class="mb-1">Miner switcher</h6>
            <p class="text-secondary mb-0">Open the daily report for any active ZagChain miner.</p>
          </div>
          <div class="d-flex flex-wrap gap-2">
            @foreach ($miners as $networkMiner)
              <a href="{{ route('dashboard.miner-report', ['miner' => $networkMiner->slug]) }}" class="btn {{ $networkMiner->id === $miner->id ? 'btn-primary' : 'btn-outline-primary' }} btn-sm">
                {{ $networkMiner->name }}
              </a>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>
@endif

<div class="row mb-4">
  <div class="col-md-3 grid-margin stretch-card"><div class="card"><div class="card-body"><p class="text-secondary mb-1">14-day revenue</p><h4 class="mb-0">${{ number_format((float) ($minerPerformanceSummary['total_revenue'] ?? 0), 2) }}</h4></div></div></div>
  <div class="col-md-3 grid-margin stretch-card"><div class="card"><div class="card-body"><p class="text-secondary mb-1">14-day net profit</p><h4 class="mb-0">${{ number_format((float) ($minerPerformanceSummary['total_net_profit'] ?? 0), 2) }}</h4></div></div></div>
  <div class="col-md-3 grid-margin stretch-card"><div class="card"><div class="card-body"><p class="text-secondary mb-1">Average uptime</p><h4 class="mb-0">{{ number_format((float) ($minerPerformanceSummary['average_uptime'] ?? 0), 2) }}%</h4></div></div></div>
  <div class="col-md-3 grid-margin stretch-card"><div class="card"><div class="card-body"><p class="text-secondary mb-1">Average per share</p><h4 class="mb-0">${{ number_format((float) ($minerPerformanceSummary['average_revenue_per_share'] ?? 0), 4) }}</h4></div></div></div>
</div>

<div class="row mb-4">
  <div class="col-xl-8 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h5 class="mb-1">{{ $miner->name }} performance trend</h5>
            <p class="text-secondary mb-0">Revenue, costs, and net profit across the latest daily performance window.</p>
          </div>
          <span class="badge bg-light text-dark">{{ $performanceLogs->count() }} tracked days</span>
        </div>
        <div id="dailyMinerReportChart"></div>
      </div>
    </div>
  </div>
  <div class="col-xl-4 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h5 class="mb-3">Your stake in this miner</h5>
        @if ($userHasStake)
          <div class="border rounded p-3 bg-light mb-3">
            <div class="text-secondary small">Active positions</div>
            <div class="fw-semibold fs-4">{{ $activeInvestmentCount }}</div>
          </div>
          <div class="border rounded p-3 bg-light mb-3">
            <div class="text-secondary small">Owned shares</div>
            <div class="fw-semibold fs-4">{{ number_format($activeSharesOwned) }}</div>
          </div>
          <div class="border rounded p-3 bg-light mb-3">
            <div class="text-secondary small">Capital in this miner</div>
            <div class="fw-semibold fs-4">${{ number_format($activeCapital, 2) }}</div>
          </div>
          <div class="border rounded p-3">
            <div class="text-secondary small">Latest daily payout</div>
            <div class="fw-semibold fs-4">${{ number_format($latestUserMinerPayout, 2) }}</div>
            <div class="text-secondary small mt-1">14-day payout total: ${{ number_format($userMinerPayoutTotal, 2) }}</div>
          </div>
        @else
          <div class="border rounded p-4 bg-light text-center">
            <h6 class="mb-2">No active stake yet</h6>
            <p class="text-secondary mb-3">Buy shares in {{ $miner->name }} to see your personal daily payout stream here.</p>
            <a href="{{ route('dashboard.buy-shares') }}?miner={{ $miner->slug }}" class="btn btn-primary btn-sm">Open packages</a>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-xl-7 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h5 class="mb-1">How daily share earnings are produced</h5>
            <p class="text-secondary mb-0">A simple view of the miner logic behind the wallet rows investors receive.</p>
          </div>
        </div>
        <div class="row g-3">
          <div class="col-md-4">
            <div class="border rounded p-3 h-100 bg-light">
              <div class="fw-semibold mb-1">1. Capture the day</div>
              <div class="text-secondary small">Hashrate, uptime, revenue, electricity, and maintenance are recorded for the miner.</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="border rounded p-3 h-100 bg-light">
              <div class="fw-semibold mb-1">2. Find net profit</div>
              <div class="text-secondary small">Daily costs are removed from revenue to produce the net profit that the miner generated.</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="border rounded p-3 h-100 bg-light">
              <div class="fw-semibold mb-1">3. Divide by active shares</div>
              <div class="text-secondary small">Net profit per share is multiplied by each user&apos;s owned shares to create the daily payout.</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-5 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h5 class="mb-1">Your payout trend</h5>
            <p class="text-secondary mb-0">If you are invested in this miner, your personal daily share payouts appear here.</p>
          </div>
        </div>
        <div id="dailyMinerPayoutChart"></div>
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
            <h5 class="mb-1">Recent daily logs</h5>
            <p class="text-secondary mb-0">Every row shows the miner result that powers daily share distributions.</p>
          </div>
          <span class="badge bg-primary">{{ $performanceLogs->count() }} rows</span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Date</th>
                <th>Revenue</th>
                <th>Costs</th>
                <th>Net profit</th>
                <th>Per share</th>
                <th>Hashrate</th>
                <th>Uptime</th>
                <th>Source</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($performanceLogs as $log)
                <tr>
                  <td>{{ $log->logged_on?->format('M d, Y') }}</td>
                  <td>${{ number_format((float) $log->revenue_usd, 2) }}</td>
                  <td>${{ number_format((float) $log->electricity_cost_usd + (float) $log->maintenance_cost_usd, 2) }}</td>
                  <td>${{ number_format((float) $log->net_profit_usd, 2) }}</td>
                  <td>${{ number_format((float) $log->revenue_per_share_usd, 4) }}</td>
                  <td>{{ number_format((float) $log->hashrate_th, 2) }} TH/s</td>
                  <td>{{ number_format((float) $log->uptime_percentage, 2) }}%</td>
                  <td><span class="badge bg-light text-dark text-capitalize">{{ str_replace('_', ' ', $log->source ?? 'manual') }}</span></td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="text-center text-secondary py-4">No daily miner logs are available yet.</td>
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

    const performanceChartElement = document.querySelector('#dailyMinerReportChart');
    if (performanceChartElement) {
      new ApexCharts(performanceChartElement, {
        chart: {
          type: 'line',
          height: 320,
          toolbar: { show: false }
        },
        series: [{
          name: 'Revenue',
          data: @json($performanceLogs->map(fn ($log) => round((float) $log->revenue_usd, 2))->values())
        }, {
          name: 'Costs',
          data: @json($performanceLogs->map(fn ($log) => round((float) $log->electricity_cost_usd + (float) $log->maintenance_cost_usd, 2))->values())
        }, {
          name: 'Net profit',
          data: @json($performanceLogs->map(fn ($log) => round((float) $log->net_profit_usd, 2))->values())
        }],
        xaxis: {
          categories: @json($performanceLogs->map(fn ($log) => $log->logged_on?->format('M d'))->values())
        },
        stroke: { curve: 'smooth', width: 3 },
        dataLabels: { enabled: false },
        colors: ['#6571ff', '#fbbc06', '#05a34a'],
        legend: { position: 'top', horizontalAlign: 'left' },
        grid: { borderColor: 'rgba(101, 113, 255, 0.12)', strokeDashArray: 4 },
        yaxis: { labels: { formatter: function (value) { return '$' + Number(value).toFixed(2); } } },
        tooltip: { y: { formatter: function (value) { return '$' + Number(value).toFixed(2); } } },
      }).render();
    }

    const payoutChartElement = document.querySelector('#dailyMinerPayoutChart');
    if (payoutChartElement) {
      const payoutSeries = @json($userMinerEarningsByDay->pluck('total')->values());
      const payoutLabels = @json($userMinerEarningsByDay->pluck('label')->values());

      if (!payoutSeries.length) {
        payoutChartElement.innerHTML = '<div class="text-secondary small">No daily payouts for this miner yet.</div>';
        return;
      }

      new ApexCharts(payoutChartElement, {
        chart: {
          type: 'area',
          height: 280,
          toolbar: { show: false }
        },
        series: [{
          name: 'Your payout',
          data: payoutSeries
        }],
        xaxis: {
          categories: payoutLabels
        },
        stroke: { curve: 'smooth', width: 3 },
        dataLabels: { enabled: false },
        colors: ['#05a34a'],
        fill: {
          type: 'gradient',
          gradient: {
            opacityFrom: 0.35,
            opacityTo: 0.05
          }
        },
        grid: { borderColor: 'rgba(5, 163, 74, 0.12)', strokeDashArray: 4 },
        yaxis: { labels: { formatter: function (value) { return '$' + Number(value).toFixed(2); } } },
        tooltip: { y: { formatter: function (value) { return '$' + Number(value).toFixed(2); } } },
      }).render();
    }
  });
</script>
@endpush
