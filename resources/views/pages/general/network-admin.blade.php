@extends('layout.master')

@section('content')
@php
  $formatEventDepth = function (string $type, string $title): string {
      return match (true) {
          $type === 'team_subscription' => 'Level 1',
          $type === 'team_downline_subscription' => 'Level 2',
          str_starts_with($type, 'team_level_') && str_ends_with($type, '_subscription') => 'Level '.str($type)->between('team_level_', '_subscription'),
          default => $title,
      };
  };
@endphp
<div class="row">
  <div class="col-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h4 class="mb-1">Network Admin</h4>
        <p class="text-secondary mb-0">Full sponsor tree visibility for operations, leadership, and team-performance monitoring.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('dashboard.analytics') }}" class="btn btn-outline-primary btn-icon-text">
          <i data-lucide="bar-chart-3" class="btn-icon-prepend"></i> Analytics
        </a>
        <a href="{{ route('dashboard.users') }}" class="btn btn-primary btn-icon-text">
          <i data-lucide="users-round" class="btn-icon-prepend"></i> Users
        </a>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-md-3 grid-margin stretch-card"><div class="card"><div class="card-body"><p class="text-secondary mb-1">Users in tree</p><h4 class="mb-0">{{ $users->count() }}</h4></div></div></div>
  <div class="col-md-3 grid-margin stretch-card"><div class="card"><div class="card-body"><p class="text-secondary mb-1">Sponsored users</p><h4 class="mb-0">{{ $users->whereNotNull('sponsor_user_id')->count() }}</h4></div></div></div>
  <div class="col-md-3 grid-margin stretch-card"><div class="card"><div class="card-body"><p class="text-secondary mb-1">Active team investors</p><h4 class="mb-0">{{ $users->filter(fn ($user) => $user->investments->where('status', 'active')->where('amount', '>', 0)->isNotEmpty())->count() }}</h4></div></div></div>
  <div class="col-md-3 grid-margin stretch-card"><div class="card"><div class="card-body"><p class="text-secondary mb-1">Tracked events</p><h4 class="mb-0">{{ $events->count() }}</h4></div></div></div>
</div>

<div class="row mb-4">
  <div class="col-12 stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h5 class="mb-1">Network ownership</h5>
            <p class="text-secondary mb-0">Every user, their sponsor, direct team size, active team count, and branch capital.</p>
          </div>
          <span class="badge bg-primary">{{ $users->count() }} users</span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>User</th>
                <th>Sponsor</th>
                <th>Level</th>
                <th>Direct team</th>
                <th>Active team</th>
                <th>Team capital</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($users as $networkUser)
                <tr>
                  <td>
                    <div class="fw-semibold">{{ $networkUser->name }}</div>
                    <div class="text-secondary small">{{ $networkUser->email }}</div>
                  </td>
                  <td>{{ $networkUser->sponsor?->email ?? 'Top-level' }}</td>
                  <td>{{ $networkUser->userLevel?->name ?? 'Starter' }}</td>
                  <td>{{ $networkUser->sponsoredUsers->count() }}</td>
                  <td>{{ $networkUser->sponsoredUsers->filter(fn ($member) => $member->investments->where('status', 'active')->where('amount', '>', 0)->isNotEmpty())->count() }}</td>
                  <td>${{ number_format((float) $networkUser->sponsoredUsers->sum(fn ($member) => $member->investments->where('status', 'active')->where('amount', '>', 0)->sum('amount')), 2) }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
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
            <h5 class="mb-1">Recent network events</h5>
            <p class="text-secondary mb-0">Registration and subscription milestones across the entire referral tree.</p>
          </div>
          <span class="badge bg-dark">{{ $events->count() }} recent</span>
        </div>
        @if ($events->isEmpty())
          <p class="text-secondary mb-0">No network events recorded yet.</p>
        @else
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>When</th>
                  <th>Sponsor</th>
                  <th>Reward level</th>
                  <th>Event</th>
                  <th>Related user</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($events as $event)
                  <tr>
                    <td>{{ $event->created_at?->format('M d, Y h:i A') }}</td>
                    <td>{{ $event->sponsor?->email ?? '—' }}</td>
                    <td><span class="badge bg-light text-dark">{{ $formatEventDepth($event->type, $event->title) }}</span></td>
                    <td>
                      <div class="fw-semibold">{{ $event->title }}</div>
                      <div class="text-secondary small">{{ $event->message }}</div>
                    </td>
                    <td>{{ $event->relatedUser?->email ?? '—' }}</td>
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
