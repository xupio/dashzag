@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h4 class="mb-1">Notification Rules</h4>
        <p class="text-secondary mb-0">Set the platform-wide default delivery channels that new users inherit before applying their personal overrides.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('dashboard.settings') }}" class="btn btn-outline-primary btn-icon-text">
          <i data-lucide="sliders-horizontal" class="btn-icon-prepend"></i> Platform settings
        </a>
        <a href="{{ route('dashboard.notification-preferences') }}" class="btn btn-outline-secondary btn-icon-text">
          <i data-lucide="bell-ring" class="btn-icon-prepend"></i> User preferences
        </a>
      </div>
    </div>
  </div>
</div>

@if (session('notification_rules_success'))
  <div class="alert alert-success">{{ session('notification_rules_success') }}</div>
@endif

<form method="POST" action="{{ route('dashboard.notification-rules.update') }}">
  @csrf
  <div class="row">
    <div class="col-12 stretch-card">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
              <h5 class="mb-1">Default channels by category</h5>
              <p class="text-secondary mb-0">These defaults apply to new users and any category a user has not overridden yet.</p>
            </div>
            <span class="badge bg-primary">Admin defaults</span>
          </div>

          @php
            $rows = [
              'payout' => 'Payout requests, approvals, and paid confirmations.',
              'reward' => 'Referral rewards and team bonus updates.',
              'investment' => 'Package activations and investment events.',
              'network' => 'Sponsor links, downline growth, and team activity.',
              'milestone' => 'Unlocked packages and important achievements.',
            ];
          @endphp

          <div class="table-responsive">
            <table class="table align-middle">
              <thead>
                <tr>
                  <th>Category</th>
                  <th>Description</th>
                  <th class="text-center">Default in-app</th>
                  <th class="text-center">Default email</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($rows as $category => $description)
                  <tr>
                    <td><div class="fw-semibold">{{ str($category)->title() }}</div></td>
                    <td class="text-secondary">{{ $description }}</td>
                    <td class="text-center">
                      <div class="form-check d-inline-flex justify-content-center mb-0">
                        <input type="hidden" name="notification_{{ $category }}_in_app" value="0">
                        <input class="form-check-input" type="checkbox" name="notification_{{ $category }}_in_app" value="1" @checked(old('notification_'.$category.'_in_app', $settings['notification_'.$category.'_in_app']) == '1')>
                      </div>
                    </td>
                    <td class="text-center">
                      <div class="form-check d-inline-flex justify-content-center mb-0">
                        <input type="hidden" name="notification_{{ $category }}_email" value="0">
                        <input class="form-check-input" type="checkbox" name="notification_{{ $category }}_email" value="1" @checked(old('notification_'.$category.'_email', $settings['notification_'.$category.'_email']) == '1')>
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <div class="alert alert-info mt-4 mb-0">
            Existing users can still override these defaults from their own notification preferences page.
          </div>

          <div class="d-flex justify-content-end mt-4">
            <button type="submit" class="btn btn-primary btn-icon-text">
              <i data-lucide="save" class="btn-icon-prepend"></i> Save notification rules
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>
@endsection