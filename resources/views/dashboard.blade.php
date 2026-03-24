@extends('layout.master')

@push('plugin-styles')
  <link href="{{ asset('build/plugins/flatpickr/flatpickr.min.css') }}" rel="stylesheet" />
@endpush

@section('content')
@php
  $powerBadgeClasses = [
      'secondary' => 'bg-secondary-subtle text-secondary',
      'info' => 'bg-info-subtle text-info',
      'primary' => 'bg-primary-subtle text-primary',
      'warning' => 'bg-warning-subtle text-warning',
      'success' => 'bg-success-subtle text-success',
  ];
  $powerFrameClasses = [
      'secondary' => 'border-secondary-subtle',
      'info' => 'border-info-subtle',
      'primary' => 'border-primary-subtle',
      'warning' => 'border-warning-subtle',
      'success' => 'border-success-subtle',
  ];
@endphp
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin gap-3">
  <div>
    <h4 class="mb-1">{{ $miner->name }} Overview</h4>
    <p class="text-secondary mb-0">Track live production, share pricing, miner capacity, and overall package performance.</p>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a href="{{ route('dashboard.buy-shares') }}?miner={{ $miner->slug }}" class="btn btn-primary btn-sm">Buy shares</a>
    <a href="{{ route('dashboard.miner-report', ['miner' => $miner->slug]) }}" class="btn btn-outline-secondary btn-sm">Open Daily Miner Report</a>
    <a href="{{ route('dashboard.profile') }}" class="btn btn-outline-primary btn-sm">Personal profile</a>
  </div>
</div>

@if (($miners ?? collect())->count() > 1)
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div>
            <h6 class="mb-1">Miner switcher</h6>
            <p class="text-secondary mb-0">Compare all available mining units and review each one separately.</p>
          </div>
          <div class="d-flex flex-wrap gap-2">
            @foreach ($miners as $networkMiner)
              <a href="{{ route('dashboard') }}?miner={{ $networkMiner->slug }}" class="btn {{ $networkMiner->id === $miner->id ? 'btn-primary' : 'btn-outline-primary' }} btn-sm">
                {{ $networkMiner->name }}
              </a>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>
@endif

<div class="row">
  <div class="col-md-3 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <p class="text-secondary mb-1">Daily output</p>
        <h3 class="mb-2">${{ number_format((float) $miner->daily_output_usd, 2) }}</h3>
        <div class="d-flex align-items-center gap-2 text-success">
          <i data-lucide="activity" class="icon-sm"></i>
          <span>{{ number_format((float) collect($performanceUptimeData)->last(), 2) }}% uptime today</span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <p class="text-secondary mb-1">Share price</p>
        <h3 class="mb-2">${{ number_format((float) $miner->share_price, 2) }}</h3>
        <div class="text-secondary small">Base monthly return: {{ number_format((float) $miner->base_monthly_return_rate * 100, 2) }}%</div>
        <div class="text-secondary small">Package returns follow the miner base rate plus package uplift. See Buy Shares for the full breakdown.</div>
      </div>
    </div>
  </div>
  <div class="col-md-3 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <p class="text-secondary mb-1">Shares available</p>
        <h3 class="mb-2">{{ number_format($availableShares) }} / {{ number_format($miner->total_shares) }}</h3>
        <div class="progress" style="height: 8px;">
          <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $miner->total_shares > 0 ? min(($sharesSold / $miner->total_shares) * 100, 100) : 0 }}%"></div>
        </div>
        <div class="text-secondary small mt-2">{{ number_format($sharesSold) }} shares sold</div>
      </div>
    </div>
  </div>
  <div class="col-md-3 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <p class="text-secondary mb-1">Monthly output</p>
        <h3 class="mb-2">${{ number_format((float) $miner->monthly_output_usd, 2) }}</h3>
        <div class="text-secondary small">Active packages: {{ $miner->packages->count() }}</div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-xl-8 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        @php($latestPerformanceLog = $livePerformanceSummary['latest_log'] ?? null)
        @php($latestPerformanceCosts = $latestPerformanceLog ? ((float) $latestPerformanceLog->electricity_cost_usd + (float) $latestPerformanceLog->maintenance_cost_usd) : 0)
        <div class="d-flex justify-content-between align-items-baseline mb-3 flex-wrap gap-2">
          <div>
            <h6 class="card-title mb-1">Live miner performance</h6>
            <p class="text-secondary mb-0">Today&apos;s miner result feeds the daily per-share distributions investors receive from this unit.</p>
          </div>
          <span class="badge bg-light text-dark">{{ $latestPerformanceLog?->logged_on?->format('M d, Y') ?? 'No snapshot yet' }}</span>
        </div>
        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <div class="border rounded p-3 h-100 bg-light">
              <div class="text-secondary small">Current hashrate</div>
              <div class="fs-4 fw-semibold">{{ number_format((float) ($latestPerformanceLog?->hashrate_th ?? 0), 2) }} TH/s</div>
              <div class="text-secondary small">7-day avg {{ number_format((float) ($livePerformanceSummary['average_hashrate'] ?? 0), 2) }} TH/s</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="border rounded p-3 h-100 bg-light">
              <div class="text-secondary small">Current uptime</div>
              <div class="fs-4 fw-semibold">{{ number_format((float) ($latestPerformanceLog?->uptime_percentage ?? 0), 2) }}%</div>
              <div class="text-secondary small">7-day avg {{ number_format((float) ($livePerformanceSummary['average_uptime'] ?? 0), 2) }}%</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="border rounded p-3 h-100 bg-light">
              <div class="text-secondary small">Revenue per share</div>
              <div class="fs-4 fw-semibold">${{ number_format((float) ($latestPerformanceLog?->revenue_per_share_usd ?? 0), 4) }}</div>
              <div class="text-secondary small">7-day avg ${{ number_format((float) ($livePerformanceSummary['average_revenue_per_share'] ?? 0), 4) }}</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="border rounded p-3 h-100">
              <div class="text-secondary small">Today&apos;s revenue</div>
              <div class="fw-semibold fs-5">${{ number_format((float) ($latestPerformanceLog?->revenue_usd ?? 0), 2) }}</div>
              <div class="text-secondary small">7-day total ${{ number_format((float) ($livePerformanceSummary['total_revenue'] ?? 0), 2) }}</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="border rounded p-3 h-100">
              <div class="text-secondary small">Today&apos;s costs</div>
              <div class="fw-semibold fs-5">${{ number_format((float) $latestPerformanceCosts, 2) }}</div>
              <div class="text-secondary small">7-day total ${{ number_format((float) ($livePerformanceSummary['total_costs'] ?? 0), 2) }}</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="border rounded p-3 h-100">
              <div class="text-secondary small">Today&apos;s net profit</div>
              <div class="fw-semibold fs-5">${{ number_format((float) ($latestPerformanceLog?->net_profit_usd ?? 0), 2) }}</div>
              <div class="text-secondary small">Margin {{ number_format((float) ($livePerformanceSummary['margin_rate'] ?? 0), 2) }}%</div>
            </div>
          </div>
        </div>
        <div class="border rounded p-3">
          <div class="fw-semibold mb-1">7-day income engine</div>
          <div class="text-secondary small mb-3">Revenue, operating costs, and net profit move together here before the per-share amount is distributed.</div>
          <div id="minerLivePerformanceChart"></div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-4 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-3">How investor payouts are produced</h6>
        <div class="border rounded p-3 bg-light mb-3">
          <div class="fw-semibold mb-1">1. Daily miner snapshot</div>
          <div class="text-secondary small">Hashrate, uptime, revenue, electricity, and maintenance are captured for the day.</div>
        </div>
        <div class="border rounded p-3 bg-light mb-3">
          <div class="fw-semibold mb-1">2. Net profit becomes share value</div>
          <div class="text-secondary small">The miner net profit is divided by active sold shares to produce today&apos;s revenue per share.</div>
        </div>
        <div class="border rounded p-3 bg-light">
          <div class="fw-semibold mb-1">3. Investors receive daily share earnings</div>
          <div class="text-secondary small">Every active investor receives a daily earning row based on owned shares in this miner.</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-xl-8 grid-margin stretch-card">
    <div class="card overflow-hidden">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-baseline mb-3 flex-wrap gap-2">
          <div>
            <h6 class="card-title mb-1">{{ $miner->name }} production trend</h6>
            <p class="text-secondary mb-0">Last 7 days of miner revenue generated by the selected unit.</p>
          </div>
          <a href="{{ route('dashboard.miner') }}?miner={{ $miner->slug }}" class="btn btn-outline-primary btn-sm">Open miner details</a>
        </div>
        <div id="minerRevenueChart"></div>
      </div>
    </div>
  </div>
  <div class="col-xl-4 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-baseline mb-3 flex-wrap gap-2">
          <div>
            <h6 class="card-title mb-1">Share status</h6>
            <p class="text-secondary mb-0">Interactive view of sold package shares and the miner's remaining open capacity.</p>
          </div>
          <span class="badge bg-light text-dark text-capitalize">{{ $miner->status }}</span>
        </div>
        <div class="position-relative"><div id="minerShareStatusChart"></div><div class="position-absolute top-50 start-50 translate-middle text-center px-2 pe-none" style="max-width: 132px;"><div class="fw-semibold lh-sm" style="font-size: 16px;" id="minerShareStatusCenterLabel">{{ $shareStatusLabels[0] ?? 'Share status' }}</div></div></div>
        <div class="border rounded p-3 bg-light mt-3" id="minerShareStatusInfo">
          <div class="text-secondary small mb-1">Selected segment</div>
          <div class="fs-5 fw-semibold" id="minerShareStatusTitle">{{ $shareStatusLabels[0] ?? 'Share status' }}</div>
          <div class="small text-secondary" id="minerShareStatusBody">Tap or hover a segment to review how many shares it represents and how much of the miner it covers.</div>
        </div>
        <div class="mt-3">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="text-secondary small">Quick legend</div>
            <div class="text-secondary small">Share of miner</div>
          </div>
          <div class="d-flex flex-column gap-2">
            @php($shareLegendColors = ['#6571ff', '#05a34a', '#00acc1', '#fbbc06', '#ff3366', '#7987a1'])
            @foreach ($shareStatusDetails as $index => $segment)
              <div class="d-flex justify-content-between align-items-center border rounded px-2 py-2 share-status-legend-row {{ $index === 0 ? 'border-primary bg-primary-subtle' : '' }}" data-share-status-index="{{ $index }}">
                <div class="d-flex align-items-center gap-2">
                  <span class="rounded-circle d-inline-block" style="width: 12px; height: 12px; background-color: {{ $shareLegendColors[$index % count($shareLegendColors)] }};"></span>
                  <div>
                    <div class="fw-semibold small mb-0">{{ $segment['label'] }}</div>
                    <div class="text-secondary" style="font-size: 12px;">{{ number_format((int) $segment['shares']) }} shares</div>
                  </div>
                </div>
                <span class="badge {{ $index === 0 ? 'bg-primary text-white' : 'bg-light text-dark' }} share-status-legend-badge">{{ number_format((float) $segment['utilization'], 2) }}%</span>
              </div>
            @endforeach
          </div>
        </div>
        <div class="row g-3 mt-1">
          <div class="col-6">
            <div class="border rounded p-2 h-100">
              <div class="text-secondary small">Sold capacity</div>
              <div class="fw-semibold">{{ number_format($sharesSold) }} shares</div>
            </div>
          </div>
          <div class="col-6">
            <div class="border rounded p-2 h-100">
              <div class="text-secondary small">Utilization</div>
              <div class="fw-semibold">{{ $miner->total_shares > 0 ? number_format(min(($sharesSold / $miner->total_shares) * 100, 100), 2) : '0.00' }}%</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-lg-6 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-3">Miner stats</h6>
        <div id="minerStatsChart"></div>
      </div>
    </div>
  </div>
  <div class="col-lg-6 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-baseline mb-3 flex-wrap gap-2">
          <h6 class="card-title mb-0">Package highlights</h6>
          <a href="{{ route('dashboard.buy-shares') }}?miner={{ $miner->slug }}" class="btn btn-outline-success btn-sm">View packages</a>
        </div>
        <div class="table-responsive">
          <table class="table table-borderless mb-0">
            <tbody>
              @forelse ($miner->packages as $package)
                <tr>
                  <td class="ps-0">
                    <div class="fw-semibold">{{ $package->name }}</div>
                    <div class="text-secondary small">{{ $package->shares_count }} shares included</div>
                  </td>
                  <td class="text-end">
                    <div class="fw-semibold">${{ number_format((float) $package->price, 2) }}</div>
                    <div class="text-secondary small">{{ number_format((float) $package->monthly_return_rate * 100, 2) }}% monthly return</div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="2" class="ps-0 text-secondary">No active packages are assigned to this miner yet.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h6 class="card-title mb-1">Investor pipeline</h6>
            <p class="text-secondary mb-0">See who is actively invested in this miner and open their investor profile for more detail.</p>
            <div class="d-flex align-items-center gap-2 flex-wrap mt-2">
              <span class="badge bg-success-subtle text-success">Weekly winner</span>
              <span class="badge bg-warning-subtle text-warning">Monthly champion</span>
              <span class="text-secondary small">These badges reflect the current Hall of Fame leaders.</span>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-light text-dark">{{ $minerInvestorPipeline->count() }} investor{{ $minerInvestorPipeline->count() === 1 ? '' : 's' }}</span>
            <a href="{{ route('dashboard.hall-of-fame') }}" class="btn btn-outline-primary btn-sm">Open Hall of Fame</a>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Investor</th>
                <th>Current package</th>
                <th>Shares</th>
                <th>Capital</th>
                <th>Return rate</th>
                <th>Joined miner</th>
                <th class="text-end">Profile</th>
              </tr>
            </thead>
              <tbody>
                @forelse ($minerInvestorPipeline as $pipelineInvestor)
                  @php($pipelinePower = $pipelineInvestor['profile_power'])
                  @php($isWeeklyWinner = ($weeklyHallOfFameWinnerId ?? null) === $pipelineInvestor['user']->id)
                  @php($isMonthlyChampion = ($monthlyHallOfFameChampionId ?? null) === $pipelineInvestor['user']->id)
                  <tr>
                    <td>
                      <div class="border rounded p-2 {{ !empty($pipelinePower) ? ($powerFrameClasses[$pipelinePower['rank_accent']] ?? 'border-primary-subtle') : '' }}">
                        <div class="fw-semibold">{{ $pipelineInvestor['user']->name }}</div>
                        <div class="text-secondary small">{{ $pipelineInvestor['user']->displayEmail() }}</div>
                        @if (!empty($pipelinePower))
                          <div class="mt-2 d-flex gap-2 flex-wrap">
                            <span class="badge bg-light text-dark">Power {{ $pipelinePower['score'] }}/100</span>
                            <span class="badge {{ $powerBadgeClasses[$pipelinePower['rank_accent']] ?? 'bg-primary-subtle text-primary' }}">{{ $pipelinePower['rank_label'] }}</span>
                            @foreach (($pipelineInvestor['reward_cap_badges'] ?? []) as $rewardCapBadge)
                              <span class="badge {{ $rewardCapBadge['class'] }}">{{ $rewardCapBadge['label'] }}</span>
                            @endforeach
                            @if ($isWeeklyWinner)
                              <span class="badge bg-success">Weekly winner</span>
                            @endif
                            @if ($isMonthlyChampion)
                              <span class="badge bg-warning text-dark">Monthly champion</span>
                            @endif
                          </div>
                        @endif
                      </div>
                  </td>
                  <td>
                    <div class="fw-semibold">{{ $pipelineInvestor['package_name'] }}</div>
                    <div class="text-secondary small">{{ $pipelineInvestor['active_positions'] }} active position{{ $pipelineInvestor['active_positions'] === 1 ? '' : 's' }}</div>
                  </td>
                  <td>{{ number_format((int) $pipelineInvestor['shares_owned']) }}</td>
                  <td>${{ number_format((float) $pipelineInvestor['capital_committed'], 2) }}</td>
                  <td>{{ number_format((float) $pipelineInvestor['expected_return_rate'], 2) }}%</td>
                  <td>{{ optional($pipelineInvestor['latest_subscribed_at'])->format('M d, Y') ?? '—' }}</td>
                  <td class="text-end">
                    <a href="{{ route('dashboard.investors.show', $pipelineInvestor['user']) }}" class="btn btn-outline-primary btn-sm">Open profile</a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center text-secondary py-4">No active investors are assigned to this miner yet.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="row">
  <div class="col-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h6 class="card-title mb-1">Recent performance logs</h6>
            <p class="text-secondary mb-0">A quick client-facing snapshot of the miner's most recent recorded performance.</p>
          </div>
          <a href="{{ route('dashboard.miner') }}?miner={{ $miner->slug }}" class="btn btn-outline-primary btn-sm">Open full miner page</a>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Date</th>
                <th>Revenue</th>
                <th>Hashrate</th>
                <th>Uptime</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($recentPerformanceLogs as $log)
                <tr>
                  <td>{{ $log->logged_on?->format('M d, Y') }}</td>
                  <td>${{ number_format((float) $log->revenue_usd, 2) }}</td>
                  <td>{{ number_format((float) $log->hashrate_th, 2) }} TH/s</td>
                  <td>{{ number_format((float) $log->uptime_percentage, 2) }}%</td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="text-secondary">No performance logs are available yet for this miner.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="alert alert-light border mt-3 mb-0">
          This overview stays focused on miner-wide information. Personal investment and referral details live in your profile page, while the full technical/admin controls stay in the Miner page.
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('plugin-scripts')
  <script src="{{ asset('build/plugins/flatpickr/flatpickr.min.js') }}"></script>
  <script src="{{ asset('build/plugins/apexcharts/apexcharts.min.js') }}"></script>
@endpush

@push('custom-scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (!window.ApexCharts) {
        return;
      }

      const revenueElement = document.querySelector('#minerRevenueChart');
      if (revenueElement) {
        new ApexCharts(revenueElement, {
          chart: {
            type: 'area',
            height: 320,
            toolbar: { show: false }
          },
          series: [{
            name: 'Revenue (USD)',
            data: @json($performanceRevenueData)
          }],
          xaxis: {
            categories: @json($performanceLabels)
          },
          stroke: {
            curve: 'smooth',
            width: 3
          },
          dataLabels: { enabled: false },
          colors: ['#6571ff'],
          fill: {
            type: 'gradient',
            gradient: {
              opacityFrom: 0.35,
              opacityTo: 0.05
            }
          }
        }).render();
      }

      const livePerformanceElement = document.querySelector('#minerLivePerformanceChart');
      if (livePerformanceElement) {
        new ApexCharts(livePerformanceElement, {
          chart: {
            type: 'line',
            height: 290,
            toolbar: { show: false }
          },
          series: [{
            name: 'Revenue',
            data: @json($performanceRevenueData)
          }, {
            name: 'Costs',
            data: @json($performanceCostData)
          }, {
            name: 'Net profit',
            data: @json($performanceNetProfitData)
          }],
          xaxis: {
            categories: @json($performanceLabels)
          },
          stroke: {
            curve: 'smooth',
            width: 3
          },
          dataLabels: { enabled: false },
          colors: ['#6571ff', '#fbbc06', '#05a34a'],
          legend: {
            position: 'top',
            horizontalAlign: 'left'
          },
          grid: {
            borderColor: 'rgba(101, 113, 255, 0.12)',
            strokeDashArray: 4
          },
          yaxis: {
            labels: {
              formatter: function (value) {
                return '$' + Number(value).toFixed(2);
              }
            }
          },
          tooltip: {
            y: {
              formatter: function (value) {
                return '$' + Number(value).toFixed(2);
              }
            }
          }
        }).render();
      }

      const shareStatusDetails = @json($shareStatusDetails);
      const shareStatusElement = document.querySelector('#minerShareStatusChart');
      const shareStatusTitle = document.querySelector('#minerShareStatusTitle');
      const shareStatusBody = document.querySelector('#minerShareStatusBody');
      const shareStatusCenterLabel = document.querySelector('#minerShareStatusCenterLabel');
      const shareStatusLegendRows = document.querySelectorAll('.share-status-legend-row');

      const highlightShareStatusLegend = (index) => {
        shareStatusLegendRows.forEach((row) => {
          const isActive = Number(row.dataset.shareStatusIndex) === index;
          row.classList.toggle('border-primary', isActive);
          row.classList.toggle('bg-primary-subtle', isActive);

          const badge = row.querySelector('.share-status-legend-badge');
          if (badge) {
            badge.classList.toggle('bg-primary', isActive);
            badge.classList.toggle('text-white', isActive);
            badge.classList.toggle('bg-light', !isActive);
            badge.classList.toggle('text-dark', !isActive);
          }
        });
      };

      const updateShareStatusInfo = (index) => {
        const detail = shareStatusDetails[index];
        if (!detail || !shareStatusTitle || !shareStatusBody) {
          return;
        }

        highlightShareStatusLegend(index);
        if (shareStatusCenterLabel) {
          shareStatusCenterLabel.textContent = detail.label;
        }
        shareStatusTitle.textContent = detail.label;

        if (detail.type === 'available') {
          shareStatusBody.textContent = `${Number(detail.shares).toLocaleString()} shares are still available to purchase, which is ${Number(detail.utilization).toFixed(2)}% of {{ $miner->name }}.`;
          return;
        }

        shareStatusBody.textContent = `${Number(detail.shares).toLocaleString()} shares are active in this package across ${Number(detail.investors).toLocaleString()} investor(s), representing ${Number(detail.utilization).toFixed(2)}% of the miner and $${Number(detail.capital).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} in committed capital.`;
      };

      if (shareStatusElement) {
        new ApexCharts(shareStatusElement, {
          chart: {
            type: 'donut',
            height: 340,
            toolbar: { show: false },
            events: {
              dataPointMouseEnter: function (_event, _chart, config) {
                updateShareStatusInfo(config.dataPointIndex);
              },
              dataPointSelection: function (_event, _chart, config) {
                updateShareStatusInfo(config.dataPointIndex);
              }
            }
          },
          labels: @json($shareStatusLabels),
          series: @json($shareStatusSeries),
          legend: {
            position: 'bottom'
          },
          stroke: {
            width: 0
          },
          dataLabels: {
            enabled: false
          },
          colors: ['#6571ff', '#05a34a', '#00acc1', '#fbbc06', '#ff3366', '#7987a1'],
          plotOptions: {
            pie: {
              donut: {
                size: '62%',
                labels: {
                  show: false
                }
              }
            }
          },
          tooltip: {
            y: {
              formatter: function (value) {
                return `${Number(value).toLocaleString()} shares`;
              }
            }
          }
        }).render();

        updateShareStatusInfo(0);
      }

      const statsElement = document.querySelector('#minerStatsChart');
      if (statsElement) {
        new ApexCharts(statsElement, {
          chart: {
            type: 'line',
            height: 320,
            toolbar: { show: false }
          },
          series: [
            {
              name: 'Hashrate (TH/s)',
              data: @json($performanceHashrateData)
            },
            {
              name: 'Uptime %',
              data: @json($performanceUptimeData)
            }
          ],
          xaxis: {
            categories: @json($performanceLabels)
          },
          stroke: {
            curve: 'smooth',
            width: 3
          },
          dataLabels: { enabled: false },
          colors: ['#00acc1', '#05a34a']
        }).render();
      }
    });
  </script>
@endpush

















