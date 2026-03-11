@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h4 class="mb-1">My Network</h4>
        <p class="text-secondary mb-0">Track your sponsor, your direct team, your extended downline, and the rewards earned from that network.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('dashboard.friends') }}" class="btn btn-primary btn-icon-text">
          <i data-lucide="user-plus" class="btn-icon-prepend"></i> Invite friends
        </a>
        <a href="{{ route('dashboard.wallet') }}" class="btn btn-outline-primary btn-icon-text">
          <i data-lucide="wallet" class="btn-icon-prepend"></i> Wallet
        </a>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-md-3 grid-margin stretch-card"><div class="card"><div class="card-body"><p class="text-secondary mb-1">Direct team</p><h4 class="mb-0">{{ $directTeam->count() }}</h4></div></div></div>
  <div class="col-md-3 grid-margin stretch-card"><div class="card"><div class="card-body"><p class="text-secondary mb-1">Active team investors</p><h4 class="mb-0">{{ $activeTeamInvestors }}</h4></div></div></div>
  <div class="col-md-3 grid-margin stretch-card"><div class="card"><div class="card-body"><p class="text-secondary mb-1">Second level</p><h4 class="mb-0">{{ $secondLevelTeam->count() }}</h4></div></div></div>
  <div class="col-md-3 grid-margin stretch-card"><div class="card"><div class="card-body"><p class="text-secondary mb-1">Team rewards earned</p><h4 class="mb-0">${{ number_format($referralRewardsTotal, 2) }}</h4></div></div></div>
</div>

<div class="row mb-4">
  <div class="col-lg-4 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h5 class="mb-3">Sponsor summary</h5>
        <div class="border rounded p-3 bg-light mb-3">
          <div class="text-secondary small">Your sponsor</div>
          <div class="fw-semibold">{{ $user->sponsor?->name ?? 'No sponsor assigned' }}</div>
          <div class="text-secondary small">{{ $user->sponsor?->email ?? 'You are currently at the top of your branch.' }}</div>
        </div>
        <div class="border rounded p-3 bg-light mb-3">
          <div class="text-secondary small">Current team bonus rate</div>
          <div class="fw-semibold">{{ number_format($teamBonusRate * 100, 2) }}%</div>
        </div>
        <div class="border rounded p-3 bg-light">
          <div class="text-secondary small">Team capital</div>
          <div class="fw-semibold">${{ number_format($teamCapital, 2) }}</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-8 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h5 class="mb-1">Reward ledger</h5>
            <p class="text-secondary mb-0">Registration, subscription, and team bonuses paid into your wallet.</p>
          </div>
          <span class="badge bg-success">{{ $referralRewards->count() }} rewards</span>
        </div>
        @if ($referralRewards->isEmpty())
          <p class="text-secondary mb-0">No team rewards yet. As your branch grows, rewards will start appearing here.</p>
        @else
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Source</th>
                  <th>Status</th>
                  <th>Amount</th>
                  <th>Notes</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($referralRewards as $reward)
                  <tr>
                    <td>{{ $reward->earned_on?->format('M d, Y') }}</td>
                    <td>{{ str($reward->source)->replace('_', ' ')->title() }}</td>
                    <td><span class="badge {{ $reward->status === 'available' ? 'bg-success' : 'bg-secondary' }}">{{ str($reward->status)->replace('_', ' ')->title() }}</span></td>
                    <td>${{ number_format((float) $reward->amount, 2) }}</td>
                    <td>{{ $reward->notes ?: '—' }}</td>
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

<div class="row mb-4">
  <div class="col-12 stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h5 class="mb-1">Direct team</h5>
            <p class="text-secondary mb-0">People directly attached to your branch after registration verification.</p>
          </div>
          <span class="badge bg-primary">{{ $directTeam->count() }} members</span>
        </div>
        @if ($directTeam->isEmpty())
          <p class="text-secondary mb-0">No direct team members yet.</p>
        @else
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>Member</th>
                  <th>Active package</th>
                  <th>Active capital</th>
                  <th>Status</th>
                  <th>Joined</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($directTeam as $member)
                  @php($activeInvestment = $member->investments->where('status', 'active')->sortByDesc('subscribed_at')->first())
                  <tr>
                    <td>
                      <div class="fw-semibold">{{ $member->name }}</div>
                      <div class="text-secondary small">{{ $member->email }}</div>
                    </td>
                    <td>{{ $activeInvestment?->package?->name ?? 'No active investment yet' }}</td>
                    <td>${{ number_format((float) $member->investments->where('status', 'active')->sum('amount'), 2) }}</td>
                    <td><span class="badge {{ $activeInvestment ? 'bg-success' : 'bg-secondary' }}">{{ $activeInvestment ? 'Investor active' : 'Registered only' }}</span></td>
                    <td>{{ $member->created_at?->format('M d, Y') }}</td>
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

<div class="row mb-4">
  <div class="col-lg-6 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h5 class="mb-1">Second-level team</h5>
            <p class="text-secondary mb-0">Extended network members attached under your direct team.</p>
          </div>
          <span class="badge bg-info">{{ $secondLevelTeam->count() }} members</span>
        </div>
        @if ($secondLevelTeam->isEmpty())
          <p class="text-secondary mb-0">No second-level team activity yet.</p>
        @else
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>Member</th>
                  <th>Status</th>
                  <th>Joined</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($secondLevelTeam as $member)
                  <tr>
                    <td>
                      <div class="fw-semibold">{{ $member->name }}</div>
                      <div class="text-secondary small">{{ $member->email }}</div>
                    </td>
                    <td><span class="badge {{ $member->investments->where('status', 'active')->isNotEmpty() ? 'bg-success' : 'bg-secondary' }}">{{ $member->investments->where('status', 'active')->isNotEmpty() ? 'Investor active' : 'Registered only' }}</span></td>
                    <td>{{ $member->created_at?->format('M d, Y') }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>
  </div>
  <div class="col-lg-6 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h5 class="mb-1">Team events</h5>
            <p class="text-secondary mb-0">Important branch events visible to you as the sponsor.</p>
          </div>
          <span class="badge bg-dark">{{ $teamEvents->count() }} events</span>
        </div>
        @if ($teamEvents->isEmpty())
          <p class="text-secondary mb-0">No network events have been recorded yet.</p>
        @else
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>When</th>
                  <th>Event</th>
                  <th>Member</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($teamEvents as $event)
                  <tr>
                    <td>{{ $event->created_at?->format('M d, Y h:i A') }}</td>
                    <td>
                      <div class="fw-semibold">{{ $event->title }}</div>
                      <div class="text-secondary small">{{ $event->message }}</div>
                    </td>
                    <td>{{ $event->relatedUser?->email ?? '—' }}</td>
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

<div class="row">
  <div class="col-12 stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h5 class="mb-1">Invitation pipeline</h5>
            <p class="text-secondary mb-0">Your original invite list and each contact's progress.</p>
          </div>
          <span class="badge bg-primary">{{ $friendInvitations->count() }} contacts</span>
        </div>
        @if ($friendInvitations->isEmpty())
          <p class="text-secondary mb-0">No invited contacts yet.</p>
        @else
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Verified</th>
                  <th>Registered</th>
                  <th>Active investor</th>
                  <th>Invited</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($friendInvitations as $friendInvitation)
                  @php($teamMember = $directTeam->firstWhere('email', $friendInvitation->email))
                  <tr>
                    <td>{{ $friendInvitation->name }}</td>
                    <td>{{ $friendInvitation->email }}</td>
                    <td><span class="badge {{ $friendInvitation->verified_at ? 'bg-info' : 'bg-secondary' }}">{{ $friendInvitation->verified_at ? 'Yes' : 'No' }}</span></td>
                    <td><span class="badge {{ $friendInvitation->registered_at ? 'bg-success' : 'bg-secondary' }}">{{ $friendInvitation->registered_at ? 'Yes' : 'No' }}</span></td>
                    <td><span class="badge {{ $teamMember && $teamMember->investments->where('status', 'active')->isNotEmpty() ? 'bg-primary' : 'bg-secondary' }}">{{ $teamMember && $teamMember->investments->where('status', 'active')->isNotEmpty() ? 'Yes' : 'No' }}</span></td>
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
@endsection
