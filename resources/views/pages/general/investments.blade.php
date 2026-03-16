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
        <a href="{{ route('dashboard.buy-shares') }}" class="btn btn-primary btn-icon-text">
          <i data-lucide="shopping-cart" class="btn-icon-prepend"></i> Buy more shares
        </a>
        <a href="{{ route('dashboard.miner-report') }}" class="btn btn-outline-secondary btn-icon-text">
          <i data-lucide="line-chart" class="btn-icon-prepend"></i> Daily miner report
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

<div class="row mb-4">
  <div class="col-12 stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h5 class="mb-1">Earnings activity</h5>
            <p class="text-secondary mb-0">Switch between live miner payouts, monthly returns, and referral rewards to see how your investment side is performing.</p>
          </div>
          <span class="badge bg-primary">{{ $earningsHistory->count() }} entries in {{ $earningsSourceOptions[$activeSource]['label'] }}</span>
        </div>

        <div class="row g-3 mb-4">
          @foreach ($earningsBreakdown as $breakdownKey => $breakdown)
            <div class="col-md-6 col-xl-3">
              <div class="border rounded p-3 h-100 {{ $activeSource === $breakdownKey ? 'border-primary bg-primary-subtle' : 'bg-light' }}">
                <div class="text-secondary small">{{ $breakdown['label'] }}</div>
                <div class="fw-semibold fs-4">${{ number_format((float) $breakdown['amount'], 2) }}</div>
              </div>
            </div>
          @endforeach
        </div>

        <div class="d-flex flex-wrap gap-2 mb-4">
          @foreach ($earningsSourceOptions as $sourceKey => $sourceOption)
            <a href="{{ route('dashboard.investments', ['source' => $sourceKey]) }}" class="btn btn-sm {{ $activeSource === $sourceKey ? 'btn-primary' : 'btn-outline-primary' }}">
              {{ $sourceOption['label'] }}
            </a>
          @endforeach
        </div>

        @if ($earningsHistory->isEmpty())
          <div class="rounded border p-4 bg-light text-center">
            <h6 class="mb-2">No earnings in this view yet</h6>
            <p class="text-secondary mb-0">Try another earnings source filter or wait for new miner distributions and rewards to be generated.</p>
          </div>
        @else
          <div class="row g-3 mb-4">
            <div class="col-md-4"><div class="border rounded p-3 h-100 bg-light"><div class="text-secondary small">Filtered total</div><div class="fw-semibold fs-4">${{ number_format($totalFilteredEarnings, 2) }}</div></div></div>
            <div class="col-md-4"><div class="border rounded p-3 h-100 bg-light"><div class="text-secondary small">Latest entry</div><div class="fw-semibold fs-4">${{ number_format((float) optional($earningsHistory->first())->amount, 2) }}</div></div></div>
            <div class="col-md-4"><div class="border rounded p-3 h-100 bg-light"><div class="text-secondary small">Tracked days</div><div class="fw-semibold fs-4">{{ $earningsByDay->count() }}</div></div></div>
          </div>
          <div class="row g-4 align-items-stretch">
            <div class="col-xl-7">
              <div class="border rounded p-3 h-100">
                <div class="fw-semibold mb-1">Earnings trend</div>
                <div class="text-secondary small mb-3">Daily totals for {{ strtolower($earningsSourceOptions[$activeSource]['label']) }}.</div>
                <div id="dailyEarningsHistoryChart" style="min-height: 320px;"></div>
              </div>
            </div>
            <div class="col-xl-5">
              <div class="border rounded p-3 h-100">
                <div class="fw-semibold mb-1">Recent earnings rows</div>
                <div class="text-secondary small mb-3">Every row stays linked to its miner or reward source.</div>
                <div class="table-responsive">
                  <table class="table table-sm align-middle mb-0">
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Source</th>
                        <th>Reference</th>
                        <th>Amount</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach ($earningsHistory->take(10) as $earning)
                        <tr>
                          <td>{{ $earning->earned_on?->format('M d, Y') }}</td>
                          <td>{{ str($earning->source)->replace('_', ' ')->title() }}</td>
                          <td>{{ $earning->investment?->package?->name ?? $earning->notes ?? '—' }}</td>
                          <td class="fw-semibold">${{ number_format((float) $earning->amount, 2) }}</td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-12 stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h5 class="mb-1">Live miner payout tracking</h5>
            <p class="text-secondary mb-0">See how each active miner is converting daily performance into your own share earnings.</p>
          </div>
          <span class="badge bg-primary">{{ collect($investmentLivePerformance ?? [])->count() }} active payout streams</span>
        </div>

        @if (collect($investmentLivePerformance ?? [])->isEmpty())
          <div class="rounded border p-4 bg-light text-center">
            <h6 class="mb-2">No live miner payouts yet</h6>
            <p class="text-secondary mb-0">Once you activate a package, daily share earnings from the miner will appear here with the latest profit and payout details.</p>
          </div>
        @else
          <div class="row g-3 mb-4">
            @foreach ($investmentLivePerformance as $performance)
              @php($investment = $performance['investment'])
              <div class="col-12 col-xl-6">
                <div class="border rounded p-3 h-100">
                  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                    <div>
                      <div class="fw-semibold">{{ $investment->package?->name ?? 'Package removed' }}</div>
                      <div class="text-secondary small">{{ $investment->miner?->name ?? 'Unknown miner' }} · {{ number_format((int) $investment->shares_owned) }} shares</div>
                    </div>
                    <span class="badge bg-light text-dark">{{ $performance['tracked_days'] }} payout day{{ $performance['tracked_days'] === 1 ? '' : 's' }}</span>
                  </div>
                  <div class="row g-3 mb-3">
                    <div class="col-sm-6"><div class="bg-light rounded p-3 h-100"><div class="text-secondary small">Latest daily share payout</div><div class="fw-semibold fs-5">${{ number_format((float) optional($performance['latest_earning'])->amount, 2) }}</div></div></div>
                    <div class="col-sm-6"><div class="bg-light rounded p-3 h-100"><div class="text-secondary small">7-day payout total</div><div class="fw-semibold fs-5">${{ number_format((float) $performance['seven_day_total'], 2) }}</div></div></div>
                    <div class="col-sm-6"><div class="border rounded p-3 h-100"><div class="text-secondary small">Current revenue per share</div><div class="fw-semibold">${{ number_format((float) optional($performance['latest_log'])->revenue_per_share_usd, 4) }}</div></div></div>
                    <div class="col-sm-6"><div class="border rounded p-3 h-100"><div class="text-secondary small">Current miner net profit</div><div class="fw-semibold">${{ number_format((float) optional($performance['latest_log'])->net_profit_usd, 2) }}</div></div></div>
                  </div>
                  <div class="border rounded p-3 bg-light mb-3">
                    <div class="fw-semibold mb-1">How this pays you</div>
                    <div class="text-secondary small">Net profit from {{ $investment->miner?->name ?? 'the miner' }} is divided by active sold shares, then multiplied by your {{ number_format((int) $investment->shares_owned) }} shares to create the daily payout.</div>
                  </div>
                  <div class="investment-live-trend-chart" data-series='@json($performance["trend_values"])' data-labels='@json($performance["trend_labels"])' style="min-height: 120px;"></div>
                </div>
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
            <h5 class="mb-1">Investment history</h5>
            <p class="text-secondary mb-0">Every package you subscribed to across the mining platform.</p>
          </div>
          <span class="badge bg-primary">{{ $investments->count() }} investments</span>
        </div>

        @if ($investments->isEmpty())
          <div class="text-center py-5">
            <h5 class="mb-2">No investments yet</h5>
            <p class="text-secondary mb-3">You have not subscribed to any mining package yet.</p>
            <a href="{{ route('dashboard.buy-shares') }}" class="btn btn-primary">Start investing</a>
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

@push('plugin-scripts')
  <script src="{{ asset('build/plugins/apexcharts/apexcharts.min.js') }}"></script>
@endpush

@push('custom-scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (!window.ApexCharts) {
      return;
    }

    const earningsChartElement = document.querySelector('#dailyEarningsHistoryChart');
    if (earningsChartElement) {
      const labels = @json($earningsByDay->pluck('label')->values());
      const series = @json($earningsByDay->pluck('total')->values());

      new ApexCharts(earningsChartElement, {
        chart: {
          type: 'area',
          height: 320,
          toolbar: { show: false },
          zoom: { enabled: false },
        },
        series: [{
          name: 'Earnings',
          data: series,
        }],
        colors: ['#6571ff'],
        stroke: { curve: 'smooth', width: 3 },
        dataLabels: { enabled: false },
        fill: {
          type: 'gradient',
          gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.35,
            opacityTo: 0.04,
            stops: [0, 100],
          },
        },
        grid: { borderColor: 'rgba(101, 113, 255, 0.12)', strokeDashArray: 4 },
        xaxis: { categories: labels, axisBorder: { show: false }, axisTicks: { show: false } },
        yaxis: { labels: { formatter: function (value) { return '$' + Number(value).toFixed(2); } } },
        tooltip: { y: { formatter: function (value) { return '$' + Number(value).toFixed(2); } } },
      }).render();
    }

    document.querySelectorAll('.investment-live-trend-chart').forEach(function (element) {
      const trendSeries = JSON.parse(element.dataset.series || '[]');
      const trendLabels = JSON.parse(element.dataset.labels || '[]');

      if (!trendSeries.length) {
        element.innerHTML = '<div class="text-secondary small">No tracked miner payout days yet for this position.</div>';
        return;
      }

      new ApexCharts(element, {
        chart: {
          type: 'area',
          height: 120,
          sparkline: { enabled: true },
          toolbar: { show: false },
        },
        series: [{
          name: 'Daily payout',
          data: trendSeries,
        }],
        colors: ['#05a34a'],
        stroke: { curve: 'smooth', width: 3 },
        dataLabels: { enabled: false },
        fill: {
          type: 'gradient',
          gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.28,
            opacityTo: 0.02,
            stops: [0, 100],
          },
        },
        tooltip: {
          x: {
            formatter: function (_, opts) {
              return trendLabels[opts.dataPointIndex] || '';
            }
          },
          y: {
            formatter: function (value) {
              return '$' + Number(value).toFixed(2);
            }
          }
        },
      }).render();
    });
  });
</script>
@endpush

