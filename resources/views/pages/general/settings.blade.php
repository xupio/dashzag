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
          <div class="border rounded p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
              <div>
                <h6 class="mb-1">Payout methods</h6>
                <p class="text-secondary mb-0">Choose which withdrawal options users can request and define the destination hint for each one.</p>
              </div>
              <span class="badge bg-success">Wallet payout controls</span>
            </div>

            @foreach ([
              'btc_wallet' => 'Bitcoin wallet',
              'usdt_wallet' => 'USDT wallet',
              'bank_transfer' => 'Bank transfer',
            ] as $prefix => $label)
              <div class="border rounded p-3 mb-3 bg-light">
                <div class="form-check form-switch mb-3">
                  <input type="hidden" name="payout_{{ $prefix }}_enabled" value="0">
                  <input class="form-check-input" type="checkbox" role="switch" id="payout_{{ $prefix }}_enabled" name="payout_{{ $prefix }}_enabled" value="1" @checked(old('payout_'.$prefix.'_enabled', $settings['payout_'.$prefix.'_enabled']) == '1')>
                  <label class="form-check-label fw-semibold" for="payout_{{ $prefix }}_enabled">Enable {{ $label }}</label>
                </div>
                <div class="row g-3">
                  <div class="col-md-4">
                    <label class="form-label">Label</label>
                    <input type="text" name="payout_{{ $prefix }}_label" class="form-control @error('payout_'.$prefix.'_label') is-invalid @enderror" value="{{ old('payout_'.$prefix.'_label', $settings['payout_'.$prefix.'_label']) }}" required>
                    @error('payout_'.$prefix.'_label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="col-md-8">
                    <label class="form-label">Destination placeholder</label>
                    <input type="text" name="payout_{{ $prefix }}_placeholder" class="form-control @error('payout_'.$prefix.'_placeholder') is-invalid @enderror" value="{{ old('payout_'.$prefix.'_placeholder', $settings['payout_'.$prefix.'_placeholder']) }}" required>
                    @error('payout_'.$prefix.'_placeholder')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Minimum amount</label>
                    <input type="number" step="0.01" min="0" name="payout_{{ $prefix }}_minimum_amount" class="form-control @error('payout_'.$prefix.'_minimum_amount') is-invalid @enderror" value="{{ old('payout_'.$prefix.'_minimum_amount', $settings['payout_'.$prefix.'_minimum_amount']) }}" required>
                    @error('payout_'.$prefix.'_minimum_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Fixed fee</label>
                    <input type="number" step="0.01" min="0" name="payout_{{ $prefix }}_fixed_fee" class="form-control @error('payout_'.$prefix.'_fixed_fee') is-invalid @enderror" value="{{ old('payout_'.$prefix.'_fixed_fee', $settings['payout_'.$prefix.'_fixed_fee']) }}" required>
                    @error('payout_'.$prefix.'_fixed_fee')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Percentage fee rate</label>
                    <input type="number" step="0.0001" min="0" max="1" name="payout_{{ $prefix }}_percentage_fee_rate" class="form-control @error('payout_'.$prefix.'_percentage_fee_rate') is-invalid @enderror" value="{{ old('payout_'.$prefix.'_percentage_fee_rate', $settings['payout_'.$prefix.'_percentage_fee_rate']) }}" required>
                    @error('payout_'.$prefix.'_percentage_fee_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="col-md-8">
                    <label class="form-label">Instruction text</label>
                    <input type="text" name="payout_{{ $prefix }}_instruction" class="form-control @error('payout_'.$prefix.'_instruction') is-invalid @enderror" value="{{ old('payout_'.$prefix.'_instruction', $settings['payout_'.$prefix.'_instruction']) }}" required>
                    @error('payout_'.$prefix.'_instruction')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Processing time</label>
                    <input type="text" name="payout_{{ $prefix }}_processing_time" class="form-control @error('payout_'.$prefix.'_processing_time') is-invalid @enderror" value="{{ old('payout_'.$prefix.'_processing_time', $settings['payout_'.$prefix.'_processing_time']) }}" required>
                    @error('payout_'.$prefix.'_processing_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                </div>
              </div>
            @endforeach
          </div>

            <div class="border rounded p-3 mb-4 bg-white">
              <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                  <h6 class="mb-1">Incoming payment methods</h6>
                  <p class="text-secondary mb-0">Define the wallet or bank details investors should use when submitting share payments.</p>
                </div>
                <span class="badge bg-warning text-dark">Buy Shares instructions</span>
              </div>

              @foreach ([
                'btc_transfer' => 'BTC transfer',
                'usdt_transfer' => 'USDT transfer',
                'bank_transfer' => 'Bank transfer',
              ] as $prefix => $label)
                <div class="border rounded p-3 mb-3 bg-light">
                  <div class="form-check form-switch mb-3">
                    <input type="hidden" name="payment_{{ $prefix }}_enabled" value="0">
                    <input class="form-check-input" type="checkbox" role="switch" id="payment_{{ $prefix }}_enabled" name="payment_{{ $prefix }}_enabled" value="1" @checked(old('payment_'.$prefix.'_enabled', $settings['payment_'.$prefix.'_enabled']) == '1')>
                    <label class="form-check-label fw-semibold" for="payment_{{ $prefix }}_enabled">Enable {{ $label }}</label>
                  </div>
                  <div class="row g-3">
                    <div class="col-md-4">
                      <label class="form-label">Label</label>
                      <input type="text" name="payment_{{ $prefix }}_label" class="form-control @error('payment_'.$prefix.'_label') is-invalid @enderror" value="{{ old('payment_'.$prefix.'_label', $settings['payment_'.$prefix.'_label']) }}" required>
                      @error('payment_'.$prefix.'_label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-8">
                      <label class="form-label">Destination details</label>
                      <input type="text" name="payment_{{ $prefix }}_destination" class="form-control @error('payment_'.$prefix.'_destination') is-invalid @enderror" value="{{ old('payment_'.$prefix.'_destination', $settings['payment_'.$prefix.'_destination']) }}" required>
                      @error('payment_'.$prefix.'_destination')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Reference hint</label>
                      <input type="text" name="payment_{{ $prefix }}_reference_hint" class="form-control @error('payment_'.$prefix.'_reference_hint') is-invalid @enderror" value="{{ old('payment_'.$prefix.'_reference_hint', $settings['payment_'.$prefix.'_reference_hint']) }}" required>
                      @error('payment_'.$prefix.'_reference_hint')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Instruction text</label>
                      <input type="text" name="payment_{{ $prefix }}_instruction" class="form-control @error('payment_'.$prefix.'_instruction') is-invalid @enderror" value="{{ old('payment_'.$prefix.'_instruction', $settings['payment_'.$prefix.'_instruction']) }}" required>
                      @error('payment_'.$prefix.'_instruction')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="col-12">
                      <label class="form-label">Admin review note</label>
                      <input type="text" name="payment_{{ $prefix }}_admin_review_note" class="form-control @error('payment_'.$prefix.'_admin_review_note') is-invalid @enderror" value="{{ old('payment_'.$prefix.'_admin_review_note', $settings['payment_'.$prefix.'_admin_review_note']) }}" required>
                      <div class="form-text">Shown only in Operations to help the admin team review this payment method.</div>
                      @error('payment_'.$prefix.'_admin_review_note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">Save platform defaults</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>
@endsection




