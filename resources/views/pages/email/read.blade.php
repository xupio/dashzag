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
            <div class="d-flex align-items-center justify-content-between p-3 border-bottom fs-16px">
              <div class="d-flex align-items-center">
                <i data-lucide="messages-square" class="text-primary icon-md me-2"></i>
                <span>{{ $message->subject }}</span>
                <span class="badge bg-light text-dark ms-2">{{ \App\Models\InternalMessage::labelOptions()[$message->label] ?? ucfirst($message->label ?? 'General') }}</span>
              </div>
              <div class="d-flex gap-2">
                @if ($recipientRecord)
                  <form method="POST" action="{{ route('email.toggle-star', $recipientRecord) }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-warning" type="submit">{{ $recipientRecord->starred_at ? 'Unstar' : 'Star' }}</button>
                  </form>
                  <form method="POST" action="{{ route('email.toggle-read', $recipientRecord) }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-secondary" type="submit">{{ $recipientRecord->read_at ? 'Mark unread' : 'Mark read' }}</button>
                  </form>
                  <form method="POST" action="{{ route('email.archive', $recipientRecord) }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-secondary" type="submit">Archive</button>
                  </form>
                  <form method="POST" action="{{ route('email.delete', $recipientRecord) }}" onsubmit="return confirm('Delete this message from your mailbox?');">
                    @csrf
                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                  </form>
                @endif
                <a href="{{ route('email.compose', ['reply' => $message->id]) }}" class="btn btn-sm btn-outline-primary">Reply in compose</a>
                <a href="{{ $readContext === 'sent' ? route('email.sent') : route('email.inbox') }}" class="btn btn-sm btn-outline-secondary">Back</a>
              </div>
            </div>

            @if (session('mail_success'))
              <div class="alert alert-success m-3 mb-0">{{ session('mail_success') }}</div>
            @endif

            <div class="p-3 border-bottom bg-light">
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                  <h6 class="mb-1">Thread activity</h6>
                  <p class="text-secondary mb-0">{{ $threadMessages->count() }} message{{ $threadMessages->count() === 1 ? '' : 's' }} in this conversation.</p>
                </div>
                <span class="badge bg-primary-subtle text-primary">{{ ucfirst($readContext) }} view</span>
              </div>
            </div>

            <div class="p-3 border-bottom">
              @foreach ($threadMessages as $threadMessage)
                <div class="border rounded-3 p-3 mb-3 {{ $threadMessage->id === $message->id ? 'border-primary bg-primary-subtle' : '' }}">
                  <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-2">
                    <div class="d-flex align-items-center">
                      <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center me-2" style="width:38px; height:38px; font-weight:700;">
                        {{ strtoupper(substr($threadMessage->sender->name, 0, 1)) }}
                      </div>
                      <div>
                        <div class="fw-semibold">{{ $threadMessage->sender->name }}</div>
                        <div class="text-secondary fs-12px">
                          To: {{ $threadMessage->toRecipients->pluck('user.name')->join(', ') ?: 'No recipients' }}
                          @if ($threadMessage->ccRecipients->isNotEmpty())
                            <span class="ms-2">Cc: {{ $threadMessage->ccRecipients->pluck('user.name')->join(', ') }}</span>
                          @endif
                        </div>
                      </div>
                    </div>
                    <div class="text-secondary fs-12px text-end">
                      <div>{{ $threadMessage->created_at->format('M d, Y') }}</div>
                      <div>{{ $threadMessage->created_at->format('H:i') }}</div>
                    </div>
                  </div>
                  <div class="fs-14px lh-lg">{!! nl2br(e($threadMessage->body)) !!}</div>
                  @if ($threadMessage->attachments->isNotEmpty())
                    <div class="mt-3 border-top pt-3">
                      <div class="fw-semibold mb-2">Attachments</div>
                      <div class="d-flex flex-column gap-2">
                        @foreach ($threadMessage->attachments as $attachment)
                          <a class="text-decoration-none" href="{{ route('email.attachments.download', $attachment) }}">
                            {{ $attachment->original_name }}
                            <span class="text-secondary fs-12px">({{ number_format($attachment->size / 1024, 1) }} KB)</span>
                          </a>
                        @endforeach
                      </div>
                    </div>
                  @endif
                </div>
              @endforeach
            </div>

            <div class="p-3">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                  <h5 class="mb-1">Quick reply</h5>
                  <p class="text-secondary mb-0">Reply to everyone already in this thread except yourself.</p>
                </div>
              </div>
              <form method="POST" action="{{ route('email.reply', $message) }}">
                @csrf
                <div class="mb-3">
                  <textarea class="form-control @error('body') is-invalid @enderror" name="body" rows="6" placeholder="Write your reply here...">{{ old('body') }}</textarea>
                  @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="d-flex gap-2">
                  <button class="btn btn-primary" type="submit">Send reply</button>
                  <a href="{{ route('email.compose', ['reply' => $message->id]) }}" class="btn btn-outline-secondary">Open full composer</a>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
