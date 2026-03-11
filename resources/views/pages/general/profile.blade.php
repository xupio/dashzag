@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-12 grid-margin">
    <div class="card">
      <div class="position-relative">
        <figure class="overflow-hidden mb-0 d-flex justify-content-center bg-primary bg-opacity-10">
          <img src="{{ url('https://placehold.co/1300x272/eaf1ff/274690?text=Account+Profile') }}" class="rounded-top img-fluid" alt="profile cover">
        </figure>
        <div class="d-flex justify-content-between align-items-center position-absolute top-90 w-100 px-3 px-md-4 mt-n4 flex-wrap gap-3">
          <div class="d-flex align-items-center">
            <img class="w-70px rounded-circle border border-3 border-white shadow-sm" src="{{ url('https://placehold.co/70x70/274690/ffffff?text=' . urlencode(substr($user->name, 0, 1))) }}" alt="profile avatar">
            <div class="ms-3">
              <span class="h4 d-block text-dark mb-1">{{ $user->name }}</span>
              <span class="text-secondary">{{ $user->email }}</span>
            </div>
          </div>
          <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('profile.edit') }}" class="btn btn-primary btn-icon-text">
              <i data-lucide="edit" class="btn-icon-prepend"></i> Account settings
            </a>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-primary btn-icon-text">
              <i data-lucide="layout-dashboard" class="btn-icon-prepend"></i> Back to dashboard
            </a>
          </div>
        </div>
      </div>
      <div class="d-flex justify-content-center p-3 rounded-bottom">
        <ul class="d-flex align-items-center flex-wrap m-0 p-0 list-unstyled gap-3 gap-md-4">
          <li class="d-flex align-items-center active">
            <i class="me-1 icon-md text-primary" data-lucide="user-round"></i>
            <span class="pt-1px text-primary">Profile overview</span>
          </li>
          <li class="d-flex align-items-center">
            <i class="me-1 icon-md text-secondary" data-lucide="mail-check"></i>
            <span class="pt-1px text-body">{{ $user->hasVerifiedEmail() ? 'Verified email' : 'Verification pending' }}</span>
          </li>
          <li class="d-flex align-items-center">
            <i class="me-1 icon-md text-secondary" data-lucide="calendar-days"></i>
            <span class="pt-1px text-body">Joined {{ optional($user->created_at)->format('M d, Y') }}</span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</div>

<div class="row profile-body">
  <div class="col-md-4 col-xl-3 left-wrapper grid-margin stretch-card">
    <div class="card rounded w-100">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h6 class="card-title mb-0">Account summary</h6>
          <span class="badge {{ $user->hasVerifiedEmail() ? 'bg-success' : 'bg-warning text-dark' }}">{{ $user->hasVerifiedEmail() ? 'Verified' : 'Pending' }}</span>
        </div>
        <p class="text-secondary mb-4">Manage your public details, security settings, and the main shortcuts you need from one place.</p>
        <div class="mb-3">
          <label class="fs-11px fw-bolder mb-0 text-uppercase">Full name</label>
          <p class="text-secondary">{{ $user->name }}</p>
        </div>
        <div class="mb-3">
          <label class="fs-11px fw-bolder mb-0 text-uppercase">Email</label>
          <p class="text-secondary">{{ $user->email }}</p>
        </div>
        <div class="mb-3">
          <label class="fs-11px fw-bolder mb-0 text-uppercase">Member since</label>
          <p class="text-secondary">{{ optional($user->created_at)->format('F d, Y') }}</p>
        </div>
        <div>
          <label class="fs-11px fw-bolder mb-0 text-uppercase">Profile tools</label>
          <div class="mt-3 d-grid gap-2">
            <a href="{{ route('profile.edit') }}" class="btn btn-outline-primary">Edit profile</a>
            <a href="{{ url('/email/inbox') }}" class="btn btn-outline-secondary">Open inbox</a>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Dashboard home</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-8 col-xl-6 middle-wrapper">
    <div class="row">
      <div class="col-12 grid-margin stretch-card">
        <div class="card rounded w-100">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
              <div>
                <h6 class="card-title mb-1">Profile shortcuts</h6>
                <p class="text-secondary mb-0">Fast access to the most common account actions.</p>
              </div>
            </div>
            <div class="row g-3">
              <div class="col-sm-6">
                <a href="{{ route('profile.edit') }}" class="card border h-100 text-decoration-none text-body">
                  <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                      <div class="wd-40 ht-40 rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-3">
                        <i data-lucide="settings" class="text-primary"></i>
                      </div>
                      <h6 class="mb-0">Account settings</h6>
                    </div>
                    <p class="text-secondary mb-0">Update your name, email address, and password from the built-in settings form.</p>
                  </div>
                </a>
              </div>
              <div class="col-sm-6">
                <a href="{{ route('dashboard') }}" class="card border h-100 text-decoration-none text-body">
                  <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                      <div class="wd-40 ht-40 rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center me-3">
                        <i data-lucide="layout-dashboard" class="text-success"></i>
                      </div>
                      <h6 class="mb-0">Dashboard overview</h6>
                    </div>
                    <p class="text-secondary mb-0">Jump back to the live metrics dashboard and your top-level activity cards.</p>
                  </div>
                </a>
              </div>
              <div class="col-sm-6">
                <a href="{{ url('/email/inbox') }}" class="card border h-100 text-decoration-none text-body">
                  <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                      <div class="wd-40 ht-40 rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center me-3">
                        <i data-lucide="mail" class="text-info"></i>
                      </div>
                      <h6 class="mb-0">Inbox</h6>
                    </div>
                    <p class="text-secondary mb-0">Open the email workspace directly from your profile when you need quick follow-up access.</p>
                  </div>
                </a>
              </div>
              <div class="col-sm-6">
                <a href="{{ url('/apps/calendar') }}" class="card border h-100 text-decoration-none text-body">
                  <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                      <div class="wd-40 ht-40 rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center me-3">
                        <i data-lucide="calendar" class="text-warning"></i>
                      </div>
                      <h6 class="mb-0">Calendar</h6>
                    </div>
                    <p class="text-secondary mb-0">Use the built-in calendar as a quick navigation shortcut from the dashboard area.</p>
                  </div>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 grid-margin stretch-card">
        <div class="card rounded w-100">
          <div class="card-body">
            <h6 class="card-title mb-3">Security and status</h6>
            <div class="row g-3">
              <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                  <p class="text-secondary mb-1">Email status</p>
                  <h5 class="mb-2">{{ $user->hasVerifiedEmail() ? 'Verified' : 'Pending' }}</h5>
                  <small class="text-secondary">Verification is required before accessing protected dashboard areas.</small>
                </div>
              </div>
              <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                  <p class="text-secondary mb-1">Account created</p>
                  <h5 class="mb-2">{{ optional($user->created_at)->diffForHumans() }}</h5>
                  <small class="text-secondary">First registered on {{ optional($user->created_at)->format('M d, Y') }}.</small>
                </div>
              </div>
              <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                  <p class="text-secondary mb-1">Next action</p>
                  <h5 class="mb-2">Keep profile updated</h5>
                  <small class="text-secondary">Refresh your details anytime from the account settings screen.</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-3 right-wrapper grid-margin stretch-card">
    <div class="card rounded w-100">
      <div class="card-body">
        <h6 class="card-title mb-3">Quick links</h6>
        <div class="d-grid gap-2">
          <a href="{{ route('dashboard.profile') }}" class="btn btn-light text-start"><i data-lucide="user" class="icon-sm me-2"></i> Profile home</a>
          <a href="{{ route('profile.edit') }}" class="btn btn-light text-start"><i data-lucide="edit-3" class="icon-sm me-2"></i> Edit profile</a>
          <a href="{{ route('dashboard') }}" class="btn btn-light text-start"><i data-lucide="home" class="icon-sm me-2"></i> Dashboard</a>
          <a href="{{ route('dashboard.wallet') }}" class="btn btn-light text-start"><i data-lucide="wallet" class="icon-sm me-2"></i> Wallet</a>
          @if ($user->isAdmin())
            <a href="{{ route('dashboard.operations') }}" class="btn btn-light text-start"><i data-lucide="shield-check" class="icon-sm me-2"></i> Operations</a>
            <a href="{{ route('dashboard.miner') }}" class="btn btn-light text-start"><i data-lucide="cpu" class="icon-sm me-2"></i> Miner</a>
          @endif
          <a href="{{ route('dashboard.friends') }}" class="btn btn-light text-start"><i data-lucide="users" class="icon-sm me-2"></i> Friends</a>
          <a href="{{ url('/email/inbox') }}" class="btn btn-light text-start"><i data-lucide="inbox" class="icon-sm me-2"></i> Inbox</a>
          <a href="{{ url('/apps/chat') }}" class="btn btn-light text-start"><i data-lucide="message-square" class="icon-sm me-2"></i> Chat</a>
          <a href="{{ url('/apps/calendar') }}" class="btn btn-light text-start"><i data-lucide="calendar-range" class="icon-sm me-2"></i> Calendar</a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection


