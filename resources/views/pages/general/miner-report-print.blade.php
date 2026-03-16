<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $miner->name }} Daily Miner Report</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 32px; color: #1f2d3d; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; margin-bottom: 24px; }
    .title h1 { margin: 0 0 8px; font-size: 28px; color: #274690; }
    .title p { margin: 0; color: #5f6b7a; }
    .actions { display: flex; gap: 12px; }
    .button { border: 1px solid #6571ff; color: #6571ff; padding: 10px 14px; text-decoration: none; border-radius: 8px; font-size: 14px; }
    .button.primary { background: #6571ff; color: #fff; }
    .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    .card { border: 1px solid #d9e1f2; border-radius: 12px; padding: 16px; }
    .label { font-size: 12px; text-transform: uppercase; color: #6b7a90; letter-spacing: 0.05em; margin-bottom: 8px; }
    .value { font-size: 24px; font-weight: 700; }
    .section-title { margin: 24px 0 12px; font-size: 20px; color: #274690; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    th, td { border: 1px solid #d9e1f2; padding: 10px 12px; text-align: left; font-size: 14px; }
    th { background: #eef3ff; }
    .muted { color: #5f6b7a; font-size: 14px; }
    .two-col { display: grid; grid-template-columns: 2fr 1fr; gap: 16px; }
    .list { margin: 0; padding-left: 18px; color: #5f6b7a; }
    @media print {
      body { margin: 16px; }
      .actions { display: none; }
    }
    @media (max-width: 900px) {
      .grid, .two-col { grid-template-columns: 1fr; }
      .header { flex-direction: column; }
    }
  </style>
</head>
<body>
  <div class="header">
    <div class="title">
      <h1>{{ $miner->name }} Daily Miner Report</h1>
      <p>Prepared for {{ $user->name }} on {{ now()->format('M d, Y h:i A') }}. This printable report summarizes recent miner performance and your payout position.</p>
    </div>
    <div class="actions">
      <a href="{{ route('dashboard.miner-report', ['miner' => $miner->slug]) }}" class="button">Back to report</a>
      <button type="button" onclick="window.print()" class="button primary">Print Report</button>
    </div>
  </div>

  <div class="grid">
    <div class="card"><div class="label">14-day revenue</div><div class="value">${{ number_format((float) ($minerPerformanceSummary['total_revenue'] ?? 0), 2) }}</div></div>
    <div class="card"><div class="label">14-day net profit</div><div class="value">${{ number_format((float) ($minerPerformanceSummary['total_net_profit'] ?? 0), 2) }}</div></div>
    <div class="card"><div class="label">Average uptime</div><div class="value">{{ number_format((float) ($minerPerformanceSummary['average_uptime'] ?? 0), 2) }}%</div></div>
    <div class="card"><div class="label">Average per share</div><div class="value">${{ number_format((float) ($minerPerformanceSummary['average_revenue_per_share'] ?? 0), 4) }}</div></div>
  </div>

  <div class="two-col">
    <div class="card">
      <div class="section-title" style="margin-top:0;">How daily share earnings are produced</div>
      <ol class="list">
        <li>Hashrate, uptime, revenue, electricity, and maintenance are captured for the miner each day.</li>
        <li>Daily costs are removed from revenue to produce the net profit.</li>
        <li>Net profit is divided by active sold shares to calculate the daily value per share.</li>
        <li>Your owned shares multiply that per-share value into your personal payout row.</li>
      </ol>
    </div>
    <div class="card">
      <div class="section-title" style="margin-top:0;">Your stake in this miner</div>
      @if ($userHasStake)
        <div class="label">Active positions</div>
        <div class="value" style="font-size:20px;">{{ $activeInvestmentCount }}</div>
        <div class="label" style="margin-top:12px;">Owned shares</div>
        <div class="value" style="font-size:20px;">{{ number_format($activeSharesOwned) }}</div>
        <div class="label" style="margin-top:12px;">Capital in this miner</div>
        <div class="value" style="font-size:20px;">${{ number_format($activeCapital, 2) }}</div>
        <div class="label" style="margin-top:12px;">Latest daily payout</div>
        <div class="value" style="font-size:20px;">${{ number_format($latestUserMinerPayout, 2) }}</div>
        <div class="muted">14-day payout total: ${{ number_format($userMinerPayoutTotal, 2) }}</div>
      @else
        <p class="muted">You do not currently have an active stake in {{ $miner->name }}.</p>
      @endif
    </div>
  </div>

  <div class="section-title">Recent daily logs</div>
  <table>
    <thead>
      <tr>
        <th>Date</th>
        <th>Revenue</th>
        <th>Costs</th>
        <th>Net profit</th>
        <th>Per share</th>
        <th>Hashrate</th>
        <th>Uptime</th>
        <th>Source</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($performanceLogs as $log)
        <tr>
          <td>{{ $log->logged_on?->format('M d, Y') }}</td>
          <td>${{ number_format((float) $log->revenue_usd, 2) }}</td>
          <td>${{ number_format((float) $log->electricity_cost_usd + (float) $log->maintenance_cost_usd, 2) }}</td>
          <td>${{ number_format((float) $log->net_profit_usd, 2) }}</td>
          <td>${{ number_format((float) $log->revenue_per_share_usd, 4) }}</td>
          <td>{{ number_format((float) $log->hashrate_th, 2) }} TH/s</td>
          <td>{{ number_format((float) $log->uptime_percentage, 2) }}%</td>
          <td>{{ str_replace('_', ' ', $log->source ?? 'manual') }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @if ($userHasStake)
    <div class="section-title">Your recent payout rows</div>
    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Amount</th>
          <th>Notes</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($userMinerEarnings as $earning)
          <tr>
            <td>{{ $earning->earned_on?->format('M d, Y') }}</td>
            <td>${{ number_format((float) $earning->amount, 2) }}</td>
            <td>{{ $earning->notes }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</body>
</html>
