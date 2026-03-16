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
            <div class="p-3 border-bottom">
              <div class="row align-items-center">
                <div class="col-lg-6">
                  <div class="d-flex align-items-end mb-2 mb-lg-0">
                    <i data-lucide="file-pen-line" class="text-secondary me-2"></i>
                    <h4 class="me-1">Drafts</h4>
                    <span class="text-secondary">({{ $messageCounts['drafts'] }} saved)</span>
                  </div>
                </div>
                <div class="col-lg-6">
                  <form method="GET" action="{{ route('email.drafts') }}" class="d-flex gap-2">
                    <input class="form-control" type="text" name="search" value="{{ $search }}" placeholder="Search drafts...">
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
            <div class="email-list">
              @forelse ($messages as $message)
                <div class="email-list-item">
                  <div class="email-list-actions d-flex align-items-center gap-2">
                    <a class="favorite" href="{{ route('email.compose', ['draft' => $message->id]) }}"><span><i data-lucide="file-pen-line"></i></span></a>
                    <form method="POST" action="{{ route('email.drafts.delete', $message) }}" onsubmit="return confirm('Delete this draft and its attachments?');">
                      @csrf
                      <button class="btn btn-link text-danger p-0 border-0" type="submit" title="Delete draft">
                        <i data-lucide="trash-2"></i>
                      </button>
                    </form>
                  </div>
                  <a href="{{ route('email.compose', ['draft' => $message->id]) }}" class="email-list-detail">
                    <div class="content">
                      <span class="from">Draft · {{ $labels[$message->label] ?? ucfirst($message->label ?? 'General') }} @if($message->attachments->isNotEmpty()) · {{ $message->attachments->count() }} attachment{{ $message->attachments->count() === 1 ? '' : 's' }} @endif</span>
                      <strong class="d-block mb-1">{{ $message->subject ?: 'Untitled draft' }}</strong>
                      <p class="msg">{{ \Illuminate\Support\Str::limit(strip_tags($message->body ?: 'No message body yet.'), 150) }}</p>
                    </div>
                    <span class="date">{{ $message->updated_at->format('d M') }}</span>
                  </a>
                </div>
              @empty
                <div class="p-4 text-center text-secondary">You do not have any saved drafts.</div>
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
