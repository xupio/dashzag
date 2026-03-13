@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h4 class="mb-1">Digest Monitoring</h4>
        <p class="text-secondary mb-0">Track which verified users are on daily or weekly summaries, which channels are enabled, and who has no recent digest activity.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('dashboard.notification-rules') }}" class="btn btn-outline-primary btn-icon-text">
          <i data-lucide="bell-dot" class="btn-icon-prepend"></i> Notification rules
        </a>
        <a href="{{ route('dashboard.digests.history') }}" class="btn btn-outline-secondary btn-icon-text">
          <i data-lucide="history" class="btn-icon-prepend"></i> Digest history
        </a>
        <a href="{{ route('dashboard.analytics') }}" class="btn btn-outline-secondary btn-icon-text">
          <i data-lucide="bar-chart-3" class="btn-icon-prepend"></i> Analytics
        </a>
      </div>
    </div>
  </div>
</div>

@if (session('digests_success'))
  <div class="alert alert-success">{{ session('digests_success') }}</div>
@endif

<div class="row mb-4">
  <div class="col-lg-3 stretch-card">
    <a href="{{ route('dashboard.digests', ['segment' => 'daily_only']) }}" class="card w-100 text-decoration-none {{ $activeSegment === 'daily_only' ? 'border border-primary shadow-sm' : '' }}">
      <div class="card-body">
        <p class="text-secondary mb-1">Daily digests</p>
        <h4 class="mb-2 text-body">{{ $dailyUsersCount }}</h4>
        <p class="text-secondary mb-0">Users currently scheduled for daily summaries.</p>
      </div>
    </a>
  </div>
  <div class="col-lg-3 stretch-card">
    <a href="{{ route('dashboard.digests', ['segment' => 'weekly_only']) }}" class="card w-100 text-decoration-none {{ $activeSegment === 'weekly_only' ? 'border border-primary shadow-sm' : '' }}">
      <div class="card-body">
        <p class="text-secondary mb-1">Weekly digests</p>
        <h4 class="mb-2 text-body">{{ $weeklyUsersCount }}</h4>
        <p class="text-secondary mb-0">Users currently scheduled for weekly summaries.</p>
      </div>
    </a>
  </div>
  <div class="col-lg-3 stretch-card">
    <a href="{{ route('dashboard.digests', ['segment' => 'email_enabled']) }}" class="card w-100 text-decoration-none {{ $activeSegment === 'email_enabled' ? 'border border-primary shadow-sm' : '' }}">
      <div class="card-body">
        <p class="text-secondary mb-1">Email enabled</p>
        <h4 class="mb-2 text-body">{{ $emailEnabledCount }}</h4>
        <p class="text-secondary mb-0">Users who also receive digest copies by email.</p>
      </div>
    </a>
  </div>
  <div class="col-lg-3 stretch-card">
    <a href="{{ route('dashboard.digests', ['segment' => 'no_recent_activity']) }}" class="card w-100 text-decoration-none {{ $activeSegment === 'no_recent_activity' ? 'border border-primary shadow-sm' : '' }}">
      <div class="card-body">
        <p class="text-secondary mb-1">No recent activity</p>
        <h4 class="mb-2 text-body">{{ $inactiveCount }}</h4>
        <p class="text-secondary mb-0">Users with no digest entries and no tracked updates in the current summary window.</p>
      </div>
    </a>
  </div>
</div>

<div class="row">
  <div class="col-12 stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
          <div>
            <h5 class="mb-1">Digest delivery overview</h5>
            <p class="text-secondary mb-0">Use this report to confirm which users are configured for digest delivery and whether recent summaries are reaching them.</p>
          </div>
          <div class="d-flex align-items-center flex-wrap gap-2">
            <span class="badge bg-info">{{ $digestRows->count() }} of {{ $totalDigestRowsCount }} verified users</span>
            <form method="POST" action="{{ route('dashboard.digests.bulk-send') }}" class="d-inline-flex gap-2 align-items-center">
              @csrf
              <select name="frequency" class="form-select form-select-sm" style="min-width: 110px;">
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
              </select>
              <select name="scope" class="form-select form-select-sm" style="min-width: 150px;">
                <option value="matching">Matching users</option>
                <option value="all_verified">All verified users</option>
              </select>
              <select name="segment" class="form-select form-select-sm" style="min-width: 170px;">
                <option value="all">All segments</option>
                <option value="email_enabled">Email enabled</option>
                <option value="no_recent_activity">No recent activity</option>
                <option value="daily_only">Daily only</option>
                <option value="weekly_only">Weekly only</option>
              </select>
              <button type="submit" class="btn btn-primary btn-sm">Send bulk</button>
            </form>
          </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
          <span class="text-secondary small">Quick filters:</span>
          @foreach ($segmentOptions as $segmentKey => $segmentLabel)
            <a href="{{ route('dashboard.digests', ['segment' => $segmentKey]) }}" class="badge rounded-pill text-decoration-none {{ $activeSegment === $segmentKey ? 'bg-primary' : 'bg-light text-dark border' }}">
              {{ $segmentLabel }}
            </a>
          @endforeach
        </div>

        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead>
              <tr>
                <th>User</th>
                <th>Frequency</th>
                <th>Channels</th>
                <th>Current window</th>
                <th>Recent digests</th>
                <th>Last daily</th>
                <th>Last weekly</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($digestRows as $row)
                @php
                  $digest = $row['preferences']['digest'] ?? ['in_app' => true, 'email' => false, 'frequency' => 'weekly'];
                  $summary = $row['digest_summary'];
                @endphp
                <tr>
                  <td>
                    <div class="fw-semibold">{{ $row['user']->name }}</div>
                    <div class="text-secondary small">{{ $row['user']->email }}</div>
                  </td>
                  <td>
                    <span class="badge {{ $row['frequency'] === 'daily' ? 'bg-primary' : 'bg-secondary' }}">{{ ucfirst($row['frequency']) }}</span>
                  </td>
                  <td>
                    <div class="small">In-app: <strong>{{ ($digest['in_app'] ?? false) ? 'On' : 'Off' }}</strong></div>
                    <div class="small">Email: <strong>{{ ($digest['email'] ?? false) ? 'On' : 'Off' }}</strong></div>
                  </td>
                  <td>
                    <div class="fw-semibold">{{ $summary['total'] }} updates</div>
                    <div class="text-secondary small">{{ ucfirst($summary['frequency']) }} view for {{ $summary['period_label'] }}</div>
                  </td>
                  <td>
                    <div class="fw-semibold">{{ $row['recent_digest_count'] }}</div>
                    <div class="text-secondary small">Digest notifications in last 7 days</div>
                  </td>
                  <td>{{ $row['user']->last_daily_digest_sent_at?->format('M d, Y h:i A') ?? 'Not sent yet' }}</td>
                  <td>{{ $row['user']->last_weekly_digest_sent_at?->format('M d, Y h:i A') ?? 'Not sent yet' }}</td>
                  <td>
                    @if ($row['is_inactive'])
                      <span class="badge bg-warning text-dark">No recent activity</span>
                    @else
                      <span class="badge bg-success">Active</span>
                    @endif
                  </td>
                  <td class="text-end">
                    <form method="POST" action="{{ route('dashboard.digests.send', $row['user']) }}" class="d-inline-flex gap-2 align-items-center justify-content-end">
                      @csrf
                      <select name="frequency" class="form-select form-select-sm" style="min-width: 110px;">
                        <option value="daily" {{ $row['frequency'] === 'daily' ? 'selected' : '' }}>Daily</option>
                        <option value="weekly" {{ $row['frequency'] === 'weekly' ? 'selected' : '' }}>Weekly</option>
                      </select>
                      <button type="submit" class="btn btn-outline-primary btn-sm">Send now</button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="9" class="text-center text-secondary py-4">No verified users match the selected digest segment yet.</td>
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
