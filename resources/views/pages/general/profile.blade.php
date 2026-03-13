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
              <i data-lucide="layout-dashboard" class="btn-icon-prepend"></i> Back to overview
            </a>
          </div>
        </div>
      </div>
      <div class="d-flex justify-content-center p-3 rounded-bottom">
        <ul class="d-flex align-items-center flex-wrap m-0 p-0 list-unstyled gap-3 gap-md-4">
          <li class="d-flex align-items-center active">
            <i class="me-1 icon-md text-primary" data-lucide="user-round"></i>
            <span class="pt-1px text-primary">Personal dashboard</span>
          </li>
          <li class="d-flex align-items-center">
            <i class="me-1 icon-md text-secondary" data-lucide="mail-check"></i>
            <span class="pt-1px text-body">{{ $user->hasVerifiedEmail() ? 'Verified email' : 'Verification pending' }}</span>
          </li>
          <li class="d-flex align-items-center">
            <i class="me-1 icon-md text-secondary" data-lucide="badge-dollar-sign"></i>
            <span class="pt-1px text-body">Current level: {{ $displayTierName }}</span>
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
        <p class="text-secondary mb-4">Your profile page now holds the personal account, investment, and referral progress information.</p>
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
        <div class="mb-3">
          <label class="fs-11px fw-bolder mb-0 text-uppercase">Current level</label>
          <p class="text-secondary">{{ $displayTierName }}</p>
        </div>
        <div>
          <label class="fs-11px fw-bolder mb-0 text-uppercase">Profile tools</label>
          <div class="mt-3 d-grid gap-2">
            <a href="{{ route('profile.edit') }}" class="btn btn-outline-primary">Edit profile</a>
            <a href="{{ route('dashboard.notifications') }}" class="btn btn-outline-secondary">Notifications</a>
            <a href="{{ route('dashboard.notification-preferences') }}" class="btn btn-outline-secondary">Notification preferences</a>
            <a href="{{ route('dashboard.investment-orders') }}" class="btn btn-outline-secondary">Investment orders</a>
            <a href="{{ route('dashboard.investments') }}" class="btn btn-outline-secondary">My investments</a>
            <a href="{{ route('dashboard.network') }}" class="btn btn-outline-secondary">Referral network</a>
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
                <h6 class="card-title mb-1">Investment summary</h6>
                <p class="text-secondary mb-0">All personal mining and account performance stays here instead of the public overview.</p>
              </div>
            </div>
            <div class="row g-3">
              <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                  <p class="text-secondary mb-1">Total invested</p>
                  <h4 class="mb-2">${{ number_format($totalInvested, 2) }}</h4>
                  <small class="text-secondary">Across all active mining packages.</small>
                </div>
              </div>
              <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                  <p class="text-secondary mb-1">Expected monthly earnings</p>
                  <h4 class="mb-2">${{ number_format($expectedMonthlyEarnings, 2) }}</h4>
                  <small class="text-secondary">Projected from your active investments.</small>
                </div>
              </div>
              <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                  <p class="text-secondary mb-1">Available wallet balance</p>
                  <h4 class="mb-2">${{ number_format($availableEarnings, 2) }}</h4>
                  <small class="text-secondary">Ready inside your earnings wallet.</small>
                </div>
              </div>
            </div>
            <div class="row g-3 mt-1">
              <div class="col-md-6">
                <div class="border rounded p-3 h-100">
                  <p class="text-secondary mb-1">Active packages</p>
                  <div class="fw-semibold mb-2">{{ $activeInvestments->count() }} active investment{{ $activeInvestments->count() === 1 ? '' : 's' }}</div>
                  <div class="text-secondary small">
                    {{ $activeInvestments->isNotEmpty() ? $activeInvestments->pluck('package.name')->unique()->implode(', ') : 'No active packages yet' }}
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="border rounded p-3 h-100">
                  <p class="text-secondary mb-1">Team bonus rate</p>
                  <div class="fw-semibold mb-2">{{ number_format((float) $teamBonusRate * 100, 2) }}%</div>
                  <div class="text-secondary small">This bonus improves your paid investment returns as your network grows.</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 grid-margin stretch-card">
        <div class="card rounded w-100 {{ $starterProgress['has_unlocked_basic'] ? 'border border-success' : 'border border-warning' }}">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
              <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                  <h6 class="card-title mb-0">Free Starter mission</h6>
                  <span class="badge {{ $starterProgress['has_unlocked_basic'] ? 'bg-success' : 'bg-warning text-dark' }}">
                    {{ $starterProgress['has_unlocked_basic'] ? 'Basic 100 unlocked' : 'Upgrade in progress' }}
                  </span>
                </div>
                <p class="text-secondary mb-0">Your personal starter mission progress is tracked here because it belongs to your account journey.</p>
              </div>
              <div class="text-md-end">
                <div class="fw-semibold">Current package path: {{ $displayTierName }}</div>
                <div class="text-secondary small">Base level bonus: {{ number_format((float) $level->bonus_rate * 100, 2) }}%</div>
              </div>
            </div>
            <div class="row g-3">
              <div class="col-md-6">
                <div class="border rounded p-3 h-100">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-secondary">Verified invites</span>
                    <span class="fw-semibold">{{ $starterProgress['verified_invites'] }} / {{ $starterProgress['required_verified_invites'] }}</span>
                  </div>
                  <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: {{ min(($starterProgress['verified_invites'] / max($starterProgress['required_verified_invites'], 1)) * 100, 100) }}%"></div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="border rounded p-3 h-100">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-secondary">Direct Basic 100 subscribers</span>
                    <span class="fw-semibold">{{ $starterProgress['direct_basic_subscribers'] }} / {{ $starterProgress['required_direct_basic_subscribers'] }}</span>
                  </div>
                  <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ min(($starterProgress['direct_basic_subscribers'] / max($starterProgress['required_direct_basic_subscribers'], 1)) * 100, 100) }}%"></div>
                  </div>
                </div>
              </div>
            </div>
            <div class="alert {{ $starterProgress['has_unlocked_basic'] ? 'alert-success' : 'alert-light border' }} mt-3 mb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
              <span>
                {{ $starterProgress['has_unlocked_basic'] ? 'Your referral mission is complete. Basic 100 is active on your account.' : 'Complete both goals to unlock Basic 100 automatically on your account.' }}
              </span>
              <a href="{{ route('dashboard.network') }}" class="btn btn-sm btn-outline-primary">Open referral network</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 grid-margin stretch-card">
        <div class="card rounded w-100">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
              <div>
                <h6 class="card-title mb-1">Referral progress</h6>
                <p class="text-secondary mb-0">Your referral and network growth belongs to your personal account dashboard.</p>
              </div>
              <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('dashboard.friends') }}" class="btn btn-outline-primary btn-sm">Manage friends</a>
                <a href="{{ route('dashboard.network') }}" class="btn btn-outline-secondary btn-sm">Open network</a>
              </div>
            </div>
            <div class="row g-3">
              <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                  <p class="text-secondary mb-1">Pending invites</p>
                  <h4 class="mb-2">{{ $pendingReferrals }}</h4>
                  <small class="text-secondary">Invitations still waiting on verification.</small>
                </div>
              </div>
              <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                  <p class="text-secondary mb-1">Verified invites</p>
                  <h4 class="mb-2">{{ $verifiedReferrals }}</h4>
                  <small class="text-secondary">Confirmed contacts inside your pipeline.</small>
                </div>
              </div>
              <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                  <p class="text-secondary mb-1">Registered friends</p>
                  <h4 class="mb-2">{{ $registeredReferrals }}</h4>
                  <small class="text-secondary">Users who completed registration from your invites.</small>
                </div>
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
                  <h5 class="mb-2">Grow your team</h5>
                  <small class="text-secondary">Invite active investors and raise your team bonus rate over time.</small>
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
          <a href="{{ route('dashboard') }}" class="btn btn-light text-start"><i data-lucide="home" class="icon-sm me-2"></i> Overview</a>
          <a href="{{ route('dashboard.notifications') }}" class="btn btn-light text-start"><i data-lucide="bell" class="icon-sm me-2"></i> Notifications</a>
          <a href="{{ route('dashboard.notification-preferences') }}" class="btn btn-light text-start"><i data-lucide="bell-ring" class="icon-sm me-2"></i> Notification preferences</a>
          <a href="{{ route('dashboard.investment-orders') }}" class="btn btn-light text-start"><i data-lucide="receipt-text" class="icon-sm me-2"></i> Investment Orders</a>
          <a href="{{ route('dashboard.investments') }}" class="btn btn-light text-start"><i data-lucide="chart-column" class="icon-sm me-2"></i> Investments</a>
          <a href="{{ route('dashboard.network') }}" class="btn btn-light text-start"><i data-lucide="network" class="icon-sm me-2"></i> Network</a>
          <a href="{{ route('dashboard.wallet') }}" class="btn btn-light text-start"><i data-lucide="wallet" class="icon-sm me-2"></i> Wallet</a>
          @if ($user->isAdmin())
            <a href="{{ route('dashboard.analytics') }}" class="btn btn-light text-start"><i data-lucide="bar-chart-3" class="icon-sm me-2"></i> Analytics</a>
            <a href="{{ route('dashboard.digests') }}" class="btn btn-light text-start"><i data-lucide="calendar-range" class="icon-sm me-2"></i> Digests</a>
            <a href="{{ route('dashboard.network-admin') }}" class="btn btn-light text-start"><i data-lucide="git-branch-plus" class="icon-sm me-2"></i> Network Admin</a>
            <a href="{{ route('dashboard.shareholders') }}" class="btn btn-light text-start"><i data-lucide="badge-dollar-sign" class="icon-sm me-2"></i> Shareholders</a>
            <a href="{{ route('dashboard.users') }}" class="btn btn-light text-start"><i data-lucide="users-round" class="icon-sm me-2"></i> Users</a>
            <a href="{{ route('dashboard.operations') }}" class="btn btn-light text-start"><i data-lucide="shield-check" class="icon-sm me-2"></i> Operations</a>
            <a href="{{ route('dashboard.rewards') }}" class="btn btn-light text-start"><i data-lucide="percent" class="icon-sm me-2"></i> Rewards</a>
            <a href="{{ route('dashboard.settings') }}" class="btn btn-light text-start"><i data-lucide="sliders-horizontal" class="icon-sm me-2"></i> Settings</a>
            <a href="{{ route('dashboard.notification-rules') }}" class="btn btn-light text-start"><i data-lucide="bell-dot" class="icon-sm me-2"></i> Notification Rules</a>
            <a href="{{ route('dashboard.notification-templates') }}" class="btn btn-light text-start"><i data-lucide="message-circle-more" class="icon-sm me-2"></i> Notification Templates</a>
            <a href="{{ route('dashboard.packages') }}" class="btn btn-light text-start"><i data-lucide="package" class="icon-sm me-2"></i> Packages</a>
            <a href="{{ route('dashboard.miners') }}" class="btn btn-light text-start"><i data-lucide="server" class="icon-sm me-2"></i> Miners</a>
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



