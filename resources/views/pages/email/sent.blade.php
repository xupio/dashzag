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
                    <i data-lucide="send" class="text-secondary me-2"></i>
                    <h4 class="me-1">Sent Mail</h4>
                    <span class="text-secondary">({{ $messageCounts['sent'] }} messages)</span>
                  </div>
                </div>
                <div class="col-lg-6">
                  <form method="GET" action="{{ route('email.sent') }}" class="input-group">
                    <input class="form-control" type="text" name="search" value="{{ $search }}" placeholder="Search sent mail...">
                    <button class="btn btn-icon border bg-transparent" type="submit"><i data-lucide="search"></i></button>
                  </form>
                </div>
              </div>
            </div>
            <div class="email-list">
              @forelse ($messages as $message)
                <div class="email-list-item">
                  <div class="email-list-actions">
                    <div class="form-check">
                      <input type="checkbox" class="form-check-input" disabled>
                    </div>
                    <a class="favorite" href="javascript:;"><span><i data-lucide="send"></i></span></a>
                  </div>
                  <a href="{{ route('email.sent.read', $message) }}" class="email-list-detail">
                    <div class="content">
                      <span class="from">To: {{ $message->toRecipients->pluck('user.name')->join(', ') }}</span>
                      <strong class="d-block mb-1">{{ $message->subject }}</strong>
                      <p class="msg">{{ \Illuminate\Support\Str::limit(strip_tags($message->body), 150) }}</p>
                    </div>
                    <span class="date">{{ $message->created_at->format('d M') }}</span>
                  </a>
                </div>
              @empty
                <div class="p-4 text-center text-secondary">You have not sent any internal messages yet.</div>
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
