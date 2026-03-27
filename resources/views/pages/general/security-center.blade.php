@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h4 class="mb-1">Security Center</h4>
        <p class="text-secondary mb-0">One place to review admin protection, critical alerts, recent security activity, and operational health.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('dashboard.security-center.export.word') }}" class="btn btn-outline-dark btn-sm">Export Word</a>
        <a href="{{ route('dashboard.security-center.export.pdf') }}" class="btn btn-outline-success btn-sm" target="_blank">Export PDF</a>
        <a href="{{ route('profile.edit') }}" class="btn btn-outline-primary btn-sm">Open account security</a>
        <a href="{{ route('dashboard.notifications', ['filter' => 'all']) }}" class="btn btn-outline-secondary btn-sm">Open notifications</a>
        <a href="{{ route('dashboard.operations') }}" class="btn btn-primary btn-sm">Open operations</a>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-12">
    @php($adminSafetyViewer = auth()->user())
    @include('pages.general.partials.admin-safety-center')
  </div>
</div>

<div class="row mb-4">
  <div class="col-md-3 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="text-secondary small mb-1">Admin 2FA</div>
        <div class="fw-semibold fs-5 mb-2">{{ auth()->user()->hasAdminTwoFactorEnabled() ? 'Enabled' : 'Action needed' }}</div>
        <div class="small text-secondary">
          @if (auth()->user()->hasAdminTwoFactorEnabled())
            Your admin login challenge is active.
          @else
            Enable Admin 2FA from Account Settings now.
          @endif
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="text-secondary small mb-1">Critical alerts</div>
        <div class="fw-semibold fs-5 mb-2">{{ $criticalAlerts->count() }}</div>
        <div class="small text-secondary">Latest warning-level admin alerts in your notification feed.</div>
      </div>
    </div>
  </div>
  <div class="col-md-3 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="text-secondary small mb-1">Pending investments</div>
        <div class="fw-semibold fs-5 mb-2">{{ $currentHealthSummary['pending_investment_orders'] }}</div>
        <div class="small text-secondary">{{ $currentHealthSummary['pending_orders_missing_proof'] }} missing proof, {{ $currentHealthSummary['stale_pending_investments'] }} stale.</div>
      </div>
    </div>
  </div>
  <div class="col-md-3 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="text-secondary small mb-1">Pending payouts</div>
        <div class="fw-semibold fs-5 mb-2">{{ $currentHealthSummary['pending_payout_requests'] }}</div>
        <div class="small text-secondary">{{ $currentHealthSummary['stale_pending_payouts'] }} stale, {{ $currentHealthSummary['recent_admin_actions'] }} admin actions in the last 24h.</div>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-xl-6 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
          <div>
            <h5 class="mb-1">Current health snapshot</h5>
            <p class="text-secondary mb-0">Live operational counts from the last 24 hours.</p>
          </div>
          <span class="badge bg-light text-dark border">{{ $currentHealthSummary['period_label'] }}</span>
        </div>
        <div class="row g-3">
          <div class="col-sm-6">
            <div class="border rounded p-3 h-100 bg-light">
              <div class="text-secondary small">Pending orders with proof</div>
              <div class="fw-semibold fs-5">{{ $currentHealthSummary['pending_orders_with_proof'] }}</div>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="border rounded p-3 h-100 bg-light">
              <div class="text-secondary small">Pending orders missing proof</div>
              <div class="fw-semibold fs-5">{{ $currentHealthSummary['pending_orders_missing_proof'] }}</div>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="border rounded p-3 h-100 bg-light">
              <div class="text-secondary small">Stale pending investments</div>
              <div class="fw-semibold fs-5">{{ $currentHealthSummary['stale_pending_investments'] }}</div>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="border rounded p-3 h-100 bg-light">
              <div class="text-secondary small">Stale pending payouts</div>
              <div class="fw-semibold fs-5">{{ $currentHealthSummary['stale_pending_payouts'] }}</div>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="border rounded p-3 h-100 bg-light">
              <div class="text-secondary small">Pending invitations</div>
              <div class="fw-semibold fs-5">{{ $currentHealthSummary['pending_friend_invitations'] }}</div>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="border rounded p-3 h-100 bg-light">
              <div class="text-secondary small">Recent admin actions</div>
              <div class="fw-semibold fs-5">{{ $currentHealthSummary['recent_admin_actions'] }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-6 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
          <div>
            <h5 class="mb-1">Recovery reminders</h5>
            <p class="text-secondary mb-0">The most important steps to remember during incident response.</p>
          </div>
          <span class="badge bg-warning text-dark">Admin only</span>
        </div>
        <div class="border rounded p-3 bg-light mb-3">
          <div class="fw-semibold mb-1">Lost authenticator device</div>
          <div class="small text-secondary">Disable admin 2FA on the server first, then log back in and re-enroll the new device from your profile page.</div>
        </div>
        <div class="border rounded p-3 bg-light mb-3">
          <div class="fw-semibold mb-1">Before migrations</div>
          <div class="small text-secondary">Take a fresh production database backup before running `php artisan migrate --force`.</div>
        </div>
        <div class="border rounded p-3 bg-light">
          <div class="fw-semibold mb-1">After deployment</div>
          <div class="small text-secondary">Clear caches, then test login, dashboard, profile, wallet, buy shares, and operations.</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-xl-6 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
          <div>
            <h5 class="mb-1">Critical alerts</h5>
            <p class="text-secondary mb-0">Recent warning-level notifications for failed logins and failed admin 2FA attempts.</p>
          </div>
          <a href="{{ route('dashboard.notifications') }}" class="btn btn-outline-secondary btn-sm">Open notifications</a>
        </div>
        @if ($criticalAlerts->isEmpty())
          <p class="text-secondary mb-0">No recent critical alerts.</p>
        @else
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>Alert</th>
                  <th>Context</th>
                  <th>Status</th>
                  <th class="text-end">Time</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($criticalAlerts as $alert)
                  <tr>
                    <td>
                      <div class="fw-semibold">{{ $alert->data['subject'] ?? 'Critical alert' }}</div>
                      <div class="text-secondary small">{{ $alert->data['message'] ?? '' }}</div>
                    </td>
                    <td>
                      <div class="small">{{ $alert->data['context_label'] ?? 'Context' }}</div>
                      <div class="text-secondary small">{{ $alert->data['context_value'] ?? '—' }}</div>
                    </td>
                    <td><span class="badge bg-warning text-dark">{{ ucfirst($alert->data['status'] ?? 'warning') }}</span></td>
                    <td class="text-end text-secondary small">{{ $alert->created_at?->format('M d, Y h:i A') }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>
  </div>
  <div class="col-xl-6 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
          <div>
            <h5 class="mb-1">Daily health summaries</h5>
            <p class="text-secondary mb-0">Recent scheduled summary notifications delivered to this admin account.</p>
          </div>
          <a href="{{ route('dashboard.digests') }}" class="btn btn-outline-secondary btn-sm">Open digests</a>
        </div>
        @if ($healthSummaryNotifications->isEmpty())
          <p class="text-secondary mb-0">No admin health summaries have been delivered yet.</p>
        @else
          <div class="d-flex flex-column gap-3">
            @foreach ($healthSummaryNotifications as $summaryNotification)
              @php($summary = $summaryNotification->data['admin_health_summary'] ?? [])
              <div class="border rounded p-3 bg-light">
                <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2">
                  <div class="fw-semibold">{{ $summaryNotification->data['subject'] ?? 'Daily admin health summary' }}</div>
                  <span class="badge bg-light text-dark border">{{ $summaryNotification->created_at?->format('M d, Y h:i A') }}</span>
                </div>
                <div class="small text-secondary mb-2">{{ $summaryNotification->data['message'] ?? '' }}</div>
                <div class="row g-2 small">
                  <div class="col-sm-6">Pending investments: <span class="fw-semibold">{{ $summary['pending_investment_orders'] ?? 0 }}</span></div>
                  <div class="col-sm-6">Pending payouts: <span class="fw-semibold">{{ $summary['pending_payout_requests'] ?? 0 }}</span></div>
                  <div class="col-sm-6">Orders with proof: <span class="fw-semibold">{{ $summary['pending_orders_with_proof'] ?? 0 }}</span></div>
                  <div class="col-sm-6">Missing proof: <span class="fw-semibold">{{ $summary['pending_orders_missing_proof'] ?? 0 }}</span></div>
                </div>
              </div>
            @endforeach
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
          <div>
            <h5 class="mb-1">Recent admin security activity</h5>
            <p class="text-secondary mb-0">Latest approval and payout actions recorded in the admin audit log.</p>
          </div>
          <a href="{{ route('dashboard.operations') }}" class="btn btn-outline-primary btn-sm">Open full operations log</a>
        </div>
        @if ($recentAdminActivityLogs->isEmpty())
          <p class="text-secondary mb-0">No admin activity has been logged yet.</p>
        @else
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>Action</th>
                  <th>Admin</th>
                  <th>Summary</th>
                  <th class="text-end">Time</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($recentAdminActivityLogs as $activityLog)
                  <tr>
                    <td><span class="badge bg-light text-dark border">{{ str($activityLog->action)->replace('.', ' ')->title() }}</span></td>
                    <td>
                      <div class="fw-semibold">{{ $activityLog->admin?->name ?? 'Unknown admin' }}</div>
                      <div class="text-secondary small">{{ strtolower($activityLog->admin?->email ?? '') }}</div>
                    </td>
                    <td>{{ $activityLog->summary }}</td>
                    <td class="text-end text-secondary small">{{ $activityLog->created_at?->format('M d, Y h:i A') }}</td>
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
