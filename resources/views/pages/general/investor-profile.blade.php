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
  <div class="col-12">
    <div class="card border-0 shadow-sm overflow-hidden">
      <div class="card-body p-0">
        <div class="row g-0">
          <div class="col-xl-4">
            <div class="h-100 p-4 p-lg-5 text-white" style="background: linear-gradient(135deg, #274690 0%, #6571ff 100%);">
              <div class="text-uppercase small opacity-75 mb-2">Investor power</div>
              <div class="d-flex align-items-end gap-3 mb-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-white bg-opacity-10 border border-white border-opacity-25" style="width: 58px; height: 58px;">
                  <i data-lucide="{{ $profilePower['rank_icon'] }}" class="icon-lg"></i>
                </div>
                <div class="display-4 fw-bold mb-0">{{ $profilePower['score'] }}</div>
                <div class="pb-2">/ 100</div>
              </div>
              <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
                <span class="badge bg-white text-dark">{{ $profilePower['rank_label'] }}</span>
                <span class="small opacity-75">{{ $displayTierName }}</span>
              </div>
              <p class="mb-4 opacity-75">This public profile strength reflects network traction, active investor growth, and mining commitment inside ZagChain.</p>
              <div class="small opacity-75 mb-1">Next rank target</div>
              <div class="fw-semibold mb-2">
                {{ $profilePower['next_rank_label'] }}
                @if ($profilePower['points_to_next_rank'] > 0)
                  <span class="fw-normal opacity-75">· {{ $profilePower['points_to_next_rank'] }} points to go</span>
                @endif
              </div>
              <div class="progress bg-white bg-opacity-25" style="height: 8px;">
                <div class="progress-bar bg-white" role="progressbar" style="width: {{ $profilePower['progress_within_rank'] }}%"></div>
              </div>
            </div>
          </div>
          <div class="col-xl-8">
            <div class="p-4 p-lg-5">
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
                <div>
                  <h5 class="mb-1">Strength breakdown</h5>
                  <p class="text-secondary mb-0">A clearer view of why this investor profile is strong inside the network.</p>
                </div>
                <span class="badge bg-light text-dark">{{ $profilePower['active_direct_investors'] }} active direct investors</span>
              </div>
              <div class="row g-3 mb-4">
                <div class="col-md-4">
                  <div class="border rounded p-3 h-100 bg-light">
                    <div class="text-secondary small mb-1">Verified invites</div>
                    <div class="fw-semibold fs-4">{{ $profilePower['verified_invites'] }}</div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="border rounded p-3 h-100 bg-light">
                    <div class="text-secondary small mb-1">Registered referrals</div>
                    <div class="fw-semibold fs-4">{{ $profilePower['registered_referrals'] }}</div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="border rounded p-3 h-100 bg-light">
                    <div class="text-secondary small mb-1">Investment commitment</div>
                    <div class="fw-semibold fs-4">${{ number_format($profilePower['total_invested'], 0) }}</div>
                  </div>
                </div>
              </div>
              <div class="d-flex flex-column gap-3">
                @foreach ($profilePower['components'] as $component)
                  <div class="border rounded p-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                      <div>
                        <div class="fw-semibold">{{ $component['label'] }}</div>
                        <div class="text-secondary small">{{ $component['description'] }}</div>
                      </div>
                      <div class="text-end">
                        <div class="fw-semibold">{{ $component['display'] }}</div>
                        <div class="text-secondary small">+{{ $component['value'] }} pts</div>
                      </div>
                    </div>
                    <div class="progress" style="height: 8px;">
                      <div class="progress-bar bg-primary" role="progressbar" style="width: {{ min(($component['value'] / 30) * 100, 100) }}%"></div>
                    </div>
                  </div>
                @endforeach
              </div>
              <div class="row g-3 mt-1">
                <div class="col-lg-6">
                  <div class="border rounded p-3 h-100">
                    <h6 class="mb-1">Achievement badges</h6>
                    <p class="text-secondary small mb-3">Public wins that reflect this investor's traction inside ZagChain.</p>
                    <div class="d-flex flex-column gap-2">
                      @foreach ($profilePower['achievements'] as $achievement)
                        <div class="d-flex align-items-start justify-content-between gap-3 border rounded p-3 {{ $achievement['unlocked'] ? 'bg-success bg-opacity-10 border-success-subtle' : 'bg-light' }}">
                          <div class="d-flex align-items-start gap-2">
                            <div class="rounded-circle d-flex align-items-center justify-content-center {{ $achievement['unlocked'] ? 'bg-success text-white' : 'bg-white text-secondary border' }}" style="width: 38px; height: 38px;">
                              <i data-lucide="{{ $achievement['icon'] }}" class="icon-sm"></i>
                            </div>
                            <div>
                              <div class="fw-semibold">{{ $achievement['title'] }}</div>
                              <div class="text-secondary small">{{ $achievement['description'] }}</div>
                            </div>
                          </div>
                          <span class="badge {{ $achievement['unlocked'] ? 'bg-success' : 'bg-light text-dark' }}">{{ $achievement['unlocked'] ? 'Unlocked' : 'Locked' }}</span>
                        </div>
                      @endforeach
                    </div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="border rounded p-3 h-100">
                    <h6 class="mb-1">Milestone unlocks</h6>
                    <p class="text-secondary small mb-3">The visible thresholds this investor has already reached or is approaching.</p>
                    <div class="d-flex flex-column gap-2">
                      @foreach ($profilePower['milestones'] as $milestone)
                        @php
                          $milestoneBadgeClasses = match ($milestone['status']) {
                              'completed' => 'bg-success',
                              'ready' => 'bg-info',
                              'in_progress' => 'bg-warning text-dark',
                              default => 'bg-light text-dark',
                          };
                          $milestoneLabel = match ($milestone['status']) {
                              'completed' => 'Completed',
                              'ready' => 'Ready',
                              'in_progress' => 'In progress',
                              default => 'Locked',
                          };
                        @endphp
                        <div class="border rounded p-3">
                          <div class="d-flex align-items-start justify-content-between gap-3 mb-1">
                            <div>
                              <div class="fw-semibold">{{ $milestone['title'] }}</div>
                              <div class="text-secondary small">{{ $milestone['description'] }}</div>
                            </div>
                            <span class="badge {{ $milestoneBadgeClasses }}">{{ $milestoneLabel }}</span>
                          </div>
                          @if (!empty($milestone['current']))
                            <div class="text-secondary small">{{ $milestone['current'] }}</div>
                          @endif
                        </div>
                      @endforeach
                    </div>
                  </div>
                </div>
              </div>
              <div class="border rounded p-3 mt-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                  <div>
                    <h6 class="mb-1">Champion wins</h6>
                    <p class="text-secondary small mb-0">Public Hall of Fame victories across weekly momentum and monthly champion runs.</p>
                  </div>
                  <div class="d-flex gap-2 flex-wrap">
                    <span class="badge bg-success">Weekly wins: {{ $hallOfFameWinCounts['weekly'] }}</span>
                    <span class="badge bg-warning text-dark">Monthly wins: {{ $hallOfFameWinCounts['monthly'] }}</span>
                  </div>
                </div>
                @if ($recentHallOfFameWins->isEmpty())
                  <div class="text-secondary small">No public Hall of Fame wins recorded yet for this investor.</div>
                @else
                  <div class="row g-3">
                    @foreach ($recentHallOfFameWins as $win)
                      @php($isMonthlyWin = ($win->data['event_key'] ?? null) === 'hall_of_fame_monthly_winner')
                      <div class="col-lg-6">
                        <div class="border rounded p-3 h-100 {{ $isMonthlyWin ? 'bg-warning bg-opacity-10 border-warning-subtle' : 'bg-success bg-opacity-10 border-success-subtle' }}">
                          <div class="d-flex align-items-start gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center {{ $isMonthlyWin ? 'bg-warning text-dark' : 'bg-success text-white' }}" style="width: 42px; height: 42px;">
                              <i data-lucide="{{ $win->data['rank_icon'] ?? 'trophy' }}" class="icon-sm"></i>
                            </div>
                            <div>
                              <div class="fw-semibold">{{ $win->data['subject'] ?? 'Champion win' }}</div>
                              <div class="text-secondary small mb-2">{{ $win->data['message'] ?? '' }}</div>
                              <div class="small">{{ $win->data['context_value'] ?? '' }}</div>
                              <div class="small fw-semibold mt-2">{{ $win->created_at?->format('M d, Y') }}</div>
                            </div>
                          </div>
                        </div>
                      </div>
                    @endforeach
                  </div>
                @endif
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
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
