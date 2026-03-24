@extends('layout.master')

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

<div class="row">
  <div class="col-12 grid-margin">
    <div class="card border-0 shadow-sm overflow-hidden">
      <div class="card-body p-0">
        <div class="row g-0">
          <div class="col-xl-4">
            <div class="h-100 p-4 p-lg-5 text-white" style="background: linear-gradient(135deg, #274690 0%, #6571ff 100%);">
              <div class="text-uppercase small opacity-75 mb-2">ZagChain competition board</div>
              <h3 class="mb-3 text-white">Hall of Fame</h3>
              <p class="mb-4 opacity-75">A live view of the strongest accounts across all-time power, weekly momentum, and monthly branch-building performance.</p>
              <div class="d-flex gap-2 flex-wrap">
                <span class="badge bg-white text-dark">{{ $powerLeaders->count() }} all-time leaders</span>
                <span class="badge bg-white text-dark">{{ $weeklyMovers->count() }} weekly movers</span>
                <span class="badge bg-white text-dark">{{ $monthlyChampions->count() }} monthly champions</span>
              </div>
            </div>
          </div>
          <div class="col-xl-8">
            <div class="p-4 p-lg-5">
              <div class="row g-3">
                <div class="col-md-4">
                  <div class="border rounded p-3 h-100 bg-light">
                    <div class="text-secondary small mb-1">Highest power score</div>
                    <div class="fw-semibold fs-4">{{ $powerLeaders->first()['profile_power']['score'] ?? 0 }}/100</div>
                    <div class="text-secondary small">{{ $powerLeaders->first()['user']->name ?? 'No leaders yet' }}</div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="border rounded p-3 h-100 bg-light">
                    <div class="text-secondary small mb-1">Top weekly mover</div>
                    <div class="fw-semibold fs-4">{{ $weeklyMovers->first()['weekly_momentum']['score'] ?? 0 }}/100</div>
                    <div class="text-secondary small">{{ $weeklyMovers->first()['user']->name ?? 'No movers yet' }}</div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="border rounded p-3 h-100 bg-light">
                    <div class="text-secondary small mb-1">Monthly champion</div>
                    <div class="fw-semibold fs-4">{{ $monthlyChampions->first()['monthly_momentum']['score'] ?? 0 }}/100</div>
                    <div class="text-secondary small">{{ $monthlyChampions->first()['user']->name ?? 'No champions yet' }}</div>
                  </div>
                </div>
              </div>
              <div class="mt-4 text-secondary">Use this page to compare how network growth, investor activation, and mining participation are shaping the strongest profiles in the platform.</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-xl-4 grid-margin stretch-card">
    <div class="card w-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
          <div>
            <h5 class="mb-1">All-time leaders</h5>
            <p class="text-secondary mb-0">The strongest profiles by total account power.</p>
          </div>
          <span class="badge bg-primary">Power ranking</span>
        </div>
        <div class="d-flex flex-column gap-3">
          @forelse ($powerLeaders as $leader)
            <div class="border rounded p-3 {{ $powerFrameClasses[$leader['profile_power']['rank_accent']] ?? 'border-primary-subtle' }}">
              <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                <div>
                  <div class="fw-semibold">{{ $leader['user']->name }}</div>
                  <div class="text-secondary small">{{ $leader['user']->displayEmail() }}</div>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-semibold bg-light" style="width: 38px; height: 38px;">{{ $loop->iteration }}</div>
              </div>
              <div class="d-flex gap-2 flex-wrap mb-2">
                <span class="badge bg-light text-dark">Power {{ $leader['profile_power']['score'] }}/100</span>
                <span class="badge {{ $powerBadgeClasses[$leader['profile_power']['rank_accent']] ?? 'bg-primary-subtle text-primary' }}">{{ $leader['profile_power']['rank_label'] }}</span>
              </div>
              <div class="text-secondary small mb-3">{{ $leader['profile_power']['verified_invites'] }} verified invites - {{ $leader['profile_power']['active_direct_investors'] }} active direct investors</div>
              <a href="{{ route('dashboard.investors.show', ['user' => $leader['user'], 'from' => 'overview']) }}" class="btn btn-outline-primary btn-sm">Open profile</a>
            </div>
          @empty
            <div class="text-secondary">No ranked profiles yet.</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-4 grid-margin stretch-card">
    <div class="card w-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
          <div>
            <h5 class="mb-1">Weekly winners</h5>
            <p class="text-secondary mb-0">The fastest movers based on weekly traction.</p>
          </div>
          <span class="badge bg-success">Momentum</span>
        </div>
        <div class="d-flex flex-column gap-3">
          @forelse ($weeklyMovers as $leader)
            <div class="border rounded p-3">
              <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                <div>
                  <div class="fw-semibold">{{ $leader['user']->name }}</div>
                  <div class="text-secondary small">{{ $leader['user']->displayEmail() }}</div>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-semibold bg-light" style="width: 38px; height: 38px;">{{ $loop->iteration }}</div>
              </div>
              <div class="d-flex gap-2 flex-wrap mb-2">
                <span class="badge bg-light text-dark">Weekly {{ $leader['weekly_momentum']['score'] }}/100</span>
                <span class="badge {{ $powerBadgeClasses[$leader['profile_power']['rank_accent']] ?? 'bg-primary-subtle text-primary' }}">{{ $leader['profile_power']['rank_label'] }}</span>
              </div>
              <div class="text-secondary small mb-3">{{ $leader['weekly_momentum']['verified_invites'] }} verified - {{ $leader['weekly_momentum']['registered_referrals'] }} registered - {{ $leader['weekly_momentum']['new_active_direct_investors'] }} new investors</div>
              <a href="{{ route('dashboard.investors.show', ['user' => $leader['user'], 'from' => 'overview']) }}" class="btn btn-outline-primary btn-sm">Open profile</a>
            </div>
          @empty
            <div class="text-secondary">No weekly winners yet.</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-4 grid-margin stretch-card">
    <div class="card w-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
          <div>
            <h5 class="mb-1">Monthly champions</h5>
            <p class="text-secondary mb-0">Profiles leading the branch race this month.</p>
          </div>
          <span class="badge bg-warning text-dark">Champion board</span>
        </div>
        <div class="d-flex flex-column gap-3">
          @forelse ($monthlyChampions as $leader)
            <div class="border rounded p-3">
              <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                <div>
                  <div class="fw-semibold">{{ $leader['user']->name }}</div>
                  <div class="text-secondary small">{{ $leader['user']->displayEmail() }}</div>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-semibold bg-light" style="width: 38px; height: 38px;">{{ $loop->iteration }}</div>
              </div>
              <div class="d-flex gap-2 flex-wrap mb-2">
                <span class="badge bg-light text-dark">Monthly {{ $leader['monthly_momentum']['score'] }}/100</span>
                <span class="badge {{ $powerBadgeClasses[$leader['profile_power']['rank_accent']] ?? 'bg-primary-subtle text-primary' }}">{{ $leader['profile_power']['rank_label'] }}</span>
              </div>
              <div class="text-secondary small mb-3">{{ $leader['monthly_momentum']['verified_invites'] }} verified - {{ $leader['monthly_momentum']['new_active_direct_investors'] }} new investors - ${{ number_format($leader['monthly_momentum']['mining_income'], 2) }} miner income</div>
              <a href="{{ route('dashboard.investors.show', ['user' => $leader['user'], 'from' => 'overview']) }}" class="btn btn-outline-primary btn-sm">Open profile</a>
            </div>
          @empty
            <div class="text-secondary">No monthly champions yet.</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-xl-6 grid-margin stretch-card">
    <div class="card w-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
          <div>
            <h5 class="mb-1">Saved weekly winners</h5>
            <p class="text-secondary mb-0">Persistent weekly snapshots of the strongest momentum accounts.</p>
          </div>
          <span class="badge bg-light text-dark">{{ $weeklyWinnerHistory->count() }} saved weeks</span>
        </div>
        <div class="d-flex flex-column gap-3">
          @forelse ($weeklyWinnerHistory as $winner)
            <div class="border rounded p-3">
              <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                <div>
                  <div class="fw-semibold">{{ $winner['user']?->name ?? 'Unknown user' }}</div>
                  <div class="text-secondary small">{{ optional($winner['period_start'])->format('M d, Y') }} to {{ optional($winner['period_end'])->format('M d, Y') }}</div>
                </div>
                <span class="badge bg-success">Weekly {{ $winner['score'] }}/100</span>
              </div>
              <div class="text-secondary small mb-2">{{ $winner['rank_label'] }} - Power {{ $winner['profile_power_score'] }}/100</div>
              <div class="text-secondary small mb-3">{{ $winner['highlights']['verified_invites'] ?? 0 }} verified - {{ $winner['highlights']['registered_referrals'] ?? 0 }} registered - {{ $winner['highlights']['new_active_direct_investors'] ?? 0 }} new investors</div>
              @if ($winner['user'])
                <a href="{{ route('dashboard.investors.show', ['user' => $winner['user'], 'from' => 'overview']) }}" class="btn btn-outline-primary btn-sm">Open profile</a>
              @endif
            </div>
          @empty
            <div class="text-secondary">Weekly winners will start appearing here as the competition history grows.</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-6 grid-margin stretch-card">
    <div class="card w-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
          <div>
            <h5 class="mb-1">Saved monthly champions</h5>
            <p class="text-secondary mb-0">Persistent monthly snapshots of branch-building champions.</p>
          </div>
          <span class="badge bg-light text-dark">{{ $monthlyChampionHistory->count() }} saved months</span>
        </div>
        <div class="d-flex flex-column gap-3">
          @forelse ($monthlyChampionHistory as $winner)
            <div class="border rounded p-3">
              <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                <div>
                  <div class="fw-semibold">{{ $winner['user']?->name ?? 'Unknown user' }}</div>
                  <div class="text-secondary small">{{ optional($winner['period_start'])->format('M d, Y') }} to {{ optional($winner['period_end'])->format('M d, Y') }}</div>
                </div>
                <span class="badge bg-warning text-dark">Monthly {{ $winner['score'] }}/100</span>
              </div>
              <div class="text-secondary small mb-2">{{ $winner['rank_label'] }} - Power {{ $winner['profile_power_score'] }}/100</div>
              <div class="text-secondary small mb-3">{{ $winner['highlights']['verified_invites'] ?? 0 }} verified - {{ $winner['highlights']['new_active_direct_investors'] ?? 0 }} new investors - ${{ number_format((float) ($winner['highlights']['mining_income'] ?? 0), 2) }} miner income</div>
              @if ($winner['user'])
                <a href="{{ route('dashboard.investors.show', ['user' => $winner['user'], 'from' => 'overview']) }}" class="btn btn-outline-primary btn-sm">Open profile</a>
              @endif
            </div>
          @empty
            <div class="text-secondary">Monthly champions will start appearing here as the competition history grows.</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
