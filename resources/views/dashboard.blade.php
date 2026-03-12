@extends('layout.master')

@push('plugin-styles')
  <link href="{{ asset('build/plugins/flatpickr/flatpickr.min.css') }}" rel="stylesheet" />
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin gap-3">
  <div>
    <h4 class="mb-1">{{ $miner->name }} Mining Dashboard</h4>
    <p class="text-secondary mb-0">Track live production, share availability, and your personal mining position.</p>
  </div>
  <div class="text-md-end">
    @php
      $displayTierName = $user->account_type === 'starter'
        ? ($user->investments->firstWhere('package.slug', \App\Support\MiningPlatform::FREE_STARTER_PACKAGE_SLUG)?->package?->name ?? 'Free Starter')
        : $level->name;
    @endphp
    <div class="fw-semibold">Current level: <span class="text-primary">{{ $displayTierName }}</span></div>
    <div class="text-secondary small">Bonus rate: {{ number_format((float) $level->bonus_rate * 100, 2) }}%</div>
  </div>
</div>

@if (($miners ?? collect())->count() > 1)
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div>
            <h6 class="mb-1">Miner switcher</h6>
            <p class="text-secondary mb-0">Compare available mining units and monitor each one separately.</p>
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
  <div class="col-md-4 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <p class="text-secondary mb-1">{{ $miner->name }} daily output</p>
        <h3 class="mb-2">${{ number_format((float) $miner->daily_output_usd, 2) }}</h3>
        <div class="d-flex align-items-center gap-2 text-success">
          <i data-lucide="activity" class="icon-sm"></i>
          <span>{{ number_format((float) collect($performanceUptimeData)->last(), 2) }}% uptime today</span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-4 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <p class="text-secondary mb-1">Shares available</p>
        <h3 class="mb-2">{{ number_format($availableShares) }} / {{ number_format($miner->total_shares) }}</h3>
        <div class="progress" style="height: 8px;">
          <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $miner->total_shares > 0 ? min(($sharesSold / $miner->total_shares) * 100, 100) : 0 }}%"></div>
        </div>
        <div class="text-secondary small mt-2">{{ number_format($sharesSold) }} shares already sold</div>
      </div>
    </div>
  </div>
  <div class="col-md-4 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <p class="text-secondary mb-1">Your expected monthly earnings</p>
        <h3 class="mb-2">${{ number_format($expectedMonthlyEarnings, 2) }}</h3>
        <div class="text-secondary small">
          Active investment on this miner: {{ $activeInvestment?->package?->name ?? 'No active package yet' }}
        </div>
      </div>
    </div>
  </div>
</div>

@php
  $starterPackage = $starterPackage ?? \App\Support\MiningPlatform::freeStarterPackage();
  $starterProgress = $starterProgress ?? \App\Support\MiningPlatform::starterUpgradeProgress($user);
@endphp

<div class="row">
  <div class="col-12 grid-margin stretch-card">
    <div class="card {{ $starterProgress['has_unlocked_basic'] ? 'border border-success' : 'border border-warning' }}">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
          <div>
            <div class="d-flex align-items-center gap-2 mb-2">
              <h6 class="card-title mb-0">Free Starter Mission</h6>
              <span class="badge {{ $starterProgress['has_unlocked_basic'] ? 'bg-success' : 'bg-warning text-dark' }}">
                {{ $starterProgress['has_unlocked_basic'] ? 'Basic 100 unlocked' : 'Upgrade in progress' }}
              </span>
            </div>
            <p class="text-secondary mb-0">
              Start from {{ $starterPackage?->name ?? 'Free Starter' }} and unlock Basic 100 by growing your direct network.
            </p>
          </div>
          <div class="text-md-end">
            <div class="fw-semibold">Current package path: {{ $displayTierName }}</div>
            <div class="text-secondary small">
              Team bonus on paid investments: {{ number_format((float) \App\Support\MiningPlatform::teamBonusRate($user) * 100, 2) }}%
            </div>
          </div>
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <div class="border rounded p-3 h-100">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-secondary">Verified invites</span>
                <span class="fw-semibold">{{ $starterProgress['verified_invites'] }} / {{ $starterProgress['required_verified_invites'] }}</span>
              </div>
              <div class="progress" style="height: 8px;">
                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ min(($starterProgress['verified_invites'] / max($starterProgress['required_verified_invites'], 1)) * 100, 100) }}%"></div>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="border rounded p-3 h-100">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-secondary">Direct Basic 100 subscribers</span>
                <span class="fw-semibold">{{ $starterProgress['direct_basic_subscribers'] }} / {{ $starterProgress['required_direct_basic_subscribers'] }}</span>
              </div>
              <div class="progress" style="height: 8px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: {{ min(($starterProgress['direct_basic_subscribers'] / max($starterProgress['required_direct_basic_subscribers'], 1)) * 100, 100) }}%"></div>
              </div>
            </div>
          </div>
        </div>
        <div class="alert {{ $starterProgress['has_unlocked_basic'] ? 'alert-success' : 'alert-light border' }} mt-3 mb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
          <span>
            {{ $starterProgress['has_unlocked_basic'] ? 'Your referral mission is complete. Basic 100 is active on your account.' : 'Complete both goals to unlock Basic 100 automatically on your account.' }}
          </span>
          <a href="{{ route('dashboard.network') }}" class="btn btn-sm btn-outline-primary">Open referral network</a>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-xl-8 grid-margin stretch-card">
    <div class="card overflow-hidden">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-baseline mb-3">
          <div>
            <h6 class="card-title mb-1">{{ $miner->name }} production trend</h6>
            <p class="text-secondary mb-0">Last 7 days of revenue generated by the selected miner.</p>
          </div>
          <a href="{{ route('general.sell-products') }}?miner={{ $miner->slug }}" class="btn btn-primary btn-sm">Buy shares</a>
        </div>
        <div id="minerRevenueChart"></div>
      </div>
    </div>
  </div>
  <div class="col-xl-4 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-3">Your investment summary</h6>
        <div class="mb-3">
          <div class="text-secondary small">Total invested</div>
          <div class="fs-4 fw-semibold">${{ number_format($totalInvested, 2) }}</div>
        </div>
        <div class="mb-3">
          <div class="text-secondary small">Owned shares in {{ $miner->name }}</div>
          <div class="fs-4 fw-semibold">{{ number_format((int) $user->investments->where('status', 'active')->where('miner_id', $miner->id)->sum('shares_owned')) }}</div>
        </div>
        <div class="mb-3">
          <div class="text-secondary small">Base monthly return</div>
          <div class="fs-4 fw-semibold">{{ number_format((float) $miner->base_monthly_return_rate * 100, 2) }}%</div>
        </div>
        <div>
          <div class="text-secondary small">Latest package on this miner</div>
          <div class="fs-6 fw-semibold">{{ $activeInvestment?->package?->name ?? 'Not subscribed yet' }}</div>
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
        <div class="d-flex justify-content-between align-items-baseline mb-3">
          <h6 class="card-title mb-0">Referral progress</h6>
          <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('dashboard.investments') }}" class="btn btn-outline-info btn-sm">My investments</a>
            <a href="{{ route('dashboard.network') }}" class="btn btn-outline-secondary btn-sm">Referral network</a>
            <a href="{{ route('dashboard.wallet') }}" class="btn btn-outline-success btn-sm">Open wallet</a>
            <a href="{{ route('dashboard.friends') }}" class="btn btn-outline-primary btn-sm">Manage friends</a>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-borderless mb-0">
            <tbody>
              <tr>
                <td class="ps-0 text-secondary">Pending invites</td>
                <td class="text-end fw-semibold">{{ $pendingReferrals }}</td>
              </tr>
              <tr>
                <td class="ps-0 text-secondary">Verified invites</td>
                <td class="text-end fw-semibold">{{ $verifiedReferrals }}</td>
              </tr>
              <tr>
                <td class="ps-0 text-secondary">Registered friends</td>
                <td class="text-end fw-semibold text-success">{{ $registeredReferrals }}</td>
              </tr>
              <tr>
                <td class="ps-0 text-secondary">Level bonus impact</td>
                <td class="text-end fw-semibold">{{ number_format((float) $level->bonus_rate * 100, 2) }}%</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="alert alert-light border mt-3 mb-0">
          Every verified and registered friend helps unlock stronger monthly return bonuses.
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




