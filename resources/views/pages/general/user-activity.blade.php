@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h4 class="mb-1">User Activity</h4>
        <p class="text-secondary mb-0">Track logins, invitation volume, and time spent across pages for each user.</p>
      </div>
      <a href="{{ route('dashboard.users') }}" class="btn btn-outline-primary btn-icon-text">
        <i data-lucide="users" class="btn-icon-prepend"></i> Back to users
      </a>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <p class="text-secondary mb-1">Tracked users</p>
        <h3 class="mb-0">{{ $totalTrackedUsers }}</h3>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <p class="text-secondary mb-1">Login events</p>
        <h3 class="mb-0">{{ $totalLogins }}</h3>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <p class="text-secondary mb-1">Invitations</p>
        <h3 class="mb-0">{{ $totalInvitations }}</h3>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <p class="text-secondary mb-1">Tracked hours</p>
        <h3 class="mb-0">{{ number_format($totalTrackedHours, 1) }}</h3>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12 stretch-card">
    <div class="card">
      <div class="card-body">
        <form method="GET" action="{{ route('dashboard.user-activity') }}" class="row g-2 mb-3">
          <div class="col-md-8">
            <input type="text" name="search" class="form-control" value="{{ $activitySearch }}" placeholder="Search by name or email">
          </div>
          <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="{{ route('dashboard.user-activity') }}" class="btn btn-outline-secondary">Reset</a>
          </div>
        </form>

        @if ($activityRows->isEmpty())
          <p class="text-secondary mb-0">No user activity has been recorded yet.</p>
        @else
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>User</th>
                  <th>Logins</th>
                  <th>Last login</th>
                  <th>Invitations</th>
                  <th>Total time</th>
                  <th>Top pages</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($activityRows as $row)
                  <tr>
                    <td>
                      <div class="fw-semibold">{{ $row['user']->name }}</div>
                      <div class="text-secondary small">{{ $row['user']->email }}</div>
                    </td>
                    <td>{{ $row['login_count'] }}</td>
                    <td>{{ $row['last_login_at']?->format('M d, Y h:i A') ?? 'No logins yet' }}</td>
                    <td>{{ $row['invitation_count'] }}</td>
                    <td>{{ gmdate('H:i:s', $row['total_seconds']) }}</td>
                    <td>
                      @if ($row['top_pages']->isEmpty())
                        <span class="text-secondary small">No page activity yet</span>
                      @else
                        <div class="d-flex flex-column gap-1">
                          @foreach ($row['top_pages'] as $pageRow)
                            <div class="text-secondary small">{{ $pageRow->path }} <span class="fw-semibold text-dark">({{ gmdate('H:i:s', (int) $pageRow->total_seconds) }})</span></div>
                          @endforeach
                        </div>
                      @endif
                    </td>
                    <td class="text-end">
                      <a href="{{ route('dashboard.user-activity', ['search' => $activitySearch, 'user_id' => $row['user']->id]) }}" class="btn btn-sm btn-outline-primary">View detail</a>
                    </td>
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

@if ($selectedUser)
  <div class="row mt-4 g-3">
    <div class="col-lg-7">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
              <h5 class="mb-1">{{ $selectedUser->name }} page activity</h5>
              <p class="text-secondary mb-0">{{ $selectedUser->email }}</p>
            </div>
            <a href="{{ route('dashboard.users', ['search' => $selectedUser->email]) }}" class="btn btn-outline-secondary btn-sm">Open user record</a>
          </div>

          @if ($selectedUserPageBreakdown->isEmpty())
            <p class="text-secondary mb-0">No page activity recorded yet for this user.</p>
          @else
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead>
                  <tr>
                    <th>Page</th>
                    <th>Route</th>
                    <th>Total time</th>
                    <th class="text-end">Last seen</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($selectedUserPageBreakdown as $pageRow)
                    <tr>
                      <td>{{ $pageRow->path }}</td>
                      <td>{{ $pageRow->route_name ?: '—' }}</td>
                      <td>{{ gmdate('H:i:s', (int) $pageRow->total_seconds) }}</td>
                      <td class="text-end">{{ $pageRow->last_seen_at?->format('M d, Y h:i A') ?? '—' }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="card">
        <div class="card-body">
          <h5 class="mb-3">{{ $selectedUser->name }} recent logins</h5>
          @if ($selectedUserRecentLogins->isEmpty())
            <p class="text-secondary mb-0">No login history recorded yet for this user.</p>
          @else
            <div class="d-flex flex-column gap-2">
              @foreach ($selectedUserRecentLogins as $loginRow)
                <div class="border rounded p-3">
                  <div class="fw-semibold">{{ $loginRow->login_at?->format('M d, Y h:i A') ?? '—' }}</div>
                  <div class="text-secondary small">IP: {{ $loginRow->ip_address ?: '-' }}</div>
                </div>
              @endforeach
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
@endif
@endsection
