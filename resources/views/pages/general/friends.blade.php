@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h4 class="mb-1">Friends</h4>
        <p class="text-secondary mb-0">Review invited friends and track whether each one is verified or still pending.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-primary btn-icon-text" data-bs-toggle="modal" data-bs-target="#inviteFriendModal">
          <i data-lucide="user-plus" class="btn-icon-prepend"></i> Invite friend
        </button>
        <a href="{{ route('dashboard.profile') }}" class="btn btn-outline-primary btn-icon-text">
          <i data-lucide="arrow-left" class="btn-icon-prepend"></i> Back to profile
        </a>
      </div>
    </div>
  </div>
</div>

@if (session('invite_success'))
  <div class="row">
    <div class="col-12">
      <div class="alert alert-success d-flex align-items-center justify-content-between" role="alert">
        <span>{{ session('invite_success') }}</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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
            <h5 class="mb-1">Friend invitations</h5>
            <p class="text-secondary mb-0">Saved invitations for {{ $user->name }}.</p>
          </div>
          <span class="badge bg-primary">{{ $friendInvitations->count() }} total</span>
        </div>

        @if ($friendInvitations->isEmpty())
          <div class="text-center py-5">
            <div class="wd-60 ht-60 rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3">
              <i data-lucide="users" class="text-secondary"></i>
            </div>
            <h5 class="mb-2">No invited friends yet</h5>
            <p class="text-secondary mb-0">Use the invite button above to add the first friend invitation.</p>
          </div>
        @else
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Telephone</th>
                  <th>Country</th>
                  <th>Status</th>
                  <th>Invited</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($friendInvitations as $friendInvitation)
                  <tr>
                    <td>{{ $friendInvitation->name }}</td>
                    <td>{{ $friendInvitation->email }}</td>
                    <td>{{ $friendInvitation->phone ?: '—' }}</td>
                    <td>{{ $friendInvitation->country ?: '—' }}</td>
                    <td>
                      @if ($friendInvitation->registered_at)
                        <span class="badge bg-success">Registered friend</span>
                      @elseif ($friendInvitation->verified_at)
                        <span class="badge bg-info">Verified</span>
                      @else
                        <span class="badge bg-warning text-dark">Pending</span>
                      @endif
                    </td>
                    <td>{{ $friendInvitation->created_at?->format('M d, Y h:i A') }}</td>
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

<div class="modal fade" id="inviteFriendModal" tabindex="-1" aria-labelledby="inviteFriendModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('dashboard.friends.invite') }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="inviteFriendModalLabel">Invite friend</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="friend_name" class="form-label">Friend name</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="friend_name" name="name" value="{{ old('name') }}" required>
            @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="friend_email" class="form-label">Friend email</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="friend_email" name="email" value="{{ old('email') }}" required>
            @error('email')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="friend_phone" class="form-label">Telephone number</label>
            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="friend_phone" name="phone" value="{{ old('phone') }}">
            @error('phone')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-0">
            <label for="friend_country" class="form-label">Country</label>
            <input type="text" class="form-control @error('country') is-invalid @enderror" id="friend_country" name="country" value="{{ old('country') }}">
            @error('country')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Send invite</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('custom-scripts')
  @if ($errors->any())
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        var inviteModalElement = document.getElementById('inviteFriendModal');
        if (!inviteModalElement || !window.bootstrap) {
          return;
        }

        var inviteModal = new bootstrap.Modal(inviteModalElement);
        inviteModal.show();
      });
    </script>
  @endif
@endpush

