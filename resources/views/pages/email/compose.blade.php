@extends('layout.master')

@push('plugin-styles')
  <link href="{{ asset('build/plugins/select2/select2.min.css') }}" rel="stylesheet" />
@endpush

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
            <div class="d-flex align-items-center p-3 border-bottom fs-16px">
              <span data-lucide="edit" class="icon-md me-2"></span>
              {{ $draftMessage ? 'Edit draft' : ($replyMessage ? 'Reply to conversation' : 'New message') }}
            </div>

            @if (session('mail_success'))
              <div class="alert alert-success m-3 mb-0">{{ session('mail_success') }}</div>
            @endif

            @if ($replyMessage)
              <div class="alert alert-info m-3 mb-0">
                {{ $draftMessage ? 'Draft reply' : 'Replying' }} to <strong>{{ $replyMessage->sender->name }}</strong> about <strong>{{ $replyMessage->subject }}</strong>.
              </div>
            @endif

            <form method="POST" action="{{ route('email.store') }}" class="m-3 mb-0" enctype="multipart/form-data">
              @csrf
              @if ($draftMessage)
                <input type="hidden" name="draft_id" value="{{ $draftMessage->id }}">
              @endif
              @if ($replyMessage)
                <input type="hidden" name="reply_to_message_id" value="{{ $replyMessage->id }}">
              @endif
              <div class="row mb-3">
                <label class="col-md-2 col-form-label">To</label>
                <div class="col-md-10">
                  <select class="compose-multiple-select form-select @error('to') is-invalid @enderror" name="to[]" multiple="multiple">
                    @foreach ($users as $recipient)
                      <option value="{{ $recipient->id }}" @selected(collect(old('to', $prefillTo ?? []))->contains($recipient->id))>{{ $recipient->name }} ({{ $recipient->email }})</option>
                    @endforeach
                  </select>
                  @error('to')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
              </div>
              <div class="row mb-3">
                <label class="col-md-2 col-form-label">Cc</label>
                <div class="col-md-10">
                  <select class="compose-multiple-select form-select @error('cc') is-invalid @enderror" name="cc[]" multiple="multiple">
                    @foreach ($users as $recipient)
                      <option value="{{ $recipient->id }}" @selected(collect(old('cc', $prefillCc ?? []))->contains($recipient->id))>{{ $recipient->name }} ({{ $recipient->email }})</option>
                    @endforeach
                  </select>
                  @error('cc')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
              </div>
              <div class="row mb-3">
                <label class="col-md-2 col-form-label">Subject</label>
                <div class="col-md-10">
                  <input class="form-control @error('subject') is-invalid @enderror" type="text" name="subject" value="{{ old('subject', $prefillSubject ?? '') }}">
                  @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>
              <div class="row mb-3">
                <label class="col-md-2 col-form-label">Message</label>
                <div class="col-md-10">
                  <textarea class="form-control @error('body') is-invalid @enderror" name="body" rows="10">{{ old('body', $prefillBody ?? '') }}</textarea>
                  @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>
              <div class="row mb-3">
                <label class="col-md-2 col-form-label">Attachments</label>
                <div class="col-md-10">
                  <input class="form-control @error('attachments') is-invalid @enderror @error('attachments.*') is-invalid @enderror" type="file" name="attachments[]" multiple>
                  <div class="form-text">You can upload multiple files up to 10 MB each.</div>
                  @error('attachments')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                  @error('attachments.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                  @if ($draftMessage && $draftMessage->attachments->isNotEmpty())
                    <div class="border rounded-3 p-3 mt-3 bg-light">
                      <div class="fw-semibold mb-2">Existing draft attachments</div>
                      <div class="d-flex flex-column gap-2">
                        @foreach ($draftMessage->attachments as $attachment)
                          <div class="d-flex align-items-center justify-content-between gap-2">
                            <a class="text-decoration-none" href="{{ route('email.attachments.download', $attachment) }}">{{ $attachment->original_name }}</a>
                            <form method="POST" action="{{ route('email.draft-attachments.remove', [$draftMessage, $attachment]) }}">
                              @csrf
                              <button class="btn btn-sm btn-outline-danger" type="submit">Remove</button>
                            </form>
                          </div>
                        @endforeach
                      </div>
                    </div>
                  @endif
                </div>
              </div>
              <div class="row">
                <div class="col-md-10 offset-md-2 d-flex gap-2 flex-wrap">
                  <button class="btn btn-primary me-1 mb-1" type="submit" name="mail_action" value="send">Send</button>
                  <button class="btn btn-outline-secondary me-1 mb-1" type="submit" name="mail_action" value="draft">Save draft</button>
                  @if ($draftMessage)
                    <button class="btn btn-outline-danger me-1 mb-1" type="submit" form="delete-draft-form">Delete draft</button>
                  @endif
                  <a class="btn btn-secondary me-1 mb-1" href="{{ route('email.inbox') }}">Cancel</a>
                </div>
              </div>
            </form>
            @if ($draftMessage)
              <form id="delete-draft-form" method="POST" action="{{ route('email.drafts.delete', $draftMessage) }}" onsubmit="return confirm('Delete this draft and its attachments?');" class="d-none">
                @csrf
              </form>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('plugin-scripts')
  <script src="{{ asset('build/plugins/jquery/jquery.min.js') }}"></script>
  <script src="{{ asset('build/plugins/select2/select2.min.js') }}"></script>
@endpush

@push('custom-scripts')
<script>
  $(function () {
    $('.compose-multiple-select').select2({
      placeholder: 'Select users',
      width: '100%'
    });
  });
</script>
@endpush



