@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h4 class="mb-1">Notification Templates</h4>
        <p class="text-secondary mb-0">Edit the actual wording used by payout, reward, investment, network, and milestone notifications.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('dashboard.notification-rules') }}" class="btn btn-outline-primary btn-icon-text">
          <i data-lucide="bell-dot" class="btn-icon-prepend"></i> Notification rules
        </a>
        <a href="{{ route('dashboard.notifications') }}" class="btn btn-outline-secondary btn-icon-text">
          <i data-lucide="bell" class="btn-icon-prepend"></i> Notification feed
        </a>
      </div>
    </div>
  </div>
</div>

@if (session('notification_templates_success'))
  <div class="alert alert-success">{{ session('notification_templates_success') }}</div>
@endif

<div class="row mb-4">
  <div class="col-12 stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div>
            <h5 class="mb-1">Send Preview</h5>
            <p class="text-secondary mb-0">Send a sample notification to your own in-app feed before saving wording changes live.</p>
          </div>
          <form method="POST" action="{{ route('dashboard.notification-templates.preview') }}" class="d-flex flex-wrap gap-2 align-items-center">
            @csrf
            <select name="template_key" class="form-select" style="min-width: 260px;">
              <option value="payout_submitted">Preview payout submitted</option>
              <option value="payout_approved">Preview payout approved</option>
              <option value="payout_paid">Preview payout paid</option>
              <option value="free_starter">Preview free starter</option>
              <option value="network_join">Preview network join</option>
              <option value="reward_registration">Preview registration reward</option>
              <option value="network_sponsor">Preview sponsor link</option>
              <option value="basic_unlocked">Preview basic unlocked</option>
              <option value="investment_activated">Preview investment activated</option>
              <option value="team_level_1">Preview level 1 reward</option>
              <option value="team_level_2">Preview level 2 reward</option>
              <option value="team_level_generic">Preview deeper level reward</option>
            </select>
            <button type="submit" class="btn btn-outline-primary btn-icon-text">
              <i data-lucide="send" class="btn-icon-prepend"></i> Send preview
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<form method="POST" action="{{ route('dashboard.notification-templates.update') }}">
  @csrf
  <div class="row g-4">
    @foreach ([
      'payout_submitted' => ['label' => 'Payout Submitted', 'hint' => 'Available placeholders: :gross_amount, :fee_amount, :net_amount, :method_label, :destination'],
      'payout_approved' => ['label' => 'Payout Approved', 'hint' => 'Available placeholders: :gross_amount, :fee_amount, :net_amount, :method_label, :destination'],
      'payout_paid' => ['label' => 'Payout Paid', 'hint' => 'Available placeholders: :gross_amount, :fee_amount, :net_amount, :method_label, :destination'],
      'free_starter' => ['label' => 'Free Starter Activated', 'hint' => 'No placeholders required.'],
      'network_join' => ['label' => 'Referred User Joined', 'hint' => 'Available placeholders: :user_name, :user_email'],
      'reward_registration' => ['label' => 'Registration Reward', 'hint' => 'Available placeholders: :user_name, :user_email'],
      'network_sponsor' => ['label' => 'Sponsor Link Confirmed', 'hint' => 'Available placeholders: :sponsor_name, :sponsor_email'],
      'basic_unlocked' => ['label' => 'Basic 100 Unlocked', 'hint' => 'Available placeholders: :package_name'],
      'investment_activated' => ['label' => 'Investment Activated', 'hint' => 'Available placeholders: :package_name'],
      'team_level_1' => ['label' => 'Level 1 Team Reward', 'hint' => 'Available placeholders: :user_name, :user_email, :package_name, :level'],
      'team_level_2' => ['label' => 'Level 2 Team Reward', 'hint' => 'Available placeholders: :user_name, :user_email, :package_name, :level'],
      'team_level_generic' => ['label' => 'Generic Deeper Level Reward', 'hint' => 'Available placeholders: :user_name, :user_email, :package_name, :level'],
    ] as $key => $meta)
      <div class="col-12">
        <div class="card">
          <div class="card-body">
            <h5 class="mb-1">{{ $meta['label'] }}</h5>
            <p class="text-secondary mb-4">{{ $meta['hint'] }}</p>
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Subject</label>
                <input type="text" name="template_{{ $key }}_subject" class="form-control @error('template_'.$key.'_subject') is-invalid @enderror" value="{{ old('template_'.$key.'_subject', $settings['template_'.$key.'_subject']) }}" required>
                @error('template_'.$key.'_subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="col-md-8">
                <label class="form-label">Message</label>
                <textarea name="template_{{ $key }}_message" rows="3" class="form-control @error('template_'.$key.'_message') is-invalid @enderror" required>{{ old('template_'.$key.'_message', $settings['template_'.$key.'_message']) }}</textarea>
                @error('template_'.$key.'_message')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>
        </div>
      </div>
    @endforeach
  </div>

  <div class="d-flex justify-content-end mt-4">
    <button type="submit" class="btn btn-primary btn-icon-text">
      <i data-lucide="save" class="btn-icon-prepend"></i> Save notification templates
    </button>
  </div>
</form>
@endsection