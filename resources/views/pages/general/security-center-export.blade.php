<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>ZagChain Security Center Export</title>
  <style>
    body { font-family: Arial, sans-serif; color: #111827; margin: 32px; }
    h1, h2, h3 { margin-bottom: 8px; }
    p { line-height: 1.5; }
    .meta { color: #6b7280; margin-bottom: 24px; }
    .card { border: 1px solid #d1d5db; border-radius: 12px; padding: 18px; margin-bottom: 20px; }
    .grid { display: table; width: 100%; border-collapse: separate; border-spacing: 12px; }
    .grid-row { display: table-row; }
    .grid-cell { display: table-cell; width: 50%; vertical-align: top; }
    .small { color: #6b7280; font-size: 12px; }
    .badge { display: inline-block; background: #eef2ff; color: #274690; border-radius: 999px; padding: 4px 10px; font-size: 12px; font-weight: 700; }
    .section-title { font-size: 18px; font-weight: 700; margin-bottom: 8px; }
    pre { white-space: pre-wrap; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; font-size: 12px; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    th, td { border: 1px solid #e5e7eb; padding: 10px; text-align: left; vertical-align: top; }
    th { background: #f3f4f6; }
    @media print {
      body { margin: 16px; }
      .no-print { display: none; }
    }
  </style>
</head>
<body onload="{{ $autoPrint ? 'window.print()' : '' }}">
  <div class="no-print" style="margin-bottom: 20px;">
    <button onclick="window.print()">Print / Save as PDF</button>
  </div>

  <h1>ZagChain Security Center</h1>
  <p class="meta">Export generated on {{ now()->format('M d, Y h:i A') }}</p>

  @php($adminSafetyViewer = auth()->user())
  @include('pages.general.partials.admin-safety-center')

  <div class="card">
    <div class="section-title">Current health snapshot</div>
    <table>
      <tr><th>Metric</th><th>Value</th></tr>
      <tr><td>Pending investments</td><td>{{ $currentHealthSummary['pending_investment_orders'] }}</td></tr>
      <tr><td>Pending payouts</td><td>{{ $currentHealthSummary['pending_payout_requests'] }}</td></tr>
      <tr><td>Orders with proof</td><td>{{ $currentHealthSummary['pending_orders_with_proof'] }}</td></tr>
      <tr><td>Orders missing proof</td><td>{{ $currentHealthSummary['pending_orders_missing_proof'] }}</td></tr>
      <tr><td>Stale pending investments</td><td>{{ $currentHealthSummary['stale_pending_investments'] }}</td></tr>
      <tr><td>Stale pending payouts</td><td>{{ $currentHealthSummary['stale_pending_payouts'] }}</td></tr>
      <tr><td>Recent admin actions</td><td>{{ $currentHealthSummary['recent_admin_actions'] }}</td></tr>
      <tr><td>Pending invitations</td><td>{{ $currentHealthSummary['pending_friend_invitations'] }}</td></tr>
    </table>
  </div>

  <div class="card">
    <div class="section-title">Critical alerts</div>
    @if ($criticalAlerts->isEmpty())
      <p>No recent critical alerts.</p>
    @else
      <table>
        <tr><th>Alert</th><th>Context</th><th>Time</th></tr>
        @foreach ($criticalAlerts as $alert)
          <tr>
            <td>
              <div><strong>{{ $alert->data['subject'] ?? 'Critical alert' }}</strong></div>
              <div>{{ $alert->data['message'] ?? '' }}</div>
            </td>
            <td>{{ $alert->data['context_label'] ?? 'Context' }}: {{ $alert->data['context_value'] ?? '—' }}</td>
            <td>{{ $alert->created_at?->format('M d, Y h:i A') }}</td>
          </tr>
        @endforeach
      </table>
    @endif
  </div>

  <div class="card">
    <div class="section-title">Recent admin security activity</div>
    @if ($recentAdminActivityLogs->isEmpty())
      <p>No admin activity has been logged yet.</p>
    @else
      <table>
        <tr><th>Action</th><th>Admin</th><th>Summary</th><th>Time</th></tr>
        @foreach ($recentAdminActivityLogs as $activityLog)
          <tr>
            <td>{{ str($activityLog->action)->replace('.', ' ')->title() }}</td>
            <td>{{ $activityLog->admin?->name ?? 'Unknown admin' }}</td>
            <td>{{ $activityLog->summary }}</td>
            <td>{{ $activityLog->created_at?->format('M d, Y h:i A') }}</td>
          </tr>
        @endforeach
      </table>
    @endif
  </div>
</body>
</html>
