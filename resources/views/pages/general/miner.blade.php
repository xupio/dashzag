@extends('layout.master')

@section('content')
@php
  $snapshotDate = \Illuminate\Support\Carbon::parse($automaticSnapshot['logged_on'] ?? now()->toDateString());
@endphp
<div class="row">
  <div class="col-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h4 class="mb-1">{{ $miner->name }} Miner Details</h4>
        <p class="text-secondary mb-0">Use this page for the full technical, capacity, and admin-level management of the selected miner.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('dashboard') }}?miner={{ $miner->slug }}" class="btn btn-outline-primary btn-icon-text">
          <i data-lucide="layout-dashboard" class="btn-icon-prepend"></i> Back to overview
        </a>
        <a href="{{ route('dashboard.buy-shares') }}?miner={{ $miner->slug }}" class="btn btn-primary btn-icon-text">
          <i data-lucide="shopping-cart" class="btn-icon-prepend"></i> View packages
        </a>
      </div>
    </div>
  </div>
</div>

@if (session('miner_success'))
  <div class="alert alert-success">{{ session('miner_success') }}</div>
@endif

@if (session('log_success'))
  <div class="alert alert-success">{{ session('log_success') }}</div>
@endif

@if ($errors->any())
  <div class="alert alert-danger">
    <div class="fw-semibold mb-1">Miner settings were not saved.</div>
    <div>Please review the highlighted fields below and try again.</div>
  </div>
@endif

@if (($miners ?? collect())->count() > 1)
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div>
            <h6 class="mb-1">Select miner</h6>
            <p class="text-secondary mb-0">Switch between active miners and manage each one with the same technical/admin screen.</p>
          </div>
          <div class="d-flex flex-wrap gap-2">
            @foreach ($miners as $networkMiner)
              <a href="{{ route('dashboard.miner') }}?miner={{ $networkMiner->slug }}" class="btn {{ $networkMiner->id === $miner->id ? 'btn-primary' : 'btn-outline-primary' }} btn-sm">
                {{ $networkMiner->name }}
              </a>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>
@endif

<div class="row mb-4">
  <div class="col-md-3 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <p class="text-secondary mb-1">Share price</p>
        <h4 class="mb-0">${{ number_format((float) $miner->share_price, 2) }}</h4>
      </div>
    </div>
  </div>
  <div class="col-md-3 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <p class="text-secondary mb-1">Shares sold</p>
        <h4 class="mb-0">{{ number_format($sharesSold) }} / {{ number_format($miner->total_shares) }}</h4>
      </div>
    </div>
  </div>
  <div class="col-md-3 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <p class="text-secondary mb-1">Latest net profit</p>
        <h4 class="mb-0">${{ number_format((float) ($latestLog->net_profit_usd ?? 0), 2) }}</h4>
      </div>
    </div>
  </div>
  <div class="col-md-3 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <p class="text-secondary mb-1">Per share today</p>
        <h4 class="mb-0">${{ number_format((float) ($automaticSnapshot['revenue_per_share_usd'] ?? 0), 4) }}</h4>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-md-4 grid-margin stretch-card">
    <div class="card border-primary">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start gap-3">
          <div>
            <p class="text-secondary mb-1">Automatic snapshot preview</p>
            <h5 class="mb-1">{{ $snapshotDate->format('M d, Y') }}</h5>
            <p class="text-secondary mb-0">This is the formula-based daily projection before any manual override.</p>
          </div>
          <span class="badge bg-primary-subtle text-primary">Auto</span>
        </div>
        <div class="row g-3 mt-2">
          <div class="col-6">
            <div class="small text-secondary">Projected revenue</div>
            <div class="fw-semibold">${{ number_format((float) ($automaticSnapshot['revenue_usd'] ?? 0), 2) }}</div>
          </div>
          <div class="col-6">
            <div class="small text-secondary">Hashrate</div>
            <div class="fw-semibold">{{ number_format((float) ($automaticSnapshot['hashrate_th'] ?? 0), 2) }} TH/s</div>
          </div>
          <div class="col-6">
            <div class="small text-secondary">Uptime</div>
            <div class="fw-semibold">{{ number_format((float) ($automaticSnapshot['uptime_percentage'] ?? 0), 2) }}%</div>
          </div>
          <div class="col-6">
            <div class="small text-secondary">Active shares</div>
            <div class="fw-semibold">{{ number_format((int) ($automaticSnapshot['active_shares'] ?? 0)) }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-4 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <p class="text-secondary mb-1">Available shares</p>
        <h4 class="mb-1">{{ number_format($availableShares) }}</h4>
        <p class="text-secondary mb-0">Open miner capacity still available for new investors.</p>
      </div>
    </div>
  </div>
  <div class="col-md-4 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <p class="text-secondary mb-1">Status</p>
        <h4 class="mb-1 text-capitalize">{{ $miner->status }}</h4>
        <p class="text-secondary mb-0">Current operations state for this miner and its package flow.</p>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-xl-7 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h5 class="mb-3">Miner configuration</h5>
        <form method="POST" action="{{ route('dashboard.miner.update') }}">
          @csrf
          <input type="hidden" name="miner_slug" value="{{ $miner->slug }}">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Name</label>
              <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $miner->name) }}" required>
              @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label">Status</label>
              <select name="status" class="form-select @error('status') is-invalid @enderror">
                @foreach (['active', 'paused', 'maintenance'] as $status)
                  <option value="{{ $status }}" @selected(old('status', $miner->status) === $status)>{{ ucfirst($status) }}</option>
                @endforeach
              </select>
              @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $miner->description) }}</textarea>
              @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label">Total shares</label>
              <input type="number" min="1" name="total_shares" class="form-control @error('total_shares') is-invalid @enderror" value="{{ old('total_shares', $miner->total_shares) }}" required>
              @error('total_shares')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label">Share price</label>
              <input type="number" step="0.01" min="1" name="share_price" class="form-control @error('share_price') is-invalid @enderror" value="{{ old('share_price', $miner->share_price) }}" required>
              @error('share_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label">Daily output (USD)</label>
              <input type="number" step="0.01" min="0" name="daily_output_usd" class="form-control @error('daily_output_usd') is-invalid @enderror" value="{{ old('daily_output_usd', $miner->daily_output_usd) }}" required>
              @error('daily_output_usd')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label">Monthly output (USD)</label>
              <input type="number" step="0.01" min="0" name="monthly_output_usd" class="form-control @error('monthly_output_usd') is-invalid @enderror" value="{{ old('monthly_output_usd', $miner->monthly_output_usd) }}" required>
              @error('monthly_output_usd')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label">Base monthly return rate (%)</label><div class="form-text mb-2">Enter a normal percent. Example: <code>8</code> means 8%.</div>
              <input type="number" step="0.01" min="0" max="100" name="base_monthly_return_rate" id="minerBaseMonthlyReturnRateInput" class="form-control @error('base_monthly_return_rate') is-invalid @enderror" value="{{ old('base_monthly_return_rate', number_format((float) $miner->base_monthly_return_rate * 100, 2, '.', '')) }}" required>
              @error('base_monthly_return_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 d-flex align-items-end">
              <div class="w-100 rounded border p-3 bg-light">
                <div class="text-secondary small">Current snapshot source</div>
                <div class="fw-semibold fs-5 text-capitalize">{{ $latestLog?->source ?? 'none yet' }}</div>
              </div>
            </div>
            <div class="col-12">
              <div class="rounded border p-3 bg-light">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                  <div>
                    <div class="fw-semibold">Package return preview</div>
                    <div class="text-secondary small">These package rates will sync when you save this miner.</div>
                  </div>
                  <span class="badge bg-primary-subtle text-primary" id="minerBaseMonthlyReturnRatePreviewBadge">
                    Base: {{ number_format((float) old('base_monthly_return_rate', (float) $miner->base_monthly_return_rate * 100), 2) }}%
                  </span>
                </div>
                <div class="row g-3">
                  @foreach ($miner->packages as $package)
                    @php
                      $packageIsStarter = (float) $package->price <= 0 || (int) $package->shares_count <= 0;
                      $rateBonus = $packageIsStarter ? 0 : round((float) $package->monthly_return_rate - (float) $miner->base_monthly_return_rate, 4);
                    @endphp
                    <div class="col-md-4">
                      <div class="border rounded p-3 h-100 bg-white">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                          <div>
                            <div class="fw-semibold">{{ $package->name }}</div>
                            <div class="text-secondary small">{{ $package->shares_count }} shares</div>
                          </div>
                          <span class="badge {{ $packageIsStarter ? 'bg-secondary-subtle text-secondary' : 'bg-success-subtle text-success' }}">
                            {{ $packageIsStarter ? 'Fixed' : ($rateBonus >= 0 ? '+' : '').number_format($rateBonus * 100, 2).'%' }}
                          </span>
                        </div>
                        <div class="text-secondary small mb-1">Projected monthly return</div>
                        <div class="fw-bold fs-5 miner-package-rate-preview" data-rate-bonus="{{ $rateBonus }}" data-is-starter="{{ $packageIsStarter ? '1' : '0' }}">
                          {{ number_format((float) $package->monthly_return_rate * 100, 2) }}%
                        </div>
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-primary">Save miner details</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-xl-5 grid-margin stretch-card">
    <div class="d-flex flex-column gap-4 w-100">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
              <h5 class="mb-1">Generate automatic daily snapshot</h5>
              <p class="text-secondary mb-0">Use the ZagChain formula engine to project today’s revenue, uptime, costs, and per-share income.</p>
            </div>
            <span class="badge bg-primary-subtle text-primary">Live formula</span>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-6">
              <div class="rounded border p-3 bg-light h-100">
                <div class="text-secondary small">Electricity cost</div>
                <div class="fw-semibold">${{ number_format((float) ($automaticSnapshot['electricity_cost_usd'] ?? 0), 2) }}</div>
              </div>
            </div>
            <div class="col-6">
              <div class="rounded border p-3 bg-light h-100">
                <div class="text-secondary small">Maintenance cost</div>
                <div class="fw-semibold">${{ number_format((float) ($automaticSnapshot['maintenance_cost_usd'] ?? 0), 2) }}</div>
              </div>
            </div>
          </div>
          <form method="POST" action="{{ route('dashboard.miner.logs.generate') }}">
            @csrf
            <input type="hidden" name="miner_slug" value="{{ $miner->slug }}">
            <div class="mb-3">
              <label class="form-label">Snapshot date</label>
              <input type="date" name="logged_on" class="form-control" value="{{ old('logged_on', $snapshotDate->toDateString()) }}">
            </div>
            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-outline-primary">Generate automatic snapshot and sync earnings</button>
              <a href="{{ route('dashboard.miner.logs.template', ['miner' => $miner->slug]) }}" class="btn btn-outline-secondary">Download CSV template</a>
            </div>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <h5 class="mb-3">Add daily performance log</h5>
          <form method="POST" action="{{ route('dashboard.miner.logs.store') }}">
            @csrf
            <input type="hidden" name="miner_slug" value="{{ $miner->slug }}">
            <div class="mb-3">
              <label class="form-label">Log date</label>
              <input type="date" name="logged_on" class="form-control @error('logged_on') is-invalid @enderror" value="{{ old('logged_on', now()->toDateString()) }}" required>
              @error('logged_on')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
              <label class="form-label">Revenue (USD)</label>
              <input type="number" step="0.01" min="0" name="revenue_usd" class="form-control @error('revenue_usd') is-invalid @enderror" value="{{ old('revenue_usd', $automaticSnapshot['revenue_usd'] ?? $miner->daily_output_usd) }}" required>
              @error('revenue_usd')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Electricity cost (USD)</label>
                <input type="number" step="0.01" min="0" name="electricity_cost_usd" class="form-control @error('electricity_cost_usd') is-invalid @enderror" value="{{ old('electricity_cost_usd', $automaticSnapshot['electricity_cost_usd'] ?? 0) }}">
                @error('electricity_cost_usd')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="col-md-6">
                <label class="form-label">Maintenance cost (USD)</label>
                <input type="number" step="0.01" min="0" name="maintenance_cost_usd" class="form-control @error('maintenance_cost_usd') is-invalid @enderror" value="{{ old('maintenance_cost_usd', $automaticSnapshot['maintenance_cost_usd'] ?? 0) }}">
                @error('maintenance_cost_usd')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="row g-3 mt-0">
              <div class="col-md-6">
                <label class="form-label">Hashrate (TH/s)</label>
                <input type="number" step="0.01" min="0" name="hashrate_th" class="form-control @error('hashrate_th') is-invalid @enderror" value="{{ old('hashrate_th', $automaticSnapshot['hashrate_th'] ?? 500) }}" required>
                @error('hashrate_th')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="col-md-6">
                <label class="form-label">Uptime %</label>
                <input type="number" step="0.01" min="0" max="100" name="uptime_percentage" class="form-control @error('uptime_percentage') is-invalid @enderror" value="{{ old('uptime_percentage', $automaticSnapshot['uptime_percentage'] ?? 99.50) }}" required>
                @error('uptime_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="mb-3 mt-3">
              <label class="form-label">Notes</label>
              <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
              @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-success">Save performance log and sync earnings</button>
            </div>
          </form>
          <hr class="my-4">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
              <div class="fw-semibold">Copy yesterday</div>
              <div class="text-secondary small">Reuse the last recorded day when the new day is mostly unchanged.</div>
            </div>
            <span class="badge bg-light text-dark">Semi-manual helper</span>
          </div>
          <form method="POST" action="{{ route('dashboard.miner.logs.copy-yesterday') }}">
            @csrf
            <input type="hidden" name="miner_slug" value="{{ $miner->slug }}">
            <div class="mb-3">
              <label class="form-label">Target date</label>
              <input type="date" name="logged_on" class="form-control @error('logged_on') is-invalid @enderror" value="{{ old('logged_on', now()->toDateString()) }}" required>
              @error('logged_on')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-outline-secondary w-100">Copy latest previous log into this date</button>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
              <h5 class="mb-1">Import CSV performance logs</h5>
              <p class="text-secondary mb-0">Bulk import daily rows when you receive miner history from an external sheet or ops export.</p>
            </div>
            <span class="badge bg-info-subtle text-info">Bulk import</span>
          </div>
          <div class="border rounded p-3 bg-light mb-3">
            <div class="fw-semibold mb-1">Expected columns</div>
            <div class="text-secondary small"><code>logged_on</code>, <code>revenue_usd</code>, <code>hashrate_th</code>, <code>uptime_percentage</code></div>
            <div class="text-secondary small mt-1">Optional: <code>electricity_cost_usd</code>, <code>maintenance_cost_usd</code>, <code>notes</code></div>
          </div>
          <form method="POST" action="{{ route('dashboard.miner.logs.import') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="miner_slug" value="{{ $miner->slug }}">
            <div class="mb-3">
              <label class="form-label">CSV file</label>
              <input type="file" name="csv_file" accept=".csv,text/csv" class="form-control @error('csv_file') is-invalid @enderror" required>
              @error('csv_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-outline-info w-100">Import CSV logs and sync earnings</button>
          </form>
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
            <h5 class="mb-1">Recent performance logs</h5>
            <p class="text-secondary mb-0">Latest recorded revenue, costs, per-share income, and source for the technical/admin history.</p>
          </div>
          <span class="badge bg-primary">{{ $recentLogs->count() }} recent entries</span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Date</th>
                <th>Source</th>
                <th>Revenue</th>
                <th>Costs</th>
                <th>Net profit</th>
                <th>Per share</th>
                <th>Hashrate</th>
                <th>Uptime</th>
                <th>Notes</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($recentLogs as $log)
                <tr>
                  <td>{{ $log->logged_on?->format('M d, Y') }}</td>
                  <td><span class="badge bg-light text-dark text-capitalize">{{ str_replace('_', ' ', $log->source ?? 'manual') }}</span></td>
                  <td>${{ number_format((float) $log->revenue_usd, 2) }}</td>
                  <td>
                    ${{ number_format((float) $log->electricity_cost_usd, 2) }} elec<br>
                    ${{ number_format((float) $log->maintenance_cost_usd, 2) }} maint
                  </td>
                  <td>${{ number_format((float) $log->net_profit_usd, 2) }}</td>
                  <td>${{ number_format((float) $log->revenue_per_share_usd, 4) }}</td>
                  <td>{{ number_format((float) $log->hashrate_th, 2) }} TH/s</td>
                  <td>{{ number_format((float) $log->uptime_percentage, 2) }}%</td>
                  <td>{{ $log->notes ?: '—' }}</td>
                </tr>
              @endforeach
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
    const baseRateInput = document.getElementById('minerBaseMonthlyReturnRateInput');
    const baseRateBadge = document.getElementById('minerBaseMonthlyReturnRatePreviewBadge');
    const packageRateEls = document.querySelectorAll('.miner-package-rate-preview');

    if (!baseRateInput || !packageRateEls.length) {
      return;
    }

    const renderPreview = () => {
      const baseRate = Number.parseFloat(baseRateInput.value || '0');
      const safeBaseRate = Number.isFinite(baseRate) ? baseRate : 0;

      if (baseRateBadge) {
        baseRateBadge.textContent = `Base: ${safeBaseRate.toFixed(2)}%`;
      }

      packageRateEls.forEach((el) => {
        const isStarter = el.dataset.isStarter === '1';
        const rateBonus = Number.parseFloat(el.dataset.rateBonus || '0');
        const projectedRate = isStarter ? 0 : safeBaseRate + (Number.isFinite(rateBonus) ? (rateBonus * 100) : 0);

        el.textContent = `${Math.max(projectedRate, 0).toFixed(2)}%`;
      });
    };

    baseRateInput.addEventListener('input', renderPreview);
    renderPreview();
  });
</script>
@endpush


