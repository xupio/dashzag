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

@if (session('network_admin_success'))
  <div class="row">
    <div class="col-12">
      <div class="alert alert-success d-flex align-items-center justify-content-between" role="alert">
        <span>{{ session('network_admin_success') }}</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    </div>
  </div>
@endif

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
            <h5 class="mb-1">Referral growth overview</h5>
            <p class="text-secondary mb-0">Track how invitations are moving from first contact to active investor conversion across the whole platform.</p>
          </div>
          <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('dashboard.network-admin.referral-coaching-export') }}" class="btn btn-sm btn-outline-primary">
              Export coaching list CSV
            </a>
            <a href="{{ route('dashboard.network-admin', array_merge(request()->query(), ['referral_filter' => 'all'])) }}" class="btn btn-sm {{ $referralFilter === 'all' ? 'btn-success' : 'btn-outline-success' }}">
              All referrers
            </a>
            <a href="{{ route('dashboard.network-admin', array_merge(request()->query(), ['referral_filter' => 'needs_coaching'])) }}" class="btn btn-sm {{ $referralFilter === 'needs_coaching' ? 'btn-warning text-dark' : 'btn-outline-warning' }}">
              Needs coaching
            </a>
            <a href="{{ route('dashboard.network-admin', array_merge(request()->query(), ['referral_sort' => 'default'])) }}" class="btn btn-sm {{ $referralSort === 'default' ? 'btn-dark' : 'btn-outline-dark' }}">
              Default order
            </a>
            <a href="{{ route('dashboard.network-admin', array_merge(request()->query(), ['referral_sort' => 'urgency'])) }}" class="btn btn-sm {{ $referralSort === 'urgency' ? 'btn-danger' : 'btn-outline-danger' }}">
              Urgency first
            </a>
          </div>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-6 col-xl-3">
            <div class="border rounded p-3 h-100 bg-light">
              <div class="small text-secondary mb-2">Total invitations</div>
              <div class="h4 mb-1">{{ $referralAdminSummary['total_invitations'] }}</div>
              <div class="small text-secondary">All friend invitations sent by users.</div>
            </div>
          </div>
          <div class="col-md-6 col-xl-3">
            <div class="border rounded p-3 h-100 bg-light">
              <div class="small text-secondary mb-2">Verified invitations</div>
              <div class="h4 mb-1">{{ $referralAdminSummary['verified_invitations'] }}</div>
              <div class="small text-secondary">{{ $referralAdminSummary['verification_rate'] }}% verification rate.</div>
            </div>
          </div>
          <div class="col-md-6 col-xl-3">
            <div class="border rounded p-3 h-100 bg-light">
              <div class="small text-secondary mb-2">Registered invitations</div>
              <div class="h4 mb-1">{{ $referralAdminSummary['registered_invitations'] }}</div>
              <div class="small text-secondary">{{ $referralAdminSummary['registration_rate'] }}% registration rate.</div>
            </div>
          </div>
          <div class="col-md-6 col-xl-3">
            <div class="border rounded p-3 h-100 bg-light">
              <div class="small text-secondary mb-2">Active investor conversions</div>
              <div class="h4 mb-1">{{ $referralAdminSummary['active_investors'] }}</div>
              <div class="small text-secondary">{{ $referralAdminSummary['investor_rate'] }}% investor conversion rate.</div>
            </div>
          </div>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-6 col-xl-3">
            <div class="border rounded p-3 h-100 bg-light">
              <div class="small text-secondary mb-2">Contacted recently</div>
              <div class="h4 mb-1">{{ $coachingSummary['contacted_recently'] }}</div>
              <div class="small text-secondary">Coaching cases updated in the last 3 days.</div>
            </div>
          </div>
          <div class="col-md-6 col-xl-3">
            <div class="border rounded p-3 h-100 bg-light">
              <div class="small text-secondary mb-2">Waiting</div>
              <div class="h4 mb-1">{{ $coachingSummary['waiting'] }}</div>
              <div class="small text-secondary">Cases waiting for user response or next action.</div>
            </div>
          </div>
          <div class="col-md-6 col-xl-3">
            <div class="border rounded p-3 h-100 bg-light">
              <div class="small text-secondary mb-2">Stale follow-up</div>
              <div class="h4 mb-1">{{ $coachingSummary['stale_follow_up'] }}</div>
              <div class="small text-secondary">Open coaching cases untouched for more than 7 days.</div>
            </div>
          </div>
          <div class="col-md-6 col-xl-3">
            <div class="border rounded p-3 h-100 bg-light">
              <div class="small text-secondary mb-2">Improved</div>
              <div class="h4 mb-1">{{ $coachingSummary['improved'] }}</div>
              <div class="small text-secondary">Referrers whose conversion quality improved.</div>
            </div>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Top referrer</th>
                <th>Total invites</th>
                <th>Verified</th>
                <th>Registered</th>
                <th>Active investors</th>
                <th>Recommended action</th>
                <th>Coaching tracker</th>
                <th class="text-end">Profile</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($topReferralPerformers as $performer)
                <tr>
                  <td>
                    <div class="fw-semibold">{{ $performer['user']->name }}</div>
                    <div class="text-secondary small">{{ $performer['user']->email }}</div>
                  </td>
                  <td>{{ $performer['total_invitations'] }}</td>
                  <td>{{ $performer['verified_invitations'] }}</td>
                  <td>{{ $performer['registered_invitations'] }}</td>
                  <td>{{ $performer['active_investors'] }}</td>
                  <td>
                    @php
                      $actionBadgeClass = match ($performer['recommended_action']) {
                          'Review message quality' => 'bg-danger-subtle text-danger border border-danger-subtle',
                          'Follow up manually' => 'bg-warning-subtle text-warning border border-warning-subtle',
                          'Needs onboarding help' => 'bg-info-subtle text-info border border-info-subtle',
                          'Healthy conversion flow' => 'bg-success-subtle text-success border border-success-subtle',
                          default => 'bg-light text-dark border',
                      };
                    @endphp
                    <span class="badge {{ $actionBadgeClass }}">{{ $performer['recommended_action'] }}</span>
                  </td>
                  <td style="min-width: 300px;">
                    @php
                      $coachingStatus = $performer['coaching_note']?->status ?? 'open';
                      $coachingNote = $performer['coaching_note']?->note;
                      $coachingBadgeClass = match ($coachingStatus) {
                          'contacted' => 'bg-primary-subtle text-primary border border-primary-subtle',
                          'waiting' => 'bg-warning-subtle text-warning border border-warning-subtle',
                          'improved' => 'bg-success-subtle text-success border border-success-subtle',
                          default => 'bg-light text-dark border',
                      };
                    @endphp
                    <div class="mb-2">
                      <span class="badge {{ $coachingBadgeClass }}">{{ str($coachingStatus)->replace('_', ' ')->title() }}</span>
                      @if ($performer['coaching_note']?->admin)
                        <span class="text-secondary small ms-1">by {{ $performer['coaching_note']->admin->name }}</span>
                      @endif
                    </div>
                    <form method="POST" action="{{ route('dashboard.network-admin.referral-coaching.update', $performer['user']) }}">
                      @csrf
                      <input type="hidden" name="referral_filter" value="{{ $referralFilter }}">
                      <input type="hidden" name="tree_search" value="{{ $treeSearch }}">
                      <input type="hidden" name="tree_focus" value="{{ $selectedTreeFocus?->id }}">
                      <input type="hidden" name="tree_depth" value="{{ $treeDepth }}">
                      <div class="d-flex gap-2 mb-2">
                        <select name="status" class="form-select form-select-sm">
                          @foreach (['open' => 'Open', 'contacted' => 'Contacted', 'waiting' => 'Waiting', 'improved' => 'Improved'] as $statusValue => $statusLabel)
                            <option value="{{ $statusValue }}" @selected($coachingStatus === $statusValue)>{{ $statusLabel }}</option>
                          @endforeach
                        </select>
                        <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                      </div>
                      <textarea name="note" rows="2" class="form-control form-control-sm" placeholder="Add a quick coaching note">{{ $coachingNote }}</textarea>
                    </form>
                  </td>
                  <td class="text-end">
                    <a href="{{ route('dashboard.investors.show', ['user' => $performer['user'], 'from' => 'network-admin']) }}" class="btn btn-outline-primary btn-sm">Open profile</a>
                  </td>
                </tr>
              @empty
                @if ($referralFilter === 'needs_coaching')
                  <tr>
                    <td colspan="8" class="text-center text-secondary py-4">No referrers need coaching right now.</td>
                  </tr>
                @else
                  <tr>
                    <td colspan="8" class="text-center text-secondary py-4">No invitation activity yet.</td>
                  </tr>
                @endif
              @endforelse
            </tbody>
          </table>
        </div>
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
            <h5 class="mb-1">Visual sponsor tree</h5>
            <p class="text-secondary mb-0">Follow every root sponsor, their direct branches, and the visible sub-levels in one tree view.</p>
          </div>
          <div class="d-flex gap-2 flex-wrap">
            <span class="badge bg-primary">{{ $networkTreeSummary['root_count'] }} roots</span>
            <span class="badge bg-light text-dark">Depth {{ $networkTreeSummary['max_depth'] }}</span>
            <span class="badge bg-dark">{{ $networkTreeSummary['leaf_nodes'] }} leaves</span>
          </div>
        </div>
        <form method="GET" action="{{ route('dashboard.network-admin') }}" class="row g-3 align-items-end mb-3">
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
            <a href="{{ route('dashboard.network-admin') }}" class="btn btn-outline-secondary">Reset</a>
          </div>
          <div class="col-12">
            <a href="{{ route('dashboard.network-admin.export', ['tree_search' => $treeSearch, 'tree_focus' => $selectedTreeFocus?->id, 'tree_depth' => $treeDepth]) }}" class="btn btn-outline-success btn-sm">
              Export Focused Branch CSV
            </a>
            <a href="{{ route('dashboard.network-admin.print', ['tree_search' => $treeSearch, 'tree_focus' => $selectedTreeFocus?->id, 'tree_depth' => $treeDepth]) }}" target="_blank" class="btn btn-outline-primary btn-sm ms-2">
              Print Branch Summary
            </a>
          </div>
          @if ($selectedTreeFocus)
            <div class="col-12">
              <div class="text-secondary small">Focused on <strong>{{ $selectedTreeFocus->name }}</strong>. The chart now shows only this sponsor branch.</div>
            </div>
          @elseif($treeSearch !== '' && $treeSearchResults->isEmpty())
            <div class="col-12">
              <div class="text-secondary small">No matching investor found for this search yet.</div>
            </div>
          @endif
        </form>

        @if ($networkTree->isEmpty())
          <p class="text-secondary mb-0">The sponsor tree will appear here once users start building referral branches.</p>
        @else
          @include('pages.general.partials.network-org-chart', [
            'chartId' => 'adminNetworkOrgChart',
            'chartTitle' => 'Network Admin Sponsor Tree',
            'chartDescription' => 'Click any investor node to open a quick branch situation summary and jump into the full investor profile.',
            'tree' => $networkTree,
          ])
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
                <th>Reward caps</th>
                <th>Direct team</th>
                <th>Active team</th>
                <th>Team capital</th>
                <th class="text-end">Profile</th>
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
                  <td>
                    @php($rewardCaps = \App\Support\MiningPlatform::unlockedRewardCapBadges($networkUser))
                    @if (! empty($rewardCaps))
                      <div class="d-flex flex-wrap gap-1">
                        @foreach ($rewardCaps as $cap)
                          <span class="badge bg-info-subtle text-info border border-info-subtle">{{ $cap['short'] }}</span>
                        @endforeach
                      </div>
                    @else
                      <span class="text-secondary">—</span>
                    @endif
                  </td>
                  <td>{{ $networkUser->sponsoredUsers->count() }}</td>
                  <td>{{ $networkUser->sponsoredUsers->filter(fn ($member) => $member->investments->where('status', 'active')->where('amount', '>', 0)->isNotEmpty())->count() }}</td>
                  <td>${{ number_format((float) $networkUser->sponsoredUsers->sum(fn ($member) => $member->investments->where('status', 'active')->where('amount', '>', 0)->sum('amount')), 2) }}</td>
                  <td class="text-end">
                    <a href="{{ route('dashboard.investors.show', ['user' => $networkUser, 'from' => 'network-admin']) }}" class="btn btn-outline-primary btn-sm">Open profile</a>
                  </td>
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






