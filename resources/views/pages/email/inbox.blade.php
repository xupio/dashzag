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
                  default => 'Inbox',
              };
              $folderIcon = match ($folder) {
                  'starred' => 'star',
                  'archived' => 'archive',
                  default => 'inbox',
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
                  <form method="GET" action="{{ $folder === 'starred' ? route('email.starred') : ($folder === 'archived' ? route('email.archived') : route('email.inbox')) }}" class="input-group">
                    <input class="form-control" type="text" name="search" value="{{ $search }}" placeholder="Search mail...">
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
            <div class="email-list">
              @forelse ($messages as $record)
                <div class="email-list-item {{ $record->read_at ? '' : 'email-list-item--unread' }}">
                  <div class="email-list-actions d-flex align-items-center gap-1">
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
                    @if ($folder !== 'archived')
                      <form method="POST" action="{{ route('email.archive', $record) }}">
                        @csrf
                        <button type="submit" class="btn btn-link p-0 text-decoration-none">
                          <i data-lucide="archive" class="text-secondary"></i>
                        </button>
                      </form>
                    @endif
                  </div>
                  <a href="{{ route('email.read', $record) }}" class="email-list-detail">
                    <div class="content">
                      <span class="from">{{ $record->message->sender->name }}</span>
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
