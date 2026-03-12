@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h4 class="mb-1">Reward Settings</h4>
        <p class="text-secondary mb-0">Control referral rewards, team bonuses, and the Free Starter unlock mission from the admin panel.</p>
      </div>
      <a href="{{ route('dashboard.network-admin') }}" class="btn btn-outline-primary btn-icon-text">
        <i data-lucide="git-branch-plus" class="btn-icon-prepend"></i> Open network admin
      </a>
    </div>
  </div>
</div>

@if (session('rewards_success'))
  <div class="alert alert-success">{{ session('rewards_success') }}</div>
@endif

<form method="POST" action="{{ route('dashboard.rewards.update') }}" class="row g-4">
  @csrf
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <h5 class="mb-3">Free Starter mission</h5>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Verified invites required</label>
            <input type="number" min="1" name="free_starter_verified_invites_required" class="form-control @error('free_starter_verified_invites_required') is-invalid @enderror" value="{{ old('free_starter_verified_invites_required', $settings['free_starter_verified_invites_required'] ?? 20) }}" required>
            @error('free_starter_verified_invites_required')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">Direct Basic 100 subscribers required</label>
            <input type="number" min="1" name="free_starter_direct_basic_required" class="form-control @error('free_starter_direct_basic_required') is-invalid @enderror" value="{{ old('free_starter_direct_basic_required', $settings['free_starter_direct_basic_required'] ?? 1) }}" required>
            @error('free_starter_direct_basic_required')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="mb-3">Reward payouts</h5>
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Referral registration reward</label>
            <input type="number" step="0.01" min="0" name="referral_registration_reward" class="form-control @error('referral_registration_reward') is-invalid @enderror" value="{{ old('referral_registration_reward', $settings['referral_registration_reward'] ?? 25) }}" required>
            @error('referral_registration_reward')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-12">
            <label class="form-label">Referral subscription reward rate</label>
            <input type="number" step="0.0001" min="0" max="1" name="referral_subscription_reward_rate" class="form-control @error('referral_subscription_reward_rate') is-invalid @enderror" value="{{ old('referral_subscription_reward_rate', $settings['referral_subscription_reward_rate'] ?? 0.05) }}" required>
            @error('referral_subscription_reward_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-12">
            <label class="form-label">Direct team subscription reward rate</label>
            <input type="number" step="0.0001" min="0" max="1" name="team_direct_subscription_reward_rate" class="form-control @error('team_direct_subscription_reward_rate') is-invalid @enderror" value="{{ old('team_direct_subscription_reward_rate', $settings['team_direct_subscription_reward_rate'] ?? 0.03) }}" required>
            @error('team_direct_subscription_reward_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-12">
            <label class="form-label">Second-level team reward rate</label>
            <input type="number" step="0.0001" min="0" max="1" name="team_indirect_subscription_reward_rate" class="form-control @error('team_indirect_subscription_reward_rate') is-invalid @enderror" value="{{ old('team_indirect_subscription_reward_rate', $settings['team_indirect_subscription_reward_rate'] ?? 0.01) }}" required>
            @error('team_indirect_subscription_reward_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="mb-3">Bonus thresholds</h5>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Invite bonus after 10 verified</label>
            <input type="number" step="0.0001" min="0" max="1" name="invitation_bonus_after_10_rate" class="form-control @error('invitation_bonus_after_10_rate') is-invalid @enderror" value="{{ old('invitation_bonus_after_10_rate', $settings['invitation_bonus_after_10_rate'] ?? 0.003) }}" required>
            @error('invitation_bonus_after_10_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">Invite bonus after 20 verified</label>
            <input type="number" step="0.0001" min="0" max="1" name="invitation_bonus_after_20_rate" class="form-control @error('invitation_bonus_after_20_rate') is-invalid @enderror" value="{{ old('invitation_bonus_after_20_rate', $settings['invitation_bonus_after_20_rate'] ?? 0.0075) }}" required>
            @error('invitation_bonus_after_20_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">Invite bonus after 50 verified</label>
            <input type="number" step="0.0001" min="0" max="1" name="invitation_bonus_after_50_rate" class="form-control @error('invitation_bonus_after_50_rate') is-invalid @enderror" value="{{ old('invitation_bonus_after_50_rate', $settings['invitation_bonus_after_50_rate'] ?? 0.015) }}" required>
            @error('invitation_bonus_after_50_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">Team bonus after 1 active investor</label>
            <input type="number" step="0.0001" min="0" max="1" name="team_bonus_after_1_investor_rate" class="form-control @error('team_bonus_after_1_investor_rate') is-invalid @enderror" value="{{ old('team_bonus_after_1_investor_rate', $settings['team_bonus_after_1_investor_rate'] ?? 0.0025) }}" required>
            @error('team_bonus_after_1_investor_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">Team bonus after 3 active investors</label>
            <input type="number" step="0.0001" min="0" max="1" name="team_bonus_after_3_investor_rate" class="form-control @error('team_bonus_after_3_investor_rate') is-invalid @enderror" value="{{ old('team_bonus_after_3_investor_rate', $settings['team_bonus_after_3_investor_rate'] ?? 0.005) }}" required>
            @error('team_bonus_after_3_investor_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">Team bonus after 5 active investors</label>
            <input type="number" step="0.0001" min="0" max="1" name="team_bonus_after_5_investor_rate" class="form-control @error('team_bonus_after_5_investor_rate') is-invalid @enderror" value="{{ old('team_bonus_after_5_investor_rate', $settings['team_bonus_after_5_investor_rate'] ?? 0.01) }}" required>
            @error('team_bonus_after_5_investor_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <h5 class="mb-3">MLM depth reward rates</h5>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Level 3 subscription reward rate</label>
            <input type="number" step="0.0001" min="0" max="1" name="team_level_3_subscription_reward_rate" class="form-control @error('team_level_3_subscription_reward_rate') is-invalid @enderror" value="{{ old('team_level_3_subscription_reward_rate', $settings['team_level_3_subscription_reward_rate'] ?? 0.005) }}" required>
            @error('team_level_3_subscription_reward_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-4">
            <label class="form-label">Level 4 subscription reward rate</label>
            <input type="number" step="0.0001" min="0" max="1" name="team_level_4_subscription_reward_rate" class="form-control @error('team_level_4_subscription_reward_rate') is-invalid @enderror" value="{{ old('team_level_4_subscription_reward_rate', $settings['team_level_4_subscription_reward_rate'] ?? 0.0025) }}" required>
            @error('team_level_4_subscription_reward_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-4">
            <label class="form-label">Level 5 subscription reward rate</label>
            <input type="number" step="0.0001" min="0" max="1" name="team_level_5_subscription_reward_rate" class="form-control @error('team_level_5_subscription_reward_rate') is-invalid @enderror" value="{{ old('team_level_5_subscription_reward_rate', $settings['team_level_5_subscription_reward_rate'] ?? 0.001) }}" required>
            @error('team_level_5_subscription_reward_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12">
    <button type="submit" class="btn btn-primary">Save reward settings</button>
  </div>
</form>
@endsection

