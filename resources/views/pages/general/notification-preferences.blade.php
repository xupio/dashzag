@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h4 class="mb-1">Notification Preferences</h4>
        <p class="text-secondary mb-0">Choose which updates stay inside the dashboard and which ones should also reach your email inbox.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('dashboard.notifications') }}" class="btn btn-outline-primary btn-icon-text">
          <i data-lucide="bell" class="btn-icon-prepend"></i> Notification feed
        </a>
        <a href="{{ route('dashboard.profile') }}" class="btn btn-outline-secondary btn-icon-text">
          <i data-lucide="user-round" class="btn-icon-prepend"></i> Profile
        </a>
      </div>
    </div>
  </div>
</div>

@if (session('preferences_success'))
  <div class="alert alert-success">{{ session('preferences_success') }}</div>
@endif

<div class="row mb-4">
  <div class="col-lg-3 stretch-card">
    <div class="card w-100">
      <div class="card-body">
        <p class="text-secondary mb-1">Digest frequency</p>
        <h4 class="mb-2">{{ ucfirst($notificationPreferences['digest']['frequency'] ?? 'weekly') }}</h4>
        <p class="text-secondary mb-0">Your digest currently summarizes {{ $digestSummary['period_label'] }}.</p>
      </div>
    </div>
  </div>
  <div class="col-lg-3 stretch-card">
    <div class="card w-100">
      <div class="card-body">
        <p class="text-secondary mb-1">Digest total</p>
        <h4 class="mb-2">{{ $digestSummary['total'] }}</h4>
        <p class="text-secondary mb-0">Tracked updates: payouts, rewards, investments, network, and milestones.</p>
      </div>
    </div>
  </div>
  <div class="col-lg-3 stretch-card">
    <div class="card w-100">
      <div class="card-body">
        <p class="text-secondary mb-1">Last daily digest</p>
        <h6 class="mb-2">{{ $user->last_daily_digest_sent_at?->format('M d, Y h:i A') ?? 'Not sent yet' }}</h6>
        <p class="text-secondary mb-0">Updated automatically when the daily scheduler runs.</p>
      </div>
    </div>
  </div>
  <div class="col-lg-3 stretch-card">
    <div class="card w-100">
      <div class="card-body">
        <p class="text-secondary mb-1">Last weekly digest</p>
        <h6 class="mb-2">{{ $user->last_weekly_digest_sent_at?->format('M d, Y h:i A') ?? 'Not sent yet' }}</h6>
        <p class="text-secondary mb-0">Updated automatically when the weekly scheduler runs.</p>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-lg-4 stretch-card">
    <div class="card w-100">
      <div class="card-body">
        <p class="text-secondary mb-1">Generate now</p>
        <form method="POST" action="{{ route('dashboard.notification-preferences.generate-digest') }}">
          @csrf
          <button type="submit" class="btn btn-outline-primary btn-sm">Send digest summary</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-8 stretch-card">
    <div class="card w-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
          <div>
            <h5 class="mb-1">Recent digest history</h5>
            <p class="text-secondary mb-0">Your latest generated digest notifications appear here.</p>
          </div>
          <span class="badge bg-info">{{ $recentDigests->count() }} entries</span>
        </div>
        @if ($recentDigests->isEmpty())
          <p class="text-secondary mb-0">No digest summaries have been generated yet.</p>
        @else
          <div class="table-responsive">
            <table class="table align-middle mb-0">
              <thead>
                <tr>
                  <th>Subject</th>
                  <th>Period</th>
                  <th>Frequency</th>
                  <th>Total updates</th>
                  <th>Created</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($recentDigests as $digest)
                  <tr>
                    <td>{{ $digest->data['subject'] ?? 'Digest summary' }}</td>
                    <td>{{ $digest->data['context_value'] ?? '—' }}</td>
                    <td>{{ ucfirst($digest->data['digest_frequency'] ?? 'weekly') }}</td>
                    <td>{{ $digest->data['amount'] ?? 0 }}</td>
                    <td>{{ $digest->created_at?->format('M d, Y h:i A') }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12 stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="mb-4">
          <h5 class="mb-1">Delivery channels</h5>
          <p class="text-secondary mb-0">In-app keeps updates in the dashboard bell and feed. Email sends a copy to {{ $user->email }}.</p>
        </div>

        <form method="POST" action="{{ route('dashboard.notification-preferences.update') }}">
          @csrf
          <div class="table-responsive">
            <table class="table align-middle">
              <thead>
                <tr>
                  <th>Category</th>
                  <th>Description</th>
                  <th class="text-center">In-app</th>
                  <th class="text-center">Email</th>
                </tr>
              </thead>
              <tbody>
                @php
                  $rows = [
                    'payout' => 'Payout requests, approvals, and paid confirmations.',
                    'reward' => 'Referral rewards and team bonus updates.',
                    'investment' => 'Package activations and investment events.',
                    'network' => 'Sponsor links, team joins, and network activity.',
                    'milestone' => 'Unlocks and important account achievements.',
                    'digest' => 'Daily or weekly summary reports based on your selected digest frequency.',
                  ];
                @endphp
                @foreach ($rows as $category => $description)
                  <tr>
                    <td>
                      <div class="fw-semibold">{{ str($category)->replace('_', ' ')->title() }}</div>
                      @if ($category === 'digest')
                        <div class="mt-2" style="max-width: 180px;">
                          <select name="digest_frequency" class="form-select form-select-sm">
                            <option value="daily" {{ ($notificationPreferences['digest']['frequency'] ?? 'weekly') === 'daily' ? 'selected' : '' }}>Daily</option>
                            <option value="weekly" {{ ($notificationPreferences['digest']['frequency'] ?? 'weekly') === 'weekly' ? 'selected' : '' }}>Weekly</option>
                          </select>
                        </div>
                      @endif
                    </td>
                    <td class="text-secondary">{{ $description }}</td>
                    <td class="text-center">
                      <div class="form-check d-inline-flex justify-content-center mb-0">
                        <input type="hidden" name="{{ $category }}_in_app" value="0">
                        <input class="form-check-input" type="checkbox" name="{{ $category }}_in_app" value="1" {{ ($notificationPreferences[$category]['in_app'] ?? false) ? 'checked' : '' }}>
                      </div>
                    </td>
                    <td class="text-center">
                      <div class="form-check d-inline-flex justify-content-center mb-0">
                        <input type="hidden" name="{{ $category }}_email" value="0">
                        <input class="form-check-input" type="checkbox" name="{{ $category }}_email" value="1" {{ ($notificationPreferences[$category]['email'] ?? false) ? 'checked' : '' }}>
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <div class="d-flex justify-content-end mt-4 gap-2">
            <a href="{{ route('dashboard.notifications') }}" class="btn btn-light">Cancel</a>
            <button type="submit" class="btn btn-primary btn-icon-text">
              <i data-lucide="save" class="btn-icon-prepend"></i> Save preferences
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection