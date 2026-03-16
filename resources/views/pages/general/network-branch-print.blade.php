<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Branch Summary</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      color: #0f172a;
      margin: 24px;
      line-height: 1.45;
    }

    h1, h2, h3 {
      margin: 0;
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 16px;
      margin-bottom: 24px;
    }

    .muted {
      color: #64748b;
    }

    .meta {
      font-size: 12px;
      text-align: right;
    }

    .summary-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 12px;
      margin-bottom: 24px;
    }

    .summary-card {
      border: 1px solid #cbd5e1;
      border-radius: 10px;
      padding: 14px;
      background: #f8fafc;
    }

    .summary-card .label {
      font-size: 12px;
      color: #64748b;
      margin-bottom: 6px;
    }

    .summary-card .value {
      font-size: 20px;
      font-weight: 700;
    }

    .section {
      margin-bottom: 24px;
    }

    .section h2 {
      font-size: 18px;
      margin-bottom: 8px;
    }

    .filter-table,
    .member-table {
      width: 100%;
      border-collapse: collapse;
    }

    .filter-table td,
    .member-table th,
    .member-table td {
      border: 1px solid #cbd5e1;
      padding: 8px 10px;
      vertical-align: top;
      font-size: 13px;
    }

    .member-table th {
      background: #e2e8f0;
      text-align: left;
    }

    .footer-note {
      margin-top: 20px;
      font-size: 12px;
      color: #64748b;
    }

    @media print {
      body {
        margin: 12px;
      }
    }
  </style>
</head>
<body>
  <div class="header">
    <div>
      <h1>Branch Summary</h1>
      <div class="muted">{{ $pageTitle }}</div>
    </div>
    <div class="meta">
      <div>Generated: {{ now()->format('M d, Y h:i A') }}</div>
      <div>Focus: {{ $focusUser?->email ?? 'All visible roots' }}</div>
      <div>Depth: {{ $treeDepth }}</div>
    </div>
  </div>

  <div class="summary-grid">
    <div class="summary-card">
      <div class="label">Visible members</div>
      <div class="value">{{ $summary['visible_nodes'] }}</div>
    </div>
    <div class="summary-card">
      <div class="label">Leaf nodes</div>
      <div class="value">{{ $summary['leaf_nodes'] }}</div>
    </div>
    <div class="summary-card">
      <div class="label">Branch investors</div>
      <div class="value">{{ $branchInvestorCount }}</div>
    </div>
    <div class="summary-card">
      <div class="label">Branch capital</div>
      <div class="value">${{ number_format($branchCapital, 2) }}</div>
    </div>
  </div>

  <div class="section">
    <h2>Filter Context</h2>
    <table class="filter-table">
      <tr><td><strong>Search term</strong></td><td>{{ $treeSearch === '' ? 'All' : $treeSearch }}</td></tr>
      <tr><td><strong>Focused branch</strong></td><td>{{ $focusUser?->name ? $focusUser->name.' ('.$focusUser->email.')' : 'All visible roots' }}</td></tr>
      <tr><td><strong>Tree depth</strong></td><td>{{ $treeDepth }}</td></tr>
    </table>
  </div>

  <div class="section">
    <h2>Visible Branch Members</h2>
    <table class="member-table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Sponsor</th>
          <th>Depth</th>
          <th>Health</th>
          <th>Power</th>
          <th>Direct team</th>
          <th>Branch investors</th>
          <th>Branch capital</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($rows as $node)
          <tr>
            <td>{{ $node['user']->name }}</td>
            <td>{{ $node['user']->email }}</td>
            <td>{{ $node['sponsor_name'] }}</td>
            <td>{{ $node['depth'] }}</td>
            <td>{{ $node['situation']['health'] }}</td>
            <td>{{ $node['power_summary']['score'] }}/100</td>
            <td>{{ $node['direct_team'] }}</td>
            <td>{{ $node['branch_active_investors'] }}</td>
            <td>${{ number_format($node['branch_active_capital'], 2) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="footer-note">
    This report shows the currently visible subtree only. Expand depth or change the focused investor to print a different branch scope.
  </div>
</body>
</html>
