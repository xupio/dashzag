@extends('layout.master')

@section('content')
@php
  $rewardRates = $scenario['reward_rates'];
  $profits = $scenario['profits'];
  $profilePower = $scenario['profile_power'];
  $minerMetrics = $scenario['miner_metrics'];
  $network = $scenario['network'];
@endphp

<div class="row">
  <div class="col-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h4 class="mb-1">Mock Manager</h4>
        <p class="text-secondary mb-0">Test a monthly investor scenario before touching live data. Enter miner month inputs, referral counts, and subscriber levels to see the final projected profit.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('dashboard.rewards') }}" class="btn btn-outline-primary btn-icon-text">
          <i data-lucide="badge-percent" class="btn-icon-prepend"></i> Reward settings
        </a>
        <a href="{{ route('dashboard.miner') }}?miner={{ $miner->slug }}" class="btn btn-primary btn-icon-text">
          <i data-lucide="pickaxe" class="btn-icon-prepend"></i> Miner setup
        </a>
      </div>
    </div>
  </div>
</div>

@if (session('mock_manager_success'))
  <div class="alert alert-success">{{ session('mock_manager_success') }}</div>
@endif

@if ($errors->any())
  <div class="alert alert-danger">
    <div class="fw-semibold mb-1">Mock scenario could not be calculated.</div>
    <div>Check the fields below and try again.</div>
  </div>
@endif

<div class="row mb-4">
  <div class="col-md-4 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <p class="text-secondary mb-1">Selected miner</p>
        <h5 class="mb-1">{{ $miner->name }}</h5>
        <p class="text-secondary mb-0">Base monthly output: ${{ number_format((float) $miner->monthly_output_usd, 2) }}</p>
      </div>
    </div>
  </div>
  <div class="col-md-4 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <p class="text-secondary mb-1">Scenario package</p>
        <h5 class="mb-1">{{ $selectedPackage->name }}</h5>
        <p class="text-secondary mb-0">${{ number_format((float) $selectedPackage->price, 2) }} for {{ $selectedPackage->shares_count }} shares</p>
      </div>
    </div>
  </div>
  <div class="col-md-4 grid-margin stretch-card">
    <div class="card border-primary">
      <div class="card-body">
        <p class="text-secondary mb-1">Final projected profit</p>
        <h4 class="mb-1">${{ number_format((float) $profits['final_projected_profit'], 2) }}</h4>
        <p class="text-secondary mb-0">Reward engine + network rewards + miner monthly share reference</p>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-xl-5 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h5 class="mb-3">Scenario inputs</h5>

        @if (($miners ?? collect())->count() > 1)
          <div class="mb-3">
            <div class="small text-secondary mb-2">Quick miner switch</div>
            <div class="d-flex flex-wrap gap-2">
              @foreach ($miners as $networkMiner)
                <a href="{{ route('dashboard.mock-manager', ['miner' => $networkMiner->slug]) }}" class="btn {{ $networkMiner->id === $miner->id ? 'btn-primary' : 'btn-outline-primary' }} btn-sm">
                  {{ $networkMiner->name }}
                </a>
              @endforeach
            </div>
          </div>
        @endif

        @if (($savedScenarios ?? collect())->isNotEmpty())
          <div class="mb-3">
            <div class="small text-secondary mb-2">Saved scenarios</div>
            <div class="d-flex flex-column gap-2">
              @foreach ($savedScenarios as $storedScenario)
                <div class="d-flex justify-content-between align-items-center border rounded p-2 {{ $savedScenario?->id === $storedScenario->id ? 'bg-light border-primary' : '' }}">
                  <div>
                    <div class="fw-semibold">{{ $storedScenario->name }}</div>
                    <div class="small text-secondary">{{ $storedScenario->miner?->name }} · {{ $storedScenario->package?->name }}</div>
                  </div>
                  <div class="d-flex gap-2">
                    <a href="{{ route('dashboard.mock-manager', ['miner' => $storedScenario->miner?->slug, 'scenario' => $storedScenario->id]) }}" class="btn btn-outline-primary btn-sm">Open</a>
                    <form method="POST" action="{{ route('dashboard.mock-manager.delete', $storedScenario) }}">
                      @csrf
                      <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                    </form>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        @endif

        <form method="POST" action="{{ route('dashboard.mock-manager.calculate') }}">
          @csrf
          <input type="hidden" name="miner_slug" value="{{ $miner->slug }}">

          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Package to test</label>
              <select name="package_id" class="form-select @error('package_id') is-invalid @enderror">
                @foreach ($packages as $package)
                  <option value="{{ $package->id }}" @selected((int) $inputs['package_id'] === $package->id)>
                    {{ $package->name }} - ${{ number_format((float) $package->price, 2) }}
                  </option>
                @endforeach
              </select>
              @error('package_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
              <div class="border rounded p-3 bg-light">
                <div class="fw-semibold mb-2">Monthly miner inputs</div>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Monthly hashrate (TH)</label>
                    <input type="number" step="0.01" min="0" name="monthly_hashrate_th" class="form-control @error('monthly_hashrate_th') is-invalid @enderror" value="{{ old('monthly_hashrate_th', $inputs['monthly_hashrate_th']) }}">
                    @error('monthly_hashrate_th')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Active shares in miner</label>
                    <input type="number" min="1" name="active_shares" class="form-control @error('active_shares') is-invalid @enderror" value="{{ old('active_shares', $inputs['active_shares']) }}">
                    @error('active_shares')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Monthly revenue</label>
                    <input type="number" step="0.01" min="0" name="monthly_revenue_usd" class="form-control @error('monthly_revenue_usd') is-invalid @enderror" value="{{ old('monthly_revenue_usd', $inputs['monthly_revenue_usd']) }}">
                    @error('monthly_revenue_usd')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Electricity cost</label>
                    <input type="number" step="0.01" min="0" name="monthly_electricity_cost_usd" class="form-control @error('monthly_electricity_cost_usd') is-invalid @enderror" value="{{ old('monthly_electricity_cost_usd', $inputs['monthly_electricity_cost_usd']) }}">
                    @error('monthly_electricity_cost_usd')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Maintenance cost</label>
                    <input type="number" step="0.01" min="0" name="monthly_maintenance_cost_usd" class="form-control @error('monthly_maintenance_cost_usd') is-invalid @enderror" value="{{ old('monthly_maintenance_cost_usd', $inputs['monthly_maintenance_cost_usd']) }}">
                    @error('monthly_maintenance_cost_usd')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                </div>
              </div>
            </div>

            <div class="col-12">
              <div class="border rounded p-3 bg-light">
                <div class="fw-semibold mb-2">Profile power drivers</div>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Verified invitees</label>
                    <input type="number" min="0" name="verified_invites" class="form-control @error('verified_invites') is-invalid @enderror" value="{{ old('verified_invites', $inputs['verified_invites']) }}">
                    @error('verified_invites')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Registered referrals</label>
                    <input type="number" min="0" name="registered_referrals" class="form-control @error('registered_referrals') is-invalid @enderror" value="{{ old('registered_referrals', $inputs['registered_referrals']) }}">
                    @error('registered_referrals')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                </div>
              </div>
            </div>

            <div class="col-12">
              <div class="border rounded p-3 bg-light">
                <div class="fw-semibold mb-2">Network subscribers</div>
                <div class="small text-secondary mb-3">Enter how many subscribers you expect in levels 1 to 5 for each package tier.</div>
                @foreach (range(1, 5) as $depth)
                  <div class="border rounded bg-white p-3 mb-3">
                    <div class="fw-semibold mb-2">Level {{ $depth }}</div>
                    <div class="row g-3">
                      <div class="col-md-4">
                        <label class="form-label">Basic 100</label>
                        <input type="number" min="0" name="level_{{ $depth }}_basic_subscribers" class="form-control @error('level_'.$depth.'_basic_subscribers') is-invalid @enderror" value="{{ old('level_'.$depth.'_basic_subscribers', $inputs['level_'.$depth.'_basic_subscribers']) }}">
                        @error('level_'.$depth.'_basic_subscribers')<div class="invalid-feedback">{{ $message }}</div>@enderror
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">Growth 500</label>
                        <input type="number" min="0" name="level_{{ $depth }}_growth_subscribers" class="form-control @error('level_'.$depth.'_growth_subscribers') is-invalid @enderror" value="{{ old('level_'.$depth.'_growth_subscribers', $inputs['level_'.$depth.'_growth_subscribers']) }}">
                        @error('level_'.$depth.'_growth_subscribers')<div class="invalid-feedback">{{ $message }}</div>@enderror
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">Scale 1000+</label>
                        <input type="number" min="0" name="level_{{ $depth }}_scale_subscribers" class="form-control @error('level_'.$depth.'_scale_subscribers') is-invalid @enderror" value="{{ old('level_'.$depth.'_scale_subscribers', $inputs['level_'.$depth.'_scale_subscribers']) }}">
                        @error('level_'.$depth.'_scale_subscribers')<div class="invalid-feedback">{{ $message }}</div>@enderror
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>
            </div>

            <div class="col-12 d-flex gap-2 flex-wrap">
              <button type="submit" class="btn btn-primary btn-icon-text">
                <i data-lucide="play" class="btn-icon-prepend"></i> Run scenario
              </button>
              <button type="submit" formaction="{{ route('dashboard.mock-manager.save') }}" class="btn btn-outline-primary btn-icon-text">
                <i data-lucide="save" class="btn-icon-prepend"></i> Save scenario
              </button>
              <a href="{{ route('dashboard.mock-manager', ['miner' => $miner->slug]) }}" class="btn btn-outline-secondary btn-icon-text">
                <i data-lucide="rotate-ccw" class="btn-icon-prepend"></i> Reset to baseline
              </a>
            </div>
            <div class="col-12">
              <label class="form-label">Scenario name</label>
              <input type="text" name="scenario_name" class="form-control @error('scenario_name') is-invalid @enderror" value="{{ old('scenario_name', $savedScenario?->name) }}" placeholder="Example: Growth 500 medium team">
              @error('scenario_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
              <div class="form-text">Only needed when you want to save this setup for later.</div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-xl-7 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
          <div>
            <h5 class="mb-1">Scenario result</h5>
            <p class="text-secondary mb-0">This combines the current reward engine, profile power boost, and a monthly miner operations reference.</p>
          </div>
          <span class="badge bg-{{ $profilePower['rank_accent'] }}-subtle text-{{ $profilePower['rank_accent'] }}">
            {{ $profilePower['rank_label'] }}
          </span>
        </div>

        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <div class="border rounded p-3 h-100">
              <div class="text-secondary small">Profile power</div>
              <div class="d-flex align-items-end gap-2">
                <div class="display-6 fw-semibold">{{ $profilePower['score'] }}</div>
                <div class="text-secondary mb-2">/ 100</div>
              </div>
              <div class="small text-secondary">Remaining to full cap: {{ $scenario['guidance']['remaining_power_points'] }} points</div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="border rounded p-3 h-100">
              <div class="text-secondary small">Active direct investors</div>
              <div class="display-6 fw-semibold">{{ $network['active_direct_investors'] }}</div>
              <div class="small text-secondary">Derived from the first-level subscriber counts in this scenario.</div>
            </div>
          </div>
        </div>

        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <div class="card bg-primary-subtle border-0 h-100">
              <div class="card-body">
                <div class="text-primary-emphasis small mb-1">Reward engine</div>
                <div class="h3 mb-1">${{ number_format((float) $profits['reward_engine_profit'], 2) }}</div>
                <div class="small text-primary-emphasis">Personal package + network rewards</div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card bg-success-subtle border-0 h-100">
              <div class="card-body">
                <div class="text-success-emphasis small mb-1">Miner share reference</div>
                <div class="h3 mb-1">${{ number_format((float) $minerMetrics['personal_miner_income_usd'], 2) }}</div>
                <div class="small text-success-emphasis">{{ number_format((float) $minerMetrics['monthly_revenue_per_share_usd'], 4) }} per share</div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card bg-warning-subtle border-0 h-100">
              <div class="card-body">
                <div class="text-warning-emphasis small mb-1">Final projected total</div>
                <div class="h3 mb-1">${{ number_format((float) $profits['final_projected_profit'], 2) }}</div>
                <div class="small text-warning-emphasis">Combined mock result for this month</div>
              </div>
            </div>
          </div>
        </div>

        <div class="table-responsive mb-4">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th>Reward component</th>
                <th class="text-end">Rate</th>
                <th class="text-end">Amount</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Base package return</td>
                <td class="text-end">{{ number_format((float) $rewardRates['base_rate'] * 100, 2) }}%</td>
                <td class="text-end">${{ number_format((float) $selectedPackage->price * (float) $rewardRates['base_rate'], 2) }}</td>
              </tr>
              <tr>
                <td>Level bonus ({{ $profilePower['level']->name }})</td>
                <td class="text-end">{{ number_format((float) $rewardRates['level_bonus_rate'] * 100, 2) }}%</td>
                <td class="text-end">${{ number_format((float) $selectedPackage->price * (float) $rewardRates['level_bonus_rate'], 2) }}</td>
              </tr>
              <tr>
                <td>Invite + team bonus</td>
                <td class="text-end">{{ number_format((float) $rewardRates['team_bonus_rate'] * 100, 2) }}%</td>
                <td class="text-end">${{ number_format((float) $selectedPackage->price * (float) $rewardRates['team_bonus_rate'], 2) }}</td>
              </tr>
              <tr>
                <td>Profile power boost</td>
                <td class="text-end">{{ number_format((float) $rewardRates['profile_power_reward_rate'] * 100, 2) }}%</td>
                <td class="text-end">${{ number_format((float) $selectedPackage->price * (float) $rewardRates['profile_power_reward_rate'], 2) }}</td>
              </tr>
              <tr class="fw-semibold">
                <td>Total package reward</td>
                <td class="text-end">{{ number_format((float) $rewardRates['total_rate'] * 100, 2) }}%</td>
                <td class="text-end">${{ number_format((float) $profits['projected_package_profit'], 2) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <div class="border rounded p-3 h-100">
              <div class="fw-semibold mb-2">Network reward breakdown</div>
              <div class="d-flex justify-content-between small mb-2"><span>Registration rewards</span><span>${{ number_format((float) $profits['referral_registration_reward'], 2) }}</span></div>
              <div class="d-flex justify-content-between small mb-2"><span>Direct subscription rewards</span><span>${{ number_format((float) $profits['direct_subscription_reward'], 2) }}</span></div>
              @foreach ($profits['team_rewards_by_level'] as $depth => $amount)
                <div class="d-flex justify-content-between small {{ $loop->last ? '' : 'mb-2' }}">
                  <span>Level {{ $depth }} team rewards</span>
                  <span>${{ number_format((float) $amount, 2) }}</span>
                </div>
              @endforeach
            </div>
          </div>
          <div class="col-md-6">
            <div class="border rounded p-3 h-100">
              <div class="fw-semibold mb-2">Miner month reference</div>
              <div class="d-flex justify-content-between small mb-2"><span>Monthly revenue</span><span>${{ number_format((float) $minerMetrics['monthly_revenue_usd'], 2) }}</span></div>
              <div class="d-flex justify-content-between small mb-2"><span>Monthly net profit</span><span>${{ number_format((float) $minerMetrics['monthly_net_profit_usd'], 2) }}</span></div>
              <div class="d-flex justify-content-between small mb-2"><span>Revenue per share</span><span>${{ number_format((float) $minerMetrics['monthly_revenue_per_share_usd'], 4) }}</span></div>
              <div class="d-flex justify-content-between small"><span>Revenue per TH</span><span>${{ number_format((float) $minerMetrics['efficiency_per_th_usd'], 2) }}</span></div>
            </div>
          </div>
        </div>

        <div class="border rounded p-3 bg-light">
          <div class="fw-semibold mb-2">How to read this test</div>
          <div class="text-secondary small">The reward engine uses the same live rules as the platform: package return, current level bonus, invite/team bonus, and profile-power cap boost. The miner month section is a separate operations reference so you can compare platform rewards against the miner’s monthly net result.</div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
