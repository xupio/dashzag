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
        <a href="{{ route('dashboard.analytics.export', ['miner' => $selectedMinerSlug]) }}" class="btn btn-outline-success btn-icon-text">
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
  <div class="col-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h5 class="mb-1">Profile power reward analytics</h5>
            <p class="text-secondary mb-0">Track how many investors have unlocked each reward-cap tier and how much extra monthly exposure this layer adds.</p>
          </div>
          <span class="badge bg-primary">${{ number_format($rewardCapAnalyticsSummary['total_extra_monthly_liability'], 2) }} extra monthly liability</span>
        </div>
        <div class="row g-3 mb-4">
          <div class="col-md-6 col-xl-3"><div class="border rounded p-3 h-100 bg-light"><div class="text-secondary small">Unlocked 4% cap</div><div class="fw-semibold fs-4">{{ $rewardCapAnalyticsSummary['basic_unlocked_users'] }}</div></div></div>
          <div class="col-md-6 col-xl-3"><div class="border rounded p-3 h-100 bg-light"><div class="text-secondary small">Unlocked 6% cap</div><div class="fw-semibold fs-4">{{ $rewardCapAnalyticsSummary['growth_unlocked_users'] }}</div></div></div>
          <div class="col-md-6 col-xl-3"><div class="border rounded p-3 h-100 bg-light"><div class="text-secondary small">Unlocked 7% cap</div><div class="fw-semibold fs-4">{{ $rewardCapAnalyticsSummary['scale_unlocked_users'] }}</div></div></div>
          <div class="col-md-6 col-xl-3"><div class="border rounded p-3 h-100 bg-light"><div class="text-secondary small">Extra monthly liability</div><div class="fw-semibold fs-4">${{ number_format($rewardCapAnalyticsSummary['total_extra_monthly_liability'], 2) }}</div></div></div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>User</th>
                <th>Power</th>
                <th>Unlocked caps</th>
                <th>Extra monthly liability</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($topRewardCapUsers as $rewardCapUser)
                <tr>
                  <td>
                    <div class="fw-semibold">{{ $rewardCapUser['user']->name }}</div>
                    <div class="text-secondary small">{{ $rewardCapUser['user']->email }}</div>
                  </td>
                  <td>{{ $rewardCapUser['profile_power']['score'] }}/100</td>
                  <td>
                    <div class="d-flex gap-2 flex-wrap">
                      @if ($rewardCapUser['basic_unlocked'])<span class="badge bg-primary-subtle text-primary">4% cap</span>@endif
                      @if ($rewardCapUser['growth_unlocked'])<span class="badge bg-info-subtle text-info">6% cap</span>@endif
                      @if ($rewardCapUser['scale_unlocked'])<span class="badge bg-dark text-white">7% cap</span>@endif
                    </div>
                  </td>
                  <td class="fw-semibold">${{ number_format($rewardCapUser['extra_monthly_liability'], 2) }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="text-center text-secondary py-4">No active profile-power reward exposure yet.</td>
                </tr>
              @endforelse
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
            <h5 class="mb-1">Network tree snapshot</h5>
            <p class="text-secondary mb-0">A visual view of the current sponsor structure and the visible sub-levels across the platform.</p>
          </div>
          <div class="d-flex gap-2 flex-wrap">
            <span class="badge bg-primary">{{ $networkTreeSummary['root_count'] }} roots</span>
            <span class="badge bg-light text-dark">{{ $networkTreeSummary['visible_nodes'] }} visible</span>
            <span class="badge bg-dark">Depth {{ $networkTreeSummary['max_depth'] }}</span>
          </div>
        </div>
        <form method="GET" action="{{ route('dashboard.analytics') }}" class="row g-3 align-items-end mb-3">
          <input type="hidden" name="miner" value="{{ $selectedMinerSlug }}">
          <div class="col-md-4">
            <label class="form-label">Find investor</label>
            <input type="text" name="tree_search" value="{{ $treeSearch }}" class="form-control" placeholder="Search by name or email">
          </div>
          <div class="col-md-4">
            <label class="form-label">Focus branch</label>
            <select name="tree_focus" class="form-select">
              <option value="">All visible roots</option>
              @if ($selectedTreeFocus)
                <option value="{{ $selectedTreeFocus->id }}" selected>{{ $selectedTreeFocus->name }} (selected)</option>
              @endif
              @foreach ($treeSearchResults as $treeResult)
                @if (! $selectedTreeFocus || $treeResult->id !== $selectedTreeFocus->id)
                  <option value="{{ $treeResult->id }}">{{ $treeResult->name }} - {{ $treeResult->email }}</option>
                @endif
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Tree depth</label>
            <select name="tree_depth" class="form-select">
              @foreach ([2, 3, 4, 5, 6] as $depthOption)
                <option value="{{ $depthOption }}" @selected($treeDepth === $depthOption)>Depth {{ $depthOption }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100">Apply</button>
            <a href="{{ route('dashboard.analytics', ['miner' => $selectedMinerSlug]) }}" class="btn btn-outline-secondary">Reset</a>
          </div>
          <div class="col-12">
            <a href="{{ route('dashboard.analytics.tree-export', ['miner' => $selectedMinerSlug, 'tree_search' => $treeSearch, 'tree_focus' => $selectedTreeFocus?->id, 'tree_depth' => $treeDepth]) }}" class="btn btn-outline-success btn-sm">
              Export Focused Branch CSV
            </a>
            <a href="{{ route('dashboard.analytics.tree-print', ['miner' => $selectedMinerSlug, 'tree_search' => $treeSearch, 'tree_focus' => $selectedTreeFocus?->id, 'tree_depth' => $treeDepth]) }}" target="_blank" class="btn btn-outline-primary btn-sm ms-2">
              Print Branch Summary
            </a>
          </div>
          @if ($selectedTreeFocus)
            <div class="col-12">
              <div class="text-secondary small">Focused on <strong>{{ $selectedTreeFocus->name }}</strong>. The tree now shows only this visible branch.</div>
            </div>
          @elseif($treeSearch !== '' && $treeSearchResults->isEmpty())
            <div class="col-12">
              <div class="text-secondary small">No matching investor found for this search yet.</div>
            </div>
          @endif
        </form>
        @if ($networkTree->isEmpty())
          <p class="text-secondary mb-0">The sponsor tree snapshot will appear here once referrals begin to build across the platform.</p>
        @else
          @include('pages.general.partials.network-org-chart', [
            'chartId' => 'analyticsNetworkOrgChart',
            'chartTitle' => 'Analytics Sponsor Tree',
            'chartDescription' => 'Click any investor node to understand branch strength, conversion gaps, and direct capital impact.',
            'tree' => $networkTree,
          ])
        @endif
      </div>
    </div>
  </div>
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
  <div class="col-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h5 class="mb-1">Selected miner daily performance</h5>
            <p class="text-secondary mb-0">Track the last 7 days of revenue, operating costs, net profit, and per-share earnings for {{ $miner->name }}.</p>
          </div>
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-light text-dark">Slug: {{ $selectedMinerSlug }}</span>
            <span class="badge bg-primary">{{ $selectedMinerPerformanceLogs->count() }} daily logs</span>
          </div>
        </div>
        <div class="row g-3 mb-4">
          <div class="col-md-4 col-xl-2"><div class="border rounded p-3 h-100 bg-light"><div class="text-secondary small">7-day revenue</div><div class="fw-semibold fs-5">${{ number_format($selectedMinerPerformanceSummary['total_revenue'], 2) }}</div></div></div>
          <div class="col-md-4 col-xl-2"><div class="border rounded p-3 h-100 bg-light"><div class="text-secondary small">7-day net profit</div><div class="fw-semibold fs-5">${{ number_format($selectedMinerPerformanceSummary['total_net_profit'], 2) }}</div></div></div>
          <div class="col-md-4 col-xl-2"><div class="border rounded p-3 h-100 bg-light"><div class="text-secondary small">Avg uptime</div><div class="fw-semibold fs-5">{{ number_format($selectedMinerPerformanceSummary['average_uptime'], 2) }}%</div></div></div>
          <div class="col-md-4 col-xl-2"><div class="border rounded p-3 h-100 bg-light"><div class="text-secondary small">Avg hashrate</div><div class="fw-semibold fs-5">{{ number_format($selectedMinerPerformanceSummary['average_hashrate'], 2) }} TH/s</div></div></div>
          <div class="col-md-4 col-xl-2"><div class="border rounded p-3 h-100 bg-light"><div class="text-secondary small">Avg per share</div><div class="fw-semibold fs-5">${{ number_format($selectedMinerPerformanceSummary['average_per_share'], 4) }}</div></div></div>
          <div class="col-md-4 col-xl-2"><div class="border rounded p-3 h-100 bg-light"><div class="text-secondary small">Automatic runs</div><div class="fw-semibold fs-5">{{ $selectedMinerPerformanceSummary['automatic_runs'] }}</div></div></div>
        </div>
        <div class="row g-4 align-items-stretch">
          <div class="col-xl-7">
            <div class="border rounded p-3 h-100">
              <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                <div>
                  <div class="fw-semibold">Daily trend</div>
                  <div class="text-secondary small">Revenue, costs, and net profit over the last 7 logged days.</div>
                </div>
                <span class="badge bg-dark">{{ $miner->name }}</span>
              </div>
              <div id="selectedMinerPerformanceChart" style="min-height: 320px;"></div>
            </div>
          </div>
          <div class="col-xl-5">
            <div class="border rounded p-3 h-100">
              <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                <div>
                  <div class="fw-semibold">Recent daily logs</div>
                  <div class="text-secondary small">Per-share income and operational source for the selected miner.</div>
                </div>
                <span class="badge bg-light text-dark">{{ $miner->slug }}</span>
              </div>
              <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                  <thead><tr><th>Date</th><th>Source</th><th>Net</th><th>Per share</th></tr></thead>
                  <tbody>
                    @foreach ($selectedMinerPerformanceLogs as $log)
                      <tr>
                        <td>{{ $log->logged_on?->format('M d') }}</td>
                        <td><span class="badge bg-light text-dark text-capitalize">{{ str_replace('_', ' ', $log->source ?? 'manual') }}</span></td>
                        <td>${{ number_format((float) $log->net_profit_usd, 2) }}</td>
                        <td>${{ number_format((float) $log->revenue_per_share_usd, 4) }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>
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

@push('plugin-scripts')
  <script src="{{ asset('build/plugins/apexcharts/apexcharts.min.js') }}"></script>
@endpush

@push('custom-scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (!window.ApexCharts) {
      return;
    }

    const performanceChartElement = document.querySelector('#selectedMinerPerformanceChart');
    if (!performanceChartElement) {
      return;
    }

    const labels = @json($selectedMinerPerformanceLogs->map(fn ($log) => $log->logged_on?->format('M d'))->values());
    const revenueSeries = @json($selectedMinerPerformanceLogs->map(fn ($log) => round((float) $log->revenue_usd, 2))->values());
    const costSeries = @json($selectedMinerPerformanceLogs->map(fn ($log) => round((float) $log->electricity_cost_usd + (float) $log->maintenance_cost_usd, 2))->values());
    const profitSeries = @json($selectedMinerPerformanceLogs->map(fn ($log) => round((float) $log->net_profit_usd, 2))->values());

    new ApexCharts(performanceChartElement, {
      chart: {
        type: 'line',
        height: 320,
        toolbar: { show: false },
        zoom: { enabled: false },
      },
      series: [
        { name: 'Revenue', data: revenueSeries },
        { name: 'Costs', data: costSeries },
        { name: 'Net profit', data: profitSeries },
      ],
      colors: ['#6571ff', '#ff9f43', '#10b981'],
      stroke: { width: [3, 3, 4], curve: 'smooth' },
      markers: { size: 4, hover: { size: 6 } },
      dataLabels: { enabled: false },
      grid: { borderColor: 'rgba(101, 113, 255, 0.12)', strokeDashArray: 4 },
      xaxis: { categories: labels, axisBorder: { show: false }, axisTicks: { show: false } },
      yaxis: { labels: { formatter: function (value) { return '$' + Number(value).toFixed(0); } } },
      legend: { position: 'top', horizontalAlign: 'left' },
      tooltip: { y: { formatter: function (value) { return '$' + Number(value).toFixed(2); } } }
    }).render();
  });
</script>
@endpush


