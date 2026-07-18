@extends('layout.master')

@section('content')
@php
  $templateGroups = [
    'Payout Flow' => [
      'accent' => 'success',
      'description' => 'Messages used when payout requests are submitted, approved, and paid.',
      'items' => [
        'payout_submitted' => ['label' => 'Payout Submitted', 'hint' => 'Available placeholders: :gross_amount, :fee_amount, :net_amount, :method_label, :destination'],
        'payout_approved' => ['label' => 'Payout Approved', 'hint' => 'Available placeholders: :gross_amount, :fee_amount, :net_amount, :method_label, :destination'],
        'payout_paid' => ['label' => 'Payout Paid', 'hint' => 'Available placeholders: :gross_amount, :fee_amount, :net_amount, :method_label, :destination'],
      ],
    ],
    'Network & Unlocks' => [
      'accent' => 'primary',
      'description' => 'Milestones and referral-linked updates sent as users join, verify, and unlock access.',
      'items' => [
        'free_starter' => ['label' => 'Free Starter Activated', 'hint' => 'No placeholders required.'],
        'network_join' => ['label' => 'Referred User Joined', 'hint' => 'Available placeholders: :user_name, :user_email'],
        'reward_registration' => ['label' => 'Registration Reward', 'hint' => 'Available placeholders: :user_name, :user_email'],
        'network_sponsor' => ['label' => 'Sponsor Link Confirmed', 'hint' => 'Available placeholders: :sponsor_name, :sponsor_email'],
        'basic_unlocked' => ['label' => 'Basic 100 Unlocked', 'hint' => 'Available placeholders: :package_name'],
      ],
    ],
    'Investment & Team Rewards' => [
      'accent' => 'warning',
      'description' => 'Messages for investment activation and direct or deeper team-level reward events.',
      'items' => [
        'investment_activated' => ['label' => 'Investment Activated', 'hint' => 'Available placeholders: :package_name'],
        'team_level_1' => ['label' => 'Level 1 Team Reward', 'hint' => 'Available placeholders: :user_name, :user_email, :package_name, :level'],
        'team_level_2' => ['label' => 'Level 2 Team Reward', 'hint' => 'Available placeholders: :user_name, :user_email, :package_name, :level'],
        'team_level_generic' => ['label' => 'Generic Deeper Level Reward', 'hint' => 'Available placeholders: :user_name, :user_email, :package_name, :level'],
      ],
    ],
  ];

  $allPreviewItems = collect($templateGroups)
    ->flatMap(fn ($group) => $group['items'])
    ->mapWithKeys(fn ($meta, $key) => [$key => 'Preview '.$meta['label']]);
@endphp

<style>
  .notification-templates-shell {
    display: grid;
    gap: 1.5rem;
  }

  .notification-templates-hero {
    position: relative;
    overflow: hidden;
    border-radius: 1.5rem;
    padding: 1.6rem 1.7rem;
    background:
      radial-gradient(circle at top right, rgba(255,255,255,0.18), transparent 28%),
      linear-gradient(135deg, #081122 0%, #10264a 58%, #2563eb 100%);
    color: #ffffff;
    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.16);
  }

  .notification-templates-hero__eyebrow {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    margin-bottom: .95rem;
    padding: .42rem .85rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 999px;
    color: #dbe8ff;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
  }

  .notification-templates-hero h4 {
    margin-bottom: .55rem;
    font-size: 2rem;
    color: #ffffff;
  }

  .notification-templates-hero p {
    max-width: 820px;
    margin-bottom: 0;
    color: #d7e6ff;
    line-height: 1.75;
  }

  .notification-templates-actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
  }

  .notification-templates-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
  }

  .notification-summary-card {
    border: 1px solid #dbe4f0;
    border-radius: 1.25rem;
    background: #ffffff;
    padding: 1.2rem 1.25rem;
    box-shadow: 0 14px 35px rgba(15, 23, 42, 0.05);
  }

  .notification-summary-card__label {
    display: inline-flex;
    align-items: center;
    margin-bottom: .65rem;
    padding: .35rem .7rem;
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
  }

  .notification-summary-card__label--success {
    background: #edf9f1;
    color: #177245;
  }

  .notification-summary-card__label--primary {
    background: #eef5ff;
    color: #2557b8;
  }

  .notification-summary-card__label--warning {
    background: #fff7e8;
    color: #9a6216;
  }

  .notification-summary-card h6 {
    margin-bottom: .45rem;
    font-size: 1rem;
  }

  .notification-summary-card p {
    margin-bottom: 0;
    color: #66758c;
    line-height: 1.7;
  }

  .notification-preview-card,
  .template-group-card {
    border: 1px solid #dbe4f0;
    border-radius: 1.35rem;
    background: #ffffff;
    box-shadow: 0 16px 38px rgba(15, 23, 42, 0.06);
  }

  .notification-preview-card__body,
  .template-group-card__body {
    padding: 1.35rem;
  }

  .notification-preview-card__header,
  .template-group-card__header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1rem;
  }

  .notification-preview-card__title,
  .template-group-card__title {
    margin-bottom: .25rem;
  }

  .notification-preview-card__text,
  .template-group-card__text {
    margin-bottom: 0;
    color: #66758c;
    line-height: 1.7;
  }

  .notification-preview-form {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    align-items: center;
  }

  .notification-preview-form .form-select {
    min-width: 280px;
    border-radius: .9rem;
  }

  .template-group-list {
    display: grid;
    gap: 1rem;
  }

  .template-editor-card {
    border: 1px solid #e3ebf5;
    border-radius: 1.1rem;
    background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
    padding: 1.1rem;
  }

  .template-editor-card__title {
    margin-bottom: .35rem;
    font-size: 1rem;
    font-weight: 700;
    color: #162132;
  }

  .template-editor-card__hint {
    margin-bottom: .95rem;
    color: #6b7a90;
    font-size: .9rem;
    line-height: 1.7;
  }

  .template-editor-card .form-label {
    font-weight: 700;
    color: #20304b;
  }

  .template-editor-card .form-control {
    border-radius: .9rem;
    border-color: #d5dfeb;
    box-shadow: none;
  }

  .template-editor-card .form-control:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 .18rem rgba(37, 99, 235, 0.12);
  }

  .notification-template-savebar {
    position: sticky;
    bottom: 0;
    z-index: 5;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
    margin-top: 1.25rem;
    padding: 1rem 1.2rem;
    border: 1px solid #dbe4f0;
    border-radius: 1.15rem;
    background: rgba(255, 255, 255, 0.96);
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
    backdrop-filter: blur(8px);
  }

  .notification-template-savebar p {
    margin-bottom: 0;
    color: #66758c;
  }

  @media (max-width: 991px) {
    .notification-templates-summary {
      grid-template-columns: 1fr;
    }
  }
</style>

<div class="notification-templates-shell">
  <div class="notification-templates-hero">
    <div class="notification-templates-hero__eyebrow">
      <i data-lucide="message-square-text"></i>
      Message Control
    </div>
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
      <div>
        <h4>Notification Templates</h4>
        <p>Edit the actual wording used by payout, reward, investment, network, and milestone notifications so the product tone stays clear and consistent.</p>
      </div>
      <div class="notification-templates-actions">
        <a href="{{ route('dashboard.notification-rules') }}" class="btn btn-light btn-icon-text">
          <i data-lucide="bell-dot" class="btn-icon-prepend"></i> Notification rules
        </a>
        <a href="{{ route('dashboard.notifications') }}" class="btn btn-outline-light btn-icon-text">
          <i data-lucide="bell" class="btn-icon-prepend"></i> Notification feed
        </a>
      </div>
    </div>
  </div>

  @if (session('notification_templates_success'))
    <div class="alert alert-success mb-0">{{ session('notification_templates_success') }}</div>
  @endif

  <div class="notification-templates-summary">
    @foreach ($templateGroups as $groupLabel => $group)
      <div class="notification-summary-card">
        <div class="notification-summary-card__label notification-summary-card__label--{{ $group['accent'] }}">
          {{ $groupLabel }}
        </div>
        <h6>{{ count($group['items']) }} editable templates</h6>
        <p>{{ $group['description'] }}</p>
      </div>
    @endforeach
  </div>

  <div class="notification-preview-card">
    <div class="notification-preview-card__body">
      <div class="notification-preview-card__header">
        <div>
          <h5 class="notification-preview-card__title">Send Preview</h5>
          <p class="notification-preview-card__text">Send a sample notification to your own in-app feed before saving wording changes live.</p>
        </div>
        <form method="POST" action="{{ route('dashboard.notification-templates.preview') }}" class="notification-preview-form">
          @csrf
          <select name="template_key" class="form-select">
            @foreach ($allPreviewItems as $previewKey => $previewLabel)
              <option value="{{ $previewKey }}">{{ $previewLabel }}</option>
            @endforeach
          </select>
          <button type="submit" class="btn btn-outline-primary btn-icon-text">
            <i data-lucide="send" class="btn-icon-prepend"></i> Send preview
          </button>
        </form>
      </div>
    </div>
  </div>

  <form method="POST" action="{{ route('dashboard.notification-templates.update') }}">
    @csrf

    <div class="template-group-list">
      @foreach ($templateGroups as $groupLabel => $group)
        <div class="template-group-card">
          <div class="template-group-card__body">
            <div class="template-group-card__header">
              <div>
                <h5 class="template-group-card__title">{{ $groupLabel }}</h5>
                <p class="template-group-card__text">{{ $group['description'] }}</p>
              </div>
              <span class="badge bg-light text-dark">{{ count($group['items']) }} templates</span>
            </div>

            <div class="template-group-list">
              @foreach ($group['items'] as $key => $meta)
                <div class="template-editor-card">
                  <div class="template-editor-card__title">{{ $meta['label'] }}</div>
                  <div class="template-editor-card__hint">{{ $meta['hint'] }}</div>

                  <div class="row g-3">
                    <div class="col-md-4">
                      <label class="form-label">Subject</label>
                      <input
                        type="text"
                        name="template_{{ $key }}_subject"
                        class="form-control @error('template_'.$key.'_subject') is-invalid @enderror"
                        value="{{ old('template_'.$key.'_subject', $settings['template_'.$key.'_subject']) }}"
                        required
                      >
                      @error('template_'.$key.'_subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-8">
                      <label class="form-label">Message</label>
                      <textarea
                        name="template_{{ $key }}_message"
                        rows="4"
                        class="form-control @error('template_'.$key.'_message') is-invalid @enderror"
                        required
                      >{{ old('template_'.$key.'_message', $settings['template_'.$key.'_message']) }}</textarea>
                      @error('template_'.$key.'_message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        </div>
      @endforeach
    </div>

    <div class="notification-template-savebar">
      <p>Review the wording carefully before saving. Previews help you test tone in your own feed first.</p>
      <button type="submit" class="btn btn-primary btn-icon-text">
        <i data-lucide="save" class="btn-icon-prepend"></i> Save notification templates
      </button>
    </div>
  </form>
</div>
@endsection
