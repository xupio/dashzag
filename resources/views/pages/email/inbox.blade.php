@extends('layout.master')

@section('content')
<div class="row inbox-wrapper">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-body">
        <div class="row">
          <div class="col-lg-3 col-xxl-2 border-end-lg">
            @include('pages.email._aside', ['folder' => $folder, 'messageCounts' => $messageCounts, 'mailIdentity' => $mailIdentity])
          </div>
          <div class="col-lg-9 col-xxl-10">
            @php
              $folderTitle = match ($folder) {
                  'starred' => 'Starred',
                  'archived' => 'Archive',
                  'trash' => 'Trash',
                  default => 'Inbox',
              };
              $folderIcon = match ($folder) {
                  'starred' => 'star',
                  'archived' => 'archive',
                  'trash' => 'trash-2',
                  default => 'inbox',
              };
              $bulkOptions = match ($folder) {
                  'trash' => [
                      'restore' => 'Restore selected',
                      'purge' => 'Delete selected permanently',
                  ],
                  default => [
                      'archive' => 'Archive selected',
                      'trash' => 'Move selected to trash',
                  ],
              };
            @endphp
            <div class="p-3 border-bottom">
              <div class="row align-items-center">
                <div class="col-lg-6">
                  <div class="d-flex align-items-end mb-2 mb-lg-0">
                    <i data-lucide="{{ $folderIcon }}" class="text-secondary me-2"></i>
                    <h4 class="me-1">{{ $folderTitle }}</h4>
                    <span class="text-secondary">({{ $messageCounts['unread'] }} unread)</span>
                  </div>
                </div>
                <div class="col-lg-6">
                  <form method="GET" action="{{ $folder === 'starred' ? route('email.starred') : ($folder === 'archived' ? route('email.archived') : ($folder === 'trash' ? route('email.trash') : route('email.inbox'))) }}" class="d-flex gap-2">
                    <input class="form-control" type="text" name="search" value="{{ $search }}" placeholder="Search mail...">
                    <select class="form-select" name="label" style="max-width: 180px;">
                      <option value="">All labels</option>
                      @foreach ($labels as $value => $label)
                        <option value="{{ $value }}" @selected($selectedLabel === $value)>{{ $label }}</option>
                      @endforeach
                    </select>
                    <button class="btn btn-icon border bg-transparent" type="submit"><i data-lucide="search"></i></button>
                  </form>
                </div>
              </div>
            </div>
            @if (session('mail_success'))
              <div class="alert alert-success m-3 mb-0">{{ session('mail_success') }}</div>
            @endif
            <div class="p-3 border-bottom d-flex align-items-center justify-content-between flex-wrap">
              <div class="text-secondary">Internal communication between registered ZagChain users.</div>
              <div class="d-flex align-items-center justify-content-end flex-grow-1 mt-2 mt-md-0">
                <span class="me-2">{{ $messages->firstItem() ?? 0 }}-{{ $messages->lastItem() ?? 0 }} of {{ $messages->total() }}</span>
              </div>
            </div>
            <form method="POST" action="{{ route('email.bulk') }}" id="bulk-mailbox-form">
              @csrf
              <input type="hidden" name="folder" value="{{ $folder }}">
              <div class="p-3 border-bottom bg-light d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                  <input class="form-check-input" type="checkbox" id="bulk-select-all">
                  <label class="form-check-label text-secondary" for="bulk-select-all">Select all on this page</label>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <select class="form-select" name="bulk_action" style="min-width: 220px;">
                    @foreach ($bulkOptions as $value => $label)
                      <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                  </select>
                  <button class="btn btn-outline-primary" type="submit">Apply</button>
                </div>
              </div>
              <div class="email-list">
                @forelse ($messages as $record)
                  <div class="email-list-item {{ $record->read_at ? '' : 'email-list-item--unread' }}">
                    <div class="email-list-actions d-flex align-items-center gap-2">
                      <input class="form-check-input mailbox-select-item" type="checkbox" name="message_ids[]" value="{{ $record->id }}">
                      <form method="POST" action="{{ route('email.toggle-star', $record) }}">
                        @csrf
                        <button type="submit" class="btn btn-link p-0 text-decoration-none">
                          <i data-lucide="star" class="{{ $record->starred_at ? 'text-warning fill-warning' : 'text-secondary' }}"></i>
                        </button>
                      </form>
                      <form method="POST" action="{{ route('email.toggle-read', $record) }}">
                        @csrf
                        <button type="submit" class="btn btn-link p-0 text-decoration-none">
                          <i data-lucide="{{ $record->read_at ? 'mail-open' : 'mail' }}" class="text-secondary"></i>
                        </button>
                      </form>
                      @if ($folder === 'trash')
                        <form method="POST" action="{{ route('email.restore', $record) }}">
                          @csrf
                          <button type="submit" class="btn btn-link p-0 text-decoration-none">
                            <i data-lucide="rotate-ccw" class="text-primary"></i>
                          </button>
                        </form>
                        <form method="POST" action="{{ route('email.purge', $record) }}" onsubmit="return confirm('Delete this message permanently?');">
                          @csrf
                          <button type="submit" class="btn btn-link p-0 text-decoration-none">
                            <i data-lucide="trash" class="text-danger"></i>
                          </button>
                        </form>
                      @else
                        @if ($folder !== 'archived')
                          <form method="POST" action="{{ route('email.archive', $record) }}">
                            @csrf
                            <button type="submit" class="btn btn-link p-0 text-decoration-none">
                              <i data-lucide="archive" class="text-secondary"></i>
                            </button>
                          </form>
                        @endif
                        <form method="POST" action="{{ route('email.delete', $record) }}" onsubmit="return confirm('Move this message to trash?');">
                          @csrf
                          <button type="submit" class="btn btn-link p-0 text-decoration-none">
                            <i data-lucide="trash-2" class="text-danger"></i>
                          </button>
                        </form>
                      @endif
                    </div>
                    <a href="{{ route('email.read', $record) }}" class="email-list-detail">
                      <div class="content">
                        <span class="from">{{ $record->message->sender->name }}</span>
                        <span class="badge bg-light text-dark ms-2">{{ $labels[$record->message->label] ?? ucfirst($record->message->label ?? 'General') }}</span>
                        <strong class="d-block mb-1">{{ $record->message->subject }}</strong>
                        <p class="msg">{{ \Illuminate\Support\Str::limit(strip_tags($record->message->body), 150) }}</p>
                      </div>
                      <span class="date">{{ $record->created_at->format('d M') }}</span>
                    </a>
                  </div>
                @empty
                  <div class="p-4 text-center text-secondary">No messages found in this folder.</div>
                @endforelse
              </div>
            </form>
            @if ($messages->hasPages())
              <div class="p-3 border-top">{{ $messages->links() }}</div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('custom-scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('bulk-select-all');
    const checkboxes = document.querySelectorAll('.mailbox-select-item');

    if (!selectAll || !checkboxes.length) {
      return;
    }

    selectAll.addEventListener('change', function () {
      checkboxes.forEach(function (checkbox) {
        checkbox.checked = selectAll.checked;
      });
    });
  });
</script>
@endpush
