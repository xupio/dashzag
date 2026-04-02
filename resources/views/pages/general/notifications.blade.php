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
        <h4 class="mb-0" data-notification-unread-total>{{ $unreadCount }}</h4>
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
            <div class="small {{ $breakdown['unread'] > 0 ? 'text-danger' : 'text-secondary' }}" data-notification-breakdown-unread="{{ $breakdownKey }}">{{ $breakdown['unread'] }} unread</div>
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
            <p class="text-secondary mb-0">Press any message to open the full details. It will be confirmed automatically.</p>
          </div>
          <span class="badge bg-info">{{ $allNotificationsCount }} total</span>
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
                  <th>State</th>
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
                  @php($notificationModalId = 'notificationModal'.$notification->id)
                  <tr
                    class="notification-row {{ $notification->read_at ? '' : 'table-light' }}"
                    role="button"
                    tabindex="0"
                    data-bs-toggle="modal"
                    data-bs-target="#{{ $notificationModalId }}"
                    data-mark-read-url="{{ route('dashboard.notifications.read', $notification->id) }}"
                    data-notification-id="{{ $notification->id }}"
                    data-is-unread="{{ $notification->read_at ? '0' : '1' }}"
                    data-notification-category="{{ $category }}"
                  >
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
                    <td class="notification-read-state" data-notification-read-state="{{ $notification->id }}">
                      <span class="badge {{ $notification->read_at ? 'bg-success' : 'bg-warning text-dark' }}">
                        {{ $notification->read_at ? 'Read' : 'Unread' }}
                      </span>
                    </td>
                  </tr>
                  <div class="modal fade" id="{{ $notificationModalId }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                      <div class="modal-content">
                        <div class="modal-header">
                          <div>
                            <h5 class="modal-title mb-1">{{ $subjectLabel }}</h5>
                            <div class="text-secondary small">{{ $notification->created_at?->format('M d, Y h:i A') }}</div>
                          </div>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="badge {{ $statusClass }}">{{ str($status)->replace('_', ' ')->title() }}</span>
                            <span class="badge bg-light text-dark border">{{ str($category)->replace('_', ' ')->title() }}</span>
                            <span class="badge {{ $notification->read_at ? 'bg-success' : 'bg-warning text-dark' }}" data-modal-read-badge="{{ $notification->id }}">
                              {{ $notification->read_at ? 'Read' : 'Unread' }}
                            </span>
                          </div>

                          <div class="mb-3">
                            <div class="text-secondary small mb-1">Message</div>
                            <div class="fs-6">{{ $data['message'] ?? 'There is a new update on your account.' }}</div>
                          </div>

                          <div class="row g-3">
                            <div class="col-md-6">
                              <div class="border rounded p-3 h-100">
                                <div class="text-secondary small mb-1">Details</div>
                                <div class="fw-semibold">{{ $detailLabel }}</div>
                                <div class="text-secondary small mt-1">{{ $data['context_value'] ?? ($data['destination'] ?? '—') }}</div>
                                <div class="text-secondary small mt-2">{{ $data['status_line'] ?? ($data['notes_line'] ?? '—') }}</div>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="border rounded p-3 h-100">
                                <div class="text-secondary small mb-1">Amount</div>
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
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="modal-footer justify-content-between">
                          <div class="text-secondary small">Opening this message confirms it automatically.</div>
                          <div class="d-flex gap-2 flex-wrap">
                            @if (! empty($data['action_url']))
                              <a href="{{ $data['action_url'] }}" class="btn btn-outline-primary">
                                {{ $data['action_text'] ?? 'Open' }}
                              </a>
                            @endif
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

@once
  @push('custom-scripts')
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
          || document.querySelector('meta[name="_token"]')?.getAttribute('content');

        const handleNotificationOpen = async function (row) {
          if (row.dataset.isUnread !== '1' || !csrfToken) {
            return;
          }

          try {
            const response = await fetch(row.dataset.markReadUrl, {
              method: 'POST',
              headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
              },
            });

            if (! response.ok) {
              return;
            }

            const readStateCell = document.querySelector('[data-notification-read-state="' + row.dataset.notificationId + '"]');
            const modalBadge = document.querySelector('[data-modal-read-badge="' + row.dataset.notificationId + '"]');
            const unreadTotal = document.querySelector('[data-notification-unread-total]');
            const categoryUnread = document.querySelector('[data-notification-breakdown-unread="' + row.dataset.notificationCategory + '"]');
            const headerIndicator = document.querySelector('[data-header-notification-indicator]');
            const headerCount = document.querySelector('[data-header-notification-count]');

            if (readStateCell) {
              readStateCell.innerHTML = '<span class="badge bg-success">Read</span>';
            }

            if (modalBadge) {
              modalBadge.className = 'badge bg-success';
              modalBadge.textContent = 'Read';
            }

            row.dataset.isUnread = '0';
            row.classList.remove('table-light');

            if (unreadTotal) {
              const currentUnreadTotal = Number.parseInt(unreadTotal.textContent || '0', 10);
              unreadTotal.textContent = String(Math.max(currentUnreadTotal - 1, 0));
            }

            if (categoryUnread) {
              const currentCategoryUnread = Number.parseInt((categoryUnread.textContent || '0').trim(), 10);
              const nextCategoryUnread = Math.max(currentCategoryUnread - 1, 0);
              categoryUnread.textContent = nextCategoryUnread + ' unread';
              categoryUnread.classList.toggle('text-danger', nextCategoryUnread > 0);
              categoryUnread.classList.toggle('text-secondary', nextCategoryUnread === 0);
            }

            if (headerCount) {
              const currentHeaderUnread = Number.parseInt(headerCount.getAttribute('data-header-notification-count') || '0', 10);
              const nextHeaderUnread = Math.max(currentHeaderUnread - 1, 0);
              headerCount.setAttribute('data-header-notification-count', String(nextHeaderUnread));
              headerCount.textContent = nextHeaderUnread + ' New Notification' + (nextHeaderUnread === 1 ? '' : 's');

              if (nextHeaderUnread === 0 && headerIndicator) {
                headerIndicator.remove();
              }
            }
          } catch (error) {
            console.error('Failed to confirm notification.', error);
          }
        };

        document.querySelectorAll('.notification-row').forEach((row) => {
          row.addEventListener('click', function () {
            handleNotificationOpen(this);
          });

          row.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
              event.preventDefault();
              this.click();
            }
          });
        });
      });
    </script>
  @endpush
@endonce
@endsection
