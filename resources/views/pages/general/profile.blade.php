@extends('layout.master')

@push('plugin-scripts')
  <script src="{{ asset('build/plugins/apexcharts/apexcharts.min.js') }}"></script>
@endpush

@section('content')
<div class="row">
  <div class="col-12 grid-margin">
    <div class="card overflow-hidden">
      <div class="position-relative" style="background: linear-gradient(135deg, #eef3ff 0%, #dfe9ff 100%); min-height: 220px;">
        <div class="position-absolute top-0 start-0 w-100 h-100" style="background: radial-gradient(circle at top right, rgba(39, 70, 144, 0.14), transparent 38%), radial-gradient(circle at bottom left, rgba(101, 113, 255, 0.16), transparent 42%);"></div>
        <div class="position-relative p-4 p-md-5 d-flex justify-content-between align-items-end flex-wrap gap-4" style="min-height: 220px;">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center border border-3 border-white shadow-sm text-white fw-bold" style="width: 84px; height: 84px; background: linear-gradient(135deg, #274690 0%, #6571ff 100%); font-size: 28px;">
              {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div>
              <div class="text-uppercase text-secondary small mb-2">Personal Profile</div>
              <h2 class="mb-1 text-dark">{{ $user->name }}</h2>
              <div class="text-secondary">{{ $user->email }}</div>
              <div class="d-flex gap-3 flex-wrap mt-3 text-secondary small">
                <span><i data-lucide="mail-check" class="icon-sm me-1"></i>{{ $user->hasVerifiedEmail() ? 'Verified email' : 'Verification pending' }}</span>
                <span><i data-lucide="badge-dollar-sign" class="icon-sm me-1"></i>Current level: {{ $displayTierName }}</span>
                <span><i data-lucide="calendar-days" class="icon-sm me-1"></i>Member since {{ optional($user->created_at)->format('M d, Y') }}</span>
              </div>
            </div>
          </div>
          <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('profile.edit') }}" class="btn btn-primary btn-icon-text">
              <i data-lucide="edit" class="btn-icon-prepend"></i> Account settings
            </a>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-primary btn-icon-text">
              <i data-lucide="layout-dashboard" class="btn-icon-prepend"></i> Back to overview
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-6 col-xl-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="text-secondary small mb-1">Total invested</div>
        <h3 class="mb-1">${{ number_format($totalInvested, 2) }}</h3>
        <div class="text-secondary small">Across all active mining packages</div>
      </div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="text-secondary small mb-1">Expected monthly earnings</div>
        <h3 class="mb-1">${{ number_format($expectedMonthlyEarnings, 2) }}</h3>
        <div class="text-secondary small">Projected from your active investments</div>
      </div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="text-secondary small mb-1">Wallet balance</div>
        <h3 class="mb-1">${{ number_format($availableEarnings, 2) }}</h3>
        <div class="text-secondary small">Available in your earnings wallet</div>
      </div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="text-secondary small mb-1">Team bonus rate</div>
        <h3 class="mb-1">{{ number_format((float) $teamBonusRate * 100, 2) }}%</h3>
        <div class="text-secondary small">Improves returns as your network grows</div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12 grid-margin stretch-card">
    <div class="card rounded w-100">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
          <div>
            <h6 class="card-title mb-1">Visual summary</h6>
            <p class="text-secondary mb-0">A simpler view of your personal investment mix and account balances.</p>
          </div>
        </div>
        <div class="row g-3">
          <div class="col-xl-6">
            <div class="border rounded p-3 h-100">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                  <div class="fw-semibold">Investment allocation</div>
                  <div class="text-secondary small">How your active capital is distributed across miners.</div>
                </div>
                <span class="badge bg-light text-dark">{{ $activeInvestments->count() }} active</span>
              </div>
              <div id="profileInvestmentChart"></div>
            </div>
          </div>
          <div class="col-xl-6">
            <div class="border rounded p-3 h-100">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                  <div class="fw-semibold">Account momentum</div>
                  <div class="text-secondary small">Capital, projected monthly return, and wallet balance.</div>
                </div>
                <span class="badge bg-light text-dark">Personal</span>
              </div>
              <div id="profileFinanceChart"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 grid-margin stretch-card">
    <div class="card rounded w-100">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
          <div>
            <h6 class="card-title mb-1">Referral pipeline</h6>
            <p class="text-secondary mb-0">Your current invite journey from pending to registered friends.</p>
          </div>
          <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('dashboard.friends') }}" class="btn btn-outline-primary btn-sm">Manage friends</a>
            <a href="{{ route('dashboard.network') }}" class="btn btn-outline-secondary btn-sm">Open network</a>
          </div>
        </div>
        <div class="border rounded p-3">
          <div id="profileReferralChart"></div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 grid-margin stretch-card">
    <div class="card rounded w-100">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
          <div>
            <h6 class="card-title mb-1">Investment summary</h6>
            <p class="text-secondary mb-0">All personal mining and account performance stays here instead of the public overview.</p>
          </div>
        </div>
        <div class="row g-3">
          <div class="col-md-4">
            <div class="border rounded p-3 h-100">
              <p class="text-secondary mb-1">Total invested</p>
              <h4 class="mb-2">${{ number_format($totalInvested, 2) }}</h4>
              <small class="text-secondary">Across all active mining packages.</small>
            </div>
          </div>
          <div class="col-md-4">
            <div class="border rounded p-3 h-100">
              <p class="text-secondary mb-1">Expected monthly earnings</p>
              <h4 class="mb-2">${{ number_format($expectedMonthlyEarnings, 2) }}</h4>
              <small class="text-secondary">Projected from your active investments.</small>
            </div>
          </div>
          <div class="col-md-4">
            <div class="border rounded p-3 h-100">
              <p class="text-secondary mb-1">Available wallet balance</p>
              <h4 class="mb-2">${{ number_format($availableEarnings, 2) }}</h4>
              <small class="text-secondary">Ready inside your earnings wallet.</small>
            </div>
          </div>
        </div>
        <div class="row g-3 mt-1">
          <div class="col-md-6">
            <div class="border rounded p-3 h-100">
              <p class="text-secondary mb-1">Active packages</p>
              <div class="fw-semibold mb-2">{{ $activeInvestments->count() }} active investment{{ $activeInvestments->count() === 1 ? '' : 's' }}</div>
              <div class="text-secondary small">
                {{ $activeInvestments->isNotEmpty() ? $activeInvestments->pluck('package.name')->unique()->implode(', ') : 'No active packages yet' }}
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="border rounded p-3 h-100">
              <p class="text-secondary mb-1">Current level</p>
              <div class="fw-semibold mb-2">{{ $displayTierName }}</div>
              <div class="text-secondary small">Base bonus: {{ number_format((float) $level->bonus_rate * 100, 2) }}%.</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 grid-margin stretch-card">
    <div class="card rounded w-100 {{ $starterProgress['has_unlocked_basic'] ? 'border border-success' : 'border border-warning' }}">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
          <div>
            <div class="d-flex align-items-center gap-2 mb-2">
              <h6 class="card-title mb-0">Free Starter mission</h6>
              <span class="badge {{ $starterProgress['has_unlocked_basic'] ? 'bg-success' : 'bg-warning text-dark' }}">
                {{ $starterProgress['has_unlocked_basic'] ? 'Basic 100 unlocked' : 'Upgrade in progress' }}
              </span>
            </div>
            <p class="text-secondary mb-0">Your personal starter mission progress is tracked here because it belongs to your account journey.</p>
          </div>
          <div class="text-md-end">
            <div class="fw-semibold">Current package path: {{ $displayTierName }}</div>
            <div class="text-secondary small">Base level bonus: {{ number_format((float) $level->bonus_rate * 100, 2) }}%</div>
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

  <div class="col-12 grid-margin stretch-card">
    <div class="card rounded w-100">
      <div class="card-body">
        <h6 class="card-title mb-3">Security and status</h6>
        <div class="row g-3">
          <div class="col-md-4">
            <div class="border rounded p-3 h-100">
              <p class="text-secondary mb-1">Email status</p>
              <h5 class="mb-2">{{ $user->hasVerifiedEmail() ? 'Verified' : 'Pending' }}</h5>
              <small class="text-secondary">Verification is required before accessing protected dashboard areas.</small>
            </div>
          </div>
          <div class="col-md-4">
            <div class="border rounded p-3 h-100">
              <p class="text-secondary mb-1">Account created</p>
              <h5 class="mb-2">{{ optional($user->created_at)->diffForHumans() }}</h5>
              <small class="text-secondary">First registered on {{ optional($user->created_at)->format('M d, Y') }}.</small>
            </div>
          </div>
          <div class="col-md-4">
            <div class="border rounded p-3 h-100">
              <p class="text-secondary mb-1">Next action</p>
              <h5 class="mb-2">Grow your team</h5>
              <small class="text-secondary">Invite active investors and raise your team bonus rate over time.</small>
            </div>
          </div>
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

      const investmentElement = document.querySelector('#profileInvestmentChart');
      if (investmentElement) {
        new ApexCharts(investmentElement, {
          chart: { type: 'donut', height: 280, toolbar: { show: false } },
          labels: @json($profileInvestmentLabels),
          series: @json($profileInvestmentSeries),
          colors: ['#6571ff', '#05a34a', '#00acc1', '#fbbc06', '#ff3366'],
          dataLabels: { enabled: false },
          legend: { position: 'bottom' },
          stroke: { width: 0 },
          plotOptions: { pie: { donut: { size: '64%', labels: { show: false } } } },
          tooltip: {
            y: { formatter: function (value) { return '$' + Number(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); } }
          }
        }).render();
      }

      const financeElement = document.querySelector('#profileFinanceChart');
      if (financeElement) {
        new ApexCharts(financeElement, {
          chart: { type: 'bar', height: 280, toolbar: { show: false } },
          series: [{ name: 'USD', data: @json($profileFinanceSeries) }],
          xaxis: { categories: @json($profileFinanceLabels) },
          colors: ['#274690'],
          dataLabels: { enabled: false },
          plotOptions: { bar: { borderRadius: 8, columnWidth: '48%' } },
          tooltip: {
            y: { formatter: function (value) { return '$' + Number(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); } }
          }
        }).render();
      }

      const referralElement = document.querySelector('#profileReferralChart');
      if (referralElement) {
        new ApexCharts(referralElement, {
          chart: { type: 'area', height: 260, toolbar: { show: false } },
          series: [{ name: 'Contacts', data: @json($profileReferralSeries) }],
          xaxis: { categories: @json($profileReferralLabels) },
          colors: ['#05a34a'],
          dataLabels: { enabled: false },
          stroke: { curve: 'smooth', width: 3 },
          fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.05 } }
        }).render();
      }
    });
  </script>
@endpush
