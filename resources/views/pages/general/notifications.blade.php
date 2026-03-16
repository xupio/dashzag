@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h4 class="mb-1">Notifications</h4>
        <p class="text-secondary mb-0">Track payout status changes and other important updates without leaving the dashboard.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        @if ($unreadCount > 0)
          <form method="POST" action="{{ route('dashboard.notifications.read-all') }}">
            @csrf
            <button type="submit" class="btn btn-primary btn-icon-text">
              <i data-lucide="mail-check" class="btn-icon-prepend"></i> Mark all as read
            </button>
          </form>
        @endif
        <a href="{{ route('dashboard') }}" class="btn btn-outline-primary btn-icon-text">
          <i data-lucide="layout-dashboard" class="btn-icon-prepend"></i> Dashboard
        </a>
      </div>
    </div>
  </div>
</div>

@if (session('notifications_success'))
  <div class="alert alert-success">{{ session('notifications_success') }}</div>
@endif

<div class="row mb-4">
  <div class="col-md-4 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <p class="text-secondary mb-1">Total notifications</p>
        <h4 class="mb-0">{{ $allNotificationsCount }}</h4>
      </div>
    </div>
  </div>
  <div class="col-md-4 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <p class="text-secondary mb-1">Unread</p>
        <h4 class="mb-0">{{ $unreadCount }}</h4>
      </div>
    </div>
  </div>
  <div class="col-md-4 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <p class="text-secondary mb-1">Latest update</p>
        <h6 class="mb-0">{{ $notifications->first()?->created_at?->format('M d, Y h:i A') ?? 'No updates yet' }}</h6>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  @foreach ($notificationBreakdown as $breakdownKey => $breakdown)
    <div class="col-md-6 col-xl-2 grid-margin stretch-card">
      <a href="{{ route('dashboard.notifications', ['filter' => $breakdownKey]) }}" class="card text-decoration-none {{ $activeFilter === $breakdownKey ? 'border-primary shadow-sm' : '' }}">
        <div class="card-body">
          <div class="text-secondary small">{{ $breakdown['label'] }}</div>
          <div class="fw-semibold fs-4 text-dark">{{ $breakdown['count'] }}</div>
          <div class="small {{ $breakdown['unread'] > 0 ? 'text-danger' : 'text-secondary' }}">{{ $breakdown['unread'] }} unread</div>
        </div>
      </a>
    </div>
  @endforeach
</div>

<div class="row mb-4">
  <div class="col-12">
    <div class="card">
      <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex flex-wrap gap-2">
          @foreach ($notificationFilters as $filterKey => $filterLabel)
            <a href="{{ route('dashboard.notifications', ['filter' => $filterKey]) }}"
               class="btn {{ $activeFilter === $filterKey ? 'btn-primary' : 'btn-outline-secondary' }} btn-sm">
              {{ $filterLabel }}
              @if ($filterKey !== 'all')
                <span class="ms-1 badge {{ $activeFilter === $filterKey ? 'bg-light text-primary' : 'bg-secondary' }}">{{ $notificationBreakdown[$filterKey]['count'] ?? 0 }}</span>
              @endif
            </a>
          @endforeach
        </div>
        <div class="d-flex flex-wrap gap-2">
          <form method="POST" action="{{ route('dashboard.notifications.clear-read') }}">
            @csrf
            <button type="submit" class="btn btn-outline-secondary btn-sm">Clear read</button>
          </form>
          <form method="POST" action="{{ route('dashboard.notifications.clear-previews') }}">
            @csrf
            <button type="submit" class="btn btn-outline-secondary btn-sm">Clear previews</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

@if (auth()->user()?->isAdmin())
  <div class="row mb-4">
    <div class="col-12">
      <div class="card border-warning">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
              <h5 class="mb-1">Admin Cleanup</h5>
              <p class="text-secondary mb-0">Prune old notifications across the whole system by category and age.</p>
            </div>
            <span class="badge bg-warning text-dark">Admin only</span>
          </div>
          <form method="POST" action="{{ route('dashboard.notifications.prune') }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-4">
              <label class="form-label">Category</label>
              <select name="filter" class="form-select">
                @foreach ($notificationFilters as $filterKey => $filterLabel)
                  <option value="{{ $filterKey }}">{{ $filterLabel }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Older than days</label>
              <input type="number" min="0" name="older_than_days" value="30" class="form-control" required>
            </div>
            <div class="col-md-4">
              <button type="submit" class="btn btn-warning">Prune notifications</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endif

<div class="row">
  <div class="col-12 stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h5 class="mb-1">Activity feed</h5>
            <p class="text-secondary mb-0">Unread items stay highlighted until you mark them as read.</p>
          </div>
          <span class="badge bg-info">{{ $unreadCount }} unread</span>
        </div>
        @if ($notifications->isEmpty())
          <div class="text-center py-5">
            <h5 class="mb-2">No notifications in this filter</h5>
            <p class="text-secondary mb-0">Try another category to see more account activity.</p>
          </div>
        @else
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>Status</th>
                  <th>Subject</th>
                  <th>Details</th>
                  <th>Amounts</th>
                  <th>Received</th>
                  <th>Read state</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($notifications as $notification)
                  @php($data = $notification->data)
                  @php($status = $data['status'] ?? 'info')
                  @php($category = $data['category'] ?? 'activity')
                  @php($eventKey = $data['event_key'] ?? null)
                  @php($isInvestmentOrderEvent = $category === 'investment' && in_array($data['subject'] ?? '', ['Payment proof uploaded', 'Investment payment rejected', 'Investment approved without proof override'], true))
                  @php($isHallOfFameWin = in_array($eventKey, ['hall_of_fame_weekly_winner', 'hall_of_fame_monthly_winner'], true))
                  @php($subjectLabel = $isHallOfFameWin ? 'Champion Win' : ($isInvestmentOrderEvent ? 'Investment Order Update' : ($data['subject'] ?? 'Notification')))
                  @php($detailLabel = $isHallOfFameWin ? 'Hall of Fame' : ($isInvestmentOrderEvent ? 'Investment review' : ($data['context_label'] ?? ($data['method_label'] ?? 'System update'))))
                  @php($statusClass = match ($status) {
                    'paid', 'approved' => 'bg-primary',
                    'success', 'active' => 'bg-success',
                    'reward' => 'bg-info',
                    default => 'bg-warning text-dark',
                  })
                  <tr class="{{ $notification->read_at ? '' : 'table-light' }}">
                    <td>
                      <span class="badge {{ $statusClass }}">
                        {{ str($status)->replace('_', ' ')->title() }}
                      </span>
                    </td>
                    <td>
                      <div class="fw-semibold">{{ $subjectLabel }}</div>
                      <div class="text-secondary small">{{ $data['message'] ?? 'There is a new update on your account.' }}</div>
                    </td>
                    <td>
                      <div>{{ $detailLabel }}</div>
                      <div class="text-secondary small">{{ $data['context_value'] ?? ($data['destination'] ?? '—') }}</div>
                      <div class="text-secondary small mt-1">{{ $data['status_line'] ?? ($data['notes_line'] ?? '—') }}</div>
                    </td>
                    <td>
                      @if (array_key_exists('gross_amount', $data))
                        <div class="fw-semibold">${{ number_format((float) ($data['gross_amount'] ?? 0), 2) }}</div>
                        <div class="text-secondary small">Fee: ${{ number_format((float) ($data['fee_amount'] ?? 0), 2) }}</div>
                        <div class="text-secondary small">Net: ${{ number_format((float) ($data['net_amount'] ?? 0), 2) }}</div>
                      @elseif (! is_null($data['amount'] ?? null))
                        <div class="fw-semibold">${{ number_format((float) ($data['amount'] ?? 0), 2) }}</div>
                        <div class="text-secondary small">{{ $data['amount_label'] ?? 'Amount' }}</div>
                      @else
                        <div class="fw-semibold">—</div>
                        <div class="text-secondary small">No amount attached</div>
                      @endif
                    </td>
                    <td>{{ $notification->created_at?->format('M d, Y h:i A') }}</td>
                    <td>
                      <span class="badge {{ $notification->read_at ? 'bg-success' : 'bg-danger' }}">
                        {{ $notification->read_at ? 'Read' : 'Unread' }}
                      </span>
                    </td>
                    <td>
                      @if (! $notification->read_at)
                        <form method="POST" action="{{ route('dashboard.notifications.read', $notification->id) }}">
                          @csrf
                          <button type="submit" class="btn btn-sm btn-outline-primary">Mark read</button>
                        </form>
                      @else
                        <span class="text-secondary small">No action</span>
                      @endif
                    </td>
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
@endsection
