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
              {{ $replyMessage ? 'Reply to conversation' : 'New message' }}
            </div>

            @if ($replyMessage)
              <div class="alert alert-info m-3 mb-0">
                Replying to <strong>{{ $replyMessage->sender->name }}</strong> about <strong>{{ $replyMessage->subject }}</strong>.
              </div>
            @endif

            <form method="POST" action="{{ route('email.store') }}" class="m-3 mb-0">
              @csrf
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
                  <textarea class="form-control @error('body') is-invalid @enderror" name="body" rows="10">{{ old('body') }}</textarea>
                  @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>
              <div class="row">
                <div class="col-md-10 offset-md-2">
                  <button class="btn btn-primary me-1 mb-1" type="submit">Send</button>
                  <a class="btn btn-secondary me-1 mb-1" href="{{ route('email.inbox') }}">Cancel</a>
                </div>
              </div>
            </form>
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
