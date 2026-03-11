@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h4 class="mb-1">Manage {{ $miner->name }}</h4>
        <p class="text-secondary mb-0">Update miner details and record daily cloud mining performance from one screen.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('dashboard') }}?miner={{ $miner->slug }}" class="btn btn-outline-primary btn-icon-text">
          <i data-lucide="layout-dashboard" class="btn-icon-prepend"></i> Dashboard
        </a>
        <a href="{{ route('general.sell-products') }}?miner={{ $miner->slug }}" class="btn btn-primary btn-icon-text">
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

@if (($miners ?? collect())->count() > 1)
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div>
            <h6 class="mb-1">Select miner</h6>
            <p class="text-secondary mb-0">Switch between active miners and manage each one with the same admin screen.</p>
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
  <div class="col-md-4 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <p class="text-secondary mb-1">Share price</p>
        <h4 class="mb-0">${{ number_format((float) $miner->share_price, 2) }}</h4>
      </div>
    </div>
  </div>
  <div class="col-md-4 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <p class="text-secondary mb-1">Shares sold</p>
        <h4 class="mb-0">{{ number_format($sharesSold) }} / {{ number_format($miner->total_shares) }}</h4>
      </div>
    </div>
  </div>
  <div class="col-md-4 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <p class="text-secondary mb-1">Status</p>
        <h4 class="mb-0 text-capitalize">{{ $miner->status }}</h4>
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
              <label class="form-label">Base monthly return rate</label>
              <input type="number" step="0.0001" min="0" max="1" name="base_monthly_return_rate" class="form-control @error('base_monthly_return_rate') is-invalid @enderror" value="{{ old('base_monthly_return_rate', $miner->base_monthly_return_rate) }}" required>
              @error('base_monthly_return_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 d-flex align-items-end">
              <div class="w-100 rounded border p-3 bg-light">
                <div class="text-secondary small">Available shares</div>
                <div class="fw-semibold fs-5">{{ number_format($availableShares) }}</div>
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
            <input type="number" step="0.01" min="0" name="revenue_usd" class="form-control @error('revenue_usd') is-invalid @enderror" value="{{ old('revenue_usd', $miner->daily_output_usd) }}" required>
            @error('revenue_usd')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="mb-3">
            <label class="form-label">Hashrate (TH/s)</label>
            <input type="number" step="0.01" min="0" name="hashrate_th" class="form-control @error('hashrate_th') is-invalid @enderror" value="{{ old('hashrate_th', 500) }}" required>
            @error('hashrate_th')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="mb-3">
            <label class="form-label">Uptime %</label>
            <input type="number" step="0.01" min="0" max="100" name="uptime_percentage" class="form-control @error('uptime_percentage') is-invalid @enderror" value="{{ old('uptime_percentage', 99.50) }}" required>
            @error('uptime_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="mb-3">
            <label class="form-label">Notes</label>
            <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <button type="submit" class="btn btn-success">Save performance log</button>
        </form>
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
            <p class="text-secondary mb-0">Latest recorded revenue, hashrate, and uptime values.</p>
          </div>
          <span class="badge bg-primary">{{ $recentLogs->count() }} recent entries</span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Date</th>
                <th>Revenue</th>
                <th>Hashrate</th>
                <th>Uptime</th>
                <th>Notes</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($recentLogs as $log)
                <tr>
                  <td>{{ $log->logged_on?->format('M d, Y') }}</td>
                  <td>${{ number_format((float) $log->revenue_usd, 2) }}</td>
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
