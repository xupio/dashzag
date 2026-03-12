@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h4 class="mb-1">Platform Settings</h4>
        <p class="text-secondary mb-0">Control default miner values and the package templates created automatically for every new miner.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('dashboard.miners') }}" class="btn btn-outline-primary btn-icon-text">
          <i data-lucide="server" class="btn-icon-prepend"></i> Back to miners
        </a>
        <a href="{{ route('dashboard.rewards') }}" class="btn btn-outline-secondary btn-icon-text">
          <i data-lucide="percent" class="btn-icon-prepend"></i> Rewards
        </a>
      </div>
    </div>
  </div>
</div>

@if (session('settings_success'))
  <div class="alert alert-success">{{ session('settings_success') }}</div>
@endif

<form method="POST" action="{{ route('dashboard.settings.update') }}">
  @csrf
  <div class="row">
    <div class="col-xl-5 grid-margin stretch-card">
      <div class="card w-100">
        <div class="card-body">
          <h5 class="mb-3">New miner defaults</h5>
          <p class="text-secondary mb-4">These values prefill the create-miner workflow and define the default economics for new mining units.</p>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Total shares</label>
              <input type="number" min="1" name="new_miner_total_shares" class="form-control @error('new_miner_total_shares') is-invalid @enderror" value="{{ old('new_miner_total_shares', $settings['new_miner_total_shares']) }}" required>
              @error('new_miner_total_shares')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label">Share price</label>
              <input type="number" step="0.01" min="1" name="new_miner_share_price" class="form-control @error('new_miner_share_price') is-invalid @enderror" value="{{ old('new_miner_share_price', $settings['new_miner_share_price']) }}" required>
              @error('new_miner_share_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label">Daily output (USD)</label>
              <input type="number" step="0.01" min="0" name="new_miner_daily_output_usd" class="form-control @error('new_miner_daily_output_usd') is-invalid @enderror" value="{{ old('new_miner_daily_output_usd', $settings['new_miner_daily_output_usd']) }}" required>
              @error('new_miner_daily_output_usd')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label">Monthly output (USD)</label>
              <input type="number" step="0.01" min="0" name="new_miner_monthly_output_usd" class="form-control @error('new_miner_monthly_output_usd') is-invalid @enderror" value="{{ old('new_miner_monthly_output_usd', $settings['new_miner_monthly_output_usd']) }}" required>
              @error('new_miner_monthly_output_usd')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
              <label class="form-label">Base monthly return rate</label>
              <input type="number" step="0.0001" min="0" max="1" name="new_miner_base_monthly_return_rate" class="form-control @error('new_miner_base_monthly_return_rate') is-invalid @enderror" value="{{ old('new_miner_base_monthly_return_rate', $settings['new_miner_base_monthly_return_rate']) }}" required>
              @error('new_miner_base_monthly_return_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-7 grid-margin stretch-card">
      <div class="card w-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
              <h5 class="mb-1">Auto package templates</h5>
              <p class="text-secondary mb-0">Every new miner receives these 3 packages automatically based on the multipliers and bonus rates below.</p>
            </div>
            <span class="badge bg-primary">3 packages per new miner</span>
          </div>

          @foreach ([
            'launch' => 'Launch package',
            'growth' => 'Growth package',
            'scale' => 'Scale package',
          ] as $prefix => $label)
            <div class="border rounded p-3 mb-3 bg-light">
              <h6 class="mb-3">{{ $label }}</h6>
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">Name</label>
                  <input type="text" name="{{ $prefix }}_package_name" class="form-control @error($prefix.'_package_name') is-invalid @enderror" value="{{ old($prefix.'_package_name', $settings[$prefix.'_package_name']) }}" required>
                  @error($prefix.'_package_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                  <label class="form-label">Shares</label>
                  <input type="number" min="1" name="{{ $prefix }}_package_shares_count" class="form-control @error($prefix.'_package_shares_count') is-invalid @enderror" value="{{ old($prefix.'_package_shares_count', $settings[$prefix.'_package_shares_count']) }}" required>
                  @error($prefix.'_package_shares_count')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                  <label class="form-label">Units</label>
                  <input type="number" min="1" name="{{ $prefix }}_package_units_limit" class="form-control @error($prefix.'_package_units_limit') is-invalid @enderror" value="{{ old($prefix.'_package_units_limit', $settings[$prefix.'_package_units_limit']) }}" required>
                  @error($prefix.'_package_units_limit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                  <label class="form-label">Price x</label>
                  <input type="number" step="0.1" min="0.1" name="{{ $prefix }}_package_price_multiplier" class="form-control @error($prefix.'_package_price_multiplier') is-invalid @enderror" value="{{ old($prefix.'_package_price_multiplier', $settings[$prefix.'_package_price_multiplier']) }}" required>
                  @error($prefix.'_package_price_multiplier')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                  <label class="form-label">Rate bonus</label>
                  <input type="number" step="0.0001" min="0" max="1" name="{{ $prefix }}_package_rate_bonus" class="form-control @error($prefix.'_package_rate_bonus') is-invalid @enderror" value="{{ old($prefix.'_package_rate_bonus', $settings[$prefix.'_package_rate_bonus']) }}" required>
                  @error($prefix.'_package_rate_bonus')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>
            </div>
          @endforeach

          <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">Save platform defaults</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>
@endsection
