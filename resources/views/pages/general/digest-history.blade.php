@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h4 class="mb-1">Digest History</h4>
        <p class="text-secondary mb-0">Review the latest digest deliveries across verified users, including whether they came from a manual send, bulk action, self-send, or scheduled run.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('dashboard.digests') }}" class="btn btn-outline-primary btn-icon-text">
          <i data-lucide="mail-search" class="btn-icon-prepend"></i> Digest monitoring
        </a>
        <a href="{{ route('dashboard.analytics') }}" class="btn btn-outline-secondary btn-icon-text">
          <i data-lucide="bar-chart-3" class="btn-icon-prepend"></i> Analytics
        </a>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-lg-3 stretch-card">
    <div class="card w-100">
      <div class="card-body">
        <p class="text-secondary mb-1">Visible entries</p>
        <h4 class="mb-2">{{ $history->count() }}</h4>
        <p class="text-secondary mb-0">Digest deliveries in the current filtered view.</p>
      </div>
    </div>
  </div>
  <div class="col-lg-3 stretch-card">
    <div class="card w-100">
      <div class="card-body">
        <p class="text-secondary mb-1">Total updates delivered</p>
        <h4 class="mb-2">{{ $totalUpdatesDelivered }}</h4>
        <p class="text-secondary mb-0">Sum of all digest update counts in the visible dataset.</p>
      </div>
    </div>
  </div>
  <div class="col-lg-3 stretch-card">
    <div class="card w-100">
      <div class="card-body">
        <p class="text-secondary mb-1">Daily vs weekly</p>
        <h6 class="mb-2">{{ $frequencyBreakdown['daily'] ?? 0 }} daily / {{ $frequencyBreakdown['weekly'] ?? 0 }} weekly</h6>
        <p class="text-secondary mb-0">Quick frequency split for the currently visible digest history.</p>
      </div>
    </div>
  </div>
  <div class="col-lg-3 stretch-card">
    <div class="card w-100">
      <div class="card-body">
        <p class="text-secondary mb-1">Top source</p>
        @php
          $topSourceKey = collect($sourceBreakdown)->sortDesc()->keys()->first();
          $topSourceLabel = match ($topSourceKey) {
            'admin_manual' => 'Admin manual',
            'admin_bulk' => 'Admin bulk',
            'user_manual' => 'User manual',
            'scheduled' => 'Scheduled',
            default => 'No source',
          };
        @endphp
        <h6 class="mb-2">{{ $topSourceLabel }}</h6>
        <p class="text-secondary mb-0">Highest-volume source inside the active filter set.</p>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-lg-6 stretch-card">
    <div class="card w-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h5 class="mb-1">Source breakdown</h5>
            <p class="text-secondary mb-0">See which delivery path is generating the most digest traffic.</p>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead>
              <tr>
                <th>Source</th>
                <th>Count</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($sourceOptions as $value => $label)
                @continue($value === 'all')
                <tr>
                  <td>{{ $label }}</td>
                  <td>{{ $sourceBreakdown[$value] ?? 0 }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-6 stretch-card">
    <div class="card w-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h5 class="mb-1">Top recipients</h5>
            <p class="text-secondary mb-0">Users receiving the most digest deliveries in this filtered history.</p>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead>
              <tr>
                <th>User</th>
                <th>Deliveries</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($topRecipients as $recipient)
                <tr>
                  <td>
                    <div class="fw-semibold">{{ $recipient['name'] }}</div>
                    <div class="text-secondary small">{{ $recipient['email'] }}</div>
                  </td>
                  <td>{{ $recipient['count'] }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="2" class="text-center text-secondary py-4">No digest recipients available for this filter.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12 stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
          <div>
            <h5 class="mb-1">Latest digest deliveries</h5>
            <p class="text-secondary mb-0">This feed keeps the most recent 100 digest notifications so operations can audit delivery activity quickly.</p>
          </div>
          <div class="d-flex align-items-center flex-wrap gap-2">
            <span class="badge bg-info">{{ $history->count() }} entries</span>
            <a href="{{ route('dashboard.digests.history.export', ['source' => $activeSource, 'frequency' => $activeFrequency]) }}" class="btn btn-outline-secondary btn-sm">Export CSV</a>
          </div>
        </div>

        <form method="GET" action="{{ route('dashboard.digests.history') }}" class="row g-3 mb-4">
          <div class="col-md-4">
            <label class="form-label">Source</label>
            <select name="source" class="form-select">
              @foreach ($sourceOptions as $value => $label)
                <option value="{{ $value }}" {{ $activeSource === $value ? 'selected' : '' }}>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Frequency</label>
            <select name="frequency" class="form-select">
              @foreach ($frequencyOptions as $value => $label)
                <option value="{{ $value }}" {{ $activeFrequency === $value ? 'selected' : '' }}>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4 d-flex align-items-end gap-2">
            <button type="submit" class="btn btn-primary">Apply filters</button>
            <a href="{{ route('dashboard.digests.history') }}" class="btn btn-light">Reset</a>
          </div>
        </form>

        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead>
              <tr>
                <th>Recipient</th>
                <th>Frequency</th>
                <th>Source</th>
                <th>Triggered by</th>
                <th>Period</th>
                <th>Total updates</th>
                <th>Created</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($history as $entry)
                @php
                  $source = $entry['data']['digest_source'] ?? 'system';
                  $sourceLabel = match ($source) {
                    'admin_manual' => 'Admin manual',
                    'admin_bulk' => 'Admin bulk',
                    'user_manual' => 'User manual',
                    'scheduled' => 'Scheduled',
                    default => str($source)->replace('_', ' ')->title()->toString(),
                  };
                @endphp
                <tr>
                  <td>
                    <div class="fw-semibold">{{ $entry['user']->name }}</div>
                    <div class="text-secondary small">{{ $entry['user']->email }}</div>
                  </td>
                  <td>{{ ucfirst($entry['data']['digest_frequency'] ?? 'weekly') }}</td>
                  <td><span class="badge bg-secondary">{{ $sourceLabel }}</span></td>
                  <td>{{ $entry['data']['triggered_by_name'] ?? 'System' }}</td>
                  <td>{{ $entry['data']['context_value'] ?? '—' }}</td>
                  <td>{{ $entry['data']['amount'] ?? 0 }}</td>
                  <td>{{ $entry['notification']->created_at?->format('M d, Y h:i A') ?? '—' }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center text-secondary py-4">No digest history is available yet.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
