@extends('layout.master')

@section('content')
@php
  $powerBadgeClasses = [
      'secondary' => 'bg-secondary-subtle text-secondary',
      'info' => 'bg-info-subtle text-info',
      'primary' => 'bg-primary-subtle text-primary',
      'warning' => 'bg-warning-subtle text-warning',
      'success' => 'bg-success-subtle text-success',
  ];
  $powerFrameClasses = [
      'secondary' => 'border-secondary-subtle',
      'info' => 'border-info-subtle',
      'primary' => 'border-primary-subtle',
      'warning' => 'border-warning-subtle',
      'success' => 'border-success-subtle',
  ];
  $formatRewardSource = function (string $source): string {
      return match (true) {
          $source === 'referral_registration' => 'Referral Registration',
          $source === 'referral_subscription' => 'Referral Subscription',
          $source === 'team_subscription_bonus' => 'Level 1 Team Bonus',
          $source === 'team_downline_bonus' => 'Level 2 Team Bonus',
          str_starts_with($source, 'team_level_') && str_ends_with($source, '_bonus') => 'Level '.str($source)->between('team_level_', '_bonus').' Team Bonus',
          default => str($source)->replace('_', ' ')->title(),
      };
  };

  $formatEventDepth = function (string $type, string $title): string {
      return match (true) {
          $type === 'team_subscription' => 'Level 1',
          $type === 'team_downline_subscription' => 'Level 2',
          str_starts_with($type, 'team_level_') && str_ends_with($type, '_subscription') => 'Level '.str($type)->between('team_level_', '_subscription'),
          default => $title,
      };
  };
@endphp
<div class="row">
  <div class="col-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h4 class="mb-1">My Network</h4>
        <p class="text-secondary mb-0">Manage your direct team, track branch production, and follow the rewards generated under your name.</p>
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
    <div class="card h-100">
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
        <div class="border rounded p-3 bg-light mb-0">
          <div class="text-secondary small">Team capital</div>
          <div class="fw-semibold">${{ number_format($teamCapital, 2) }}</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-8 grid-margin stretch-card">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h5 class="mb-1">Network snapshot</h5>
            <p class="text-secondary mb-0">A fast view of where your branch stands right now.</p>
          </div>
        </div>
        <div class="row g-3">
          <div class="col-md-6"><div class="border rounded p-3 h-100"><div class="text-secondary small mb-1">Verified invitations</div><div class="fs-4 fw-semibold">{{ $verifiedCount }}</div><div class="text-secondary small">Out of {{ $invitedCount }} total invited contacts</div></div></div>
          <div class="col-md-6"><div class="border rounded p-3 h-100"><div class="text-secondary small mb-1">Registered friends</div><div class="fs-4 fw-semibold">{{ $registeredCount }}</div><div class="text-secondary small">Members who finished account creation</div></div></div>
          <div class="col-md-6"><div class="border rounded p-3 h-100"><div class="text-secondary small mb-1">Subscribed investors</div><div class="fs-4 fw-semibold">{{ $subscribedCount }}</div><div class="text-secondary small">Invited contacts with active investment</div></div></div>
          <div class="col-md-6"><div class="border rounded p-3 h-100"><div class="text-secondary small mb-1">Reward events</div><div class="fs-4 fw-semibold">{{ $allReferralRewardsCount }}</div><div class="text-secondary small">Bonuses already added to your wallet</div></div></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
    @if ($topMover)
      <div class="col-12 stretch-card mb-3">
        <div class="card border-0 shadow-sm overflow-hidden">
          <div class="card-body p-0">
            <div class="row g-0">
              <div class="col-xl-4">
                <div class="h-100 p-4 text-white" style="background: linear-gradient(135deg, #0f9d58 0%, #6571ff 100%);">
                  <div class="text-uppercase small opacity-75 mb-2">Top mover this week</div>
                  <div class="display-6 fw-bold mb-2">{{ $topMover['weekly_momentum']['score'] }}/100</div>
                  <div class="mb-2">{{ $topMover['member']->name }}</div>
                  <span class="badge bg-white text-dark">{{ $topMover['profile_power']['rank_label'] }}</span>
                </div>
              </div>
              <div class="col-xl-8">
                <div class="p-4">
                  <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                    <div>
                      <h5 class="mb-1">{{ $topMover['member']->name }}</h5>
                      <p class="text-secondary mb-0">The strongest recent mover based on weekly verified invites, registrations, new investors, and mining streak.</p>
                    </div>
                    <a href="{{ route('dashboard.investors.show', ['user' => $topMover['member'], 'from' => 'network']) }}" class="btn btn-outline-primary btn-sm">Open profile</a>
                  </div>
                  <div class="row g-3">
                    <div class="col-md-3"><div class="border rounded p-3 h-100 bg-light"><div class="text-secondary small">Verified this week</div><div class="fw-semibold">{{ $topMover['weekly_momentum']['verified_invites'] }}</div></div></div>
                    <div class="col-md-3"><div class="border rounded p-3 h-100 bg-light"><div class="text-secondary small">Registered this week</div><div class="fw-semibold">{{ $topMover['weekly_momentum']['registered_referrals'] }}</div></div></div>
                    <div class="col-md-3"><div class="border rounded p-3 h-100 bg-light"><div class="text-secondary small">New active investors</div><div class="fw-semibold">{{ $topMover['weekly_momentum']['new_active_direct_investors'] }}</div></div></div>
                    <div class="col-md-3"><div class="border rounded p-3 h-100 bg-light"><div class="text-secondary small">Mining streak</div><div class="fw-semibold">{{ $topMover['weekly_momentum']['streak_days'] }} day{{ $topMover['weekly_momentum']['streak_days'] === 1 ? '' : 's' }}</div></div></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    @endif
    <div class="col-12 stretch-card mb-3">
      <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-body p-0">
          @if ($monthlyBranchChampion)
            <div class="row g-0">
              <div class="col-xl-4">
                <div class="h-100 p-4 text-white" style="background: linear-gradient(135deg, #f9ab00 0%, #274690 100%);">
                  <div class="text-uppercase small opacity-75 mb-2">Monthly branch champion</div>
                  <div class="display-6 fw-bold mb-2">{{ $monthlyBranchChampion['monthly_momentum']['score'] }}/100</div>
                  <div class="mb-2">{{ $monthlyBranchChampion['member']->name }}</div>
                  <span class="badge bg-white text-dark">{{ $monthlyBranchChampion['profile_power']['rank_label'] }}</span>
                </div>
              </div>
              <div class="col-xl-8">
                <div class="p-4">
                  <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                    <div>
                      <h5 class="mb-1">{{ $monthlyBranchChampion['member']->name }}</h5>
                      <p class="text-secondary mb-0">The strongest branch builder this month based on broader momentum across referrals, new investors, and miner income.</p>
                    </div>
                    <a href="{{ route('dashboard.investors.show', ['user' => $monthlyBranchChampion['member'], 'from' => 'network']) }}" class="btn btn-outline-primary btn-sm">Open profile</a>
                  </div>
                  <div class="row g-3">
                    <div class="col-md-3"><div class="border rounded p-3 h-100 bg-light"><div class="text-secondary small">Verified this month</div><div class="fw-semibold">{{ $monthlyBranchChampion['monthly_momentum']['verified_invites'] }}</div></div></div>
                    <div class="col-md-3"><div class="border rounded p-3 h-100 bg-light"><div class="text-secondary small">Registered this month</div><div class="fw-semibold">{{ $monthlyBranchChampion['monthly_momentum']['registered_referrals'] }}</div></div></div>
                    <div class="col-md-3"><div class="border rounded p-3 h-100 bg-light"><div class="text-secondary small">New active investors</div><div class="fw-semibold">{{ $monthlyBranchChampion['monthly_momentum']['new_active_direct_investors'] }}</div></div></div>
                    <div class="col-md-3"><div class="border rounded p-3 h-100 bg-light"><div class="text-secondary small">Miner income</div><div class="fw-semibold">${{ number_format($monthlyBranchChampion['monthly_momentum']['mining_income'], 2) }}</div></div></div>
                  </div>
                </div>
              </div>
            </div>
          @else
            <div class="p-4">
              <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <div>
                  <h5 class="mb-1">Monthly branch champion</h5>
                  <p class="text-secondary mb-0">This space will highlight the strongest branch builder of the month once your network starts moving.</p>
                </div>
                <span class="badge bg-light text-dark">No champion yet</span>
              </div>
              <div class="text-secondary small">As soon as your direct team starts generating verified invites, registrations, active investors, and mining income, the monthly champion card will appear here automatically.</div>
            </div>
          @endif
        </div>
      </div>
    </div>
    <div class="col-12 stretch-card">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
              <h5 class="mb-1">Team power leaderboard</h5>
              <p class="text-secondary mb-0">The strongest direct branch members in your network right now.</p>
              <div class="d-flex align-items-center gap-2 flex-wrap mt-2">
                <span class="badge bg-success-subtle text-success">Weekly winner</span>
                <span class="badge bg-warning-subtle text-warning">Monthly champion</span>
                <span class="text-secondary small">These badges reflect the current Hall of Fame leaders.</span>
              </div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <span class="badge bg-primary">{{ $teamLeaderboard->count() }} ranked members</span>
              <a href="{{ route('dashboard.hall-of-fame') }}" class="btn btn-outline-primary btn-sm">Open Hall of Fame</a>
            </div>
          </div>
          @if ($teamLeaderboard->isEmpty())
            <div class="text-secondary">No ranked team members yet. Invite and activate your first branch to start the leaderboard.</div>
          @else
            <div class="row g-3">
              @foreach ($teamLeaderboard as $leaderBranch)
                @php($isWeeklyWinner = $topMover && $leaderBranch['member']->is($topMover['member']))
                @php($isMonthlyChampion = $monthlyBranchChampion && $leaderBranch['member']->is($monthlyBranchChampion['member']))
                <div class="col-md-6 col-xl-4">
                  <div class="border rounded p-3 h-100 {{ $powerFrameClasses[$leaderBranch['profile_power']['rank_accent']] ?? 'border-primary-subtle' }} {{ $isMonthlyChampion ? 'border-warning border-2' : '' }}">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                      <div>
                        <div class="fw-semibold">{{ $leaderBranch['member']->name }}</div>
                        <div class="text-secondary small">{{ $leaderBranch['member']->email }}</div>
                      </div>
                      <div class="rounded-circle d-flex align-items-center justify-content-center fw-semibold bg-light" style="width: 36px; height: 36px;">{{ $loop->iteration }}</div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap mb-2">
                      <span class="badge bg-light text-dark">Power {{ $leaderBranch['profile_power']['score'] }}/100</span>
                      <span class="badge {{ $powerBadgeClasses[$leaderBranch['profile_power']['rank_accent']] ?? 'bg-primary-subtle text-primary' }}">{{ $leaderBranch['profile_power']['rank_label'] }}</span>
                      @foreach (($leaderBranch['reward_cap_badges'] ?? []) as $rewardCapBadge)
                        <span class="badge {{ $rewardCapBadge['class'] }}">{{ $rewardCapBadge['label'] }}</span>
                      @endforeach
                      @if ($isWeeklyWinner)
                        <span class="badge bg-success">Weekly winner</span>
                      @endif
                      @if ($isMonthlyChampion)
                        <span class="badge bg-warning text-dark">Monthly champion</span>
                      @endif
                    </div>
                    <div class="text-secondary small mb-3">${{ number_format($leaderBranch['active_capital'], 2) }} active capital · {{ $leaderBranch['downline_active_count'] }} active downline investors</div>
                    <a href="{{ route('dashboard.investors.show', ['user' => $leaderBranch['member'], 'from' => 'network']) }}" class="btn btn-outline-primary btn-sm">Open profile</a>
                  </div>
                </div>
              @endforeach
            </div>
          @endif
        </div>
      </div>
    </div>
</div>

@if ($topTeamMember)
  <div class="row mb-4">
    <div class="col-12 stretch-card">
      <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-body p-0">
          <div class="row g-0">
            <div class="col-xl-4">
              <div class="h-100 p-4 text-white" style="background: linear-gradient(135deg, #274690 0%, #6571ff 100%);">
                <div class="text-uppercase small opacity-75 mb-2">Top team member</div>
                <div class="display-6 fw-bold mb-2">{{ $topTeamMember['profile_power']['score'] }}/100</div>
                <div class="mb-2">{{ $topTeamMember['member']->name }}</div>
                <span class="badge bg-white text-dark">{{ $topTeamMember['profile_power']['rank_label'] }}</span>
              </div>
            </div>
            <div class="col-xl-8">
              <div class="p-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                  <div>
                    <h5 class="mb-1">{{ $topTeamMember['member']->name }}</h5>
                    <p class="text-secondary mb-0">{{ $topTeamMember['member']->email }}</p>
                  </div>
                  <a href="{{ route('dashboard.investors.show', ['user' => $topTeamMember['member'], 'from' => 'network']) }}" class="btn btn-outline-primary btn-sm">Open profile</a>
                </div>
                <div class="row g-3">
                  <div class="col-md-3"><div class="border rounded p-3 h-100 bg-light"><div class="text-secondary small">Rank</div><div class="fw-semibold">{{ $topTeamMember['profile_power']['rank_label'] }}</div></div></div>
                  <div class="col-md-3"><div class="border rounded p-3 h-100 bg-light"><div class="text-secondary small">Active capital</div><div class="fw-semibold">${{ number_format($topTeamMember['active_capital'], 2) }}</div></div></div>
                  <div class="col-md-3"><div class="border rounded p-3 h-100 bg-light"><div class="text-secondary small">Second level</div><div class="fw-semibold">{{ $topTeamMember['downline_count'] }}</div></div></div>
                  <div class="col-md-3"><div class="border rounded p-3 h-100 bg-light"><div class="text-secondary small">Active investors</div><div class="fw-semibold">{{ $topTeamMember['downline_active_count'] }}</div></div></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endif

<div class="row mb-4">
  <div class="col-12 stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h5 class="mb-1">Team tree</h5>
            <p class="text-secondary mb-0">Each direct member acts as a branch head for their own downline.</p>
          </div>
          <span class="badge bg-primary">{{ $directTeamBranches->count() }} active branches</span>
        </div>
        @if ($directTeamBranches->isEmpty())
          <p class="text-secondary mb-0">No direct team members yet.</p>
        @else
          <div class="row g-3">
            @foreach ($directTeamBranches as $branch)
              @php($member = $branch['member'])
              @php($isWeeklyWinner = $topMover && $member->is($topMover['member']))
              @php($isMonthlyChampion = $monthlyBranchChampion && $member->is($monthlyBranchChampion['member']))
              <div class="col-12">
                <div class="border rounded p-3 {{ $powerFrameClasses[$branch['profile_power']['rank_accent']] ?? 'border-primary-subtle' }} {{ $isMonthlyChampion ? 'border-warning border-2' : '' }}">
                  <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                    <div>
                      <div class="d-flex align-items-center gap-2 flex-wrap"><div class="fw-semibold fs-5">{{ $member->name }}</div><a href="{{ route('dashboard.investors.show', ['user' => $member, 'from' => 'network']) }}" class="btn btn-outline-primary btn-xs">Open profile</a></div>
                      <div class="text-secondary small">{{ $member->email }}</div>
                      <div class="mt-2">
                        <span class="badge {{ $branch['is_active_investor'] ? 'bg-success' : 'bg-secondary' }}">{{ $branch['is_active_investor'] ? 'Investor active' : 'Registered only' }}</span>
                        <span class="badge bg-light text-dark">Joined {{ $member->created_at?->format('M d, Y') }}</span>
                        <span class="badge bg-light text-dark">Power {{ $branch['profile_power']['score'] }}/100</span>
                        <span class="badge {{ $powerBadgeClasses[$branch['profile_power']['rank_accent']] ?? 'bg-primary-subtle text-primary' }}">{{ $branch['profile_power']['rank_label'] }}</span>
                        @foreach (($branch['reward_cap_badges'] ?? []) as $rewardCapBadge)
                          <span class="badge {{ $rewardCapBadge['class'] }}">{{ $rewardCapBadge['label'] }}</span>
                        @endforeach
                        @if ($isWeeklyWinner)
                          <span class="badge bg-success">Weekly winner</span>
                        @endif
                        @if ($isMonthlyChampion)
                          <span class="badge bg-warning text-dark">Monthly champion</span>
                        @endif
                      </div>
                    </div>
                    <div class="text-md-end">
                      <div class="fw-semibold">{{ $branch['active_package'] ?? 'No active package yet' }}</div>
                      <div class="text-secondary small">Direct capital: ${{ number_format($branch['active_capital'], 2) }}</div>
                    </div>
                  </div>
                  <div class="row g-3 mb-3">
                    <div class="col-md-4"><div class="bg-light rounded p-3 h-100"><div class="text-secondary small">Direct member capital</div><div class="fs-5 fw-semibold">${{ number_format($branch['active_capital'], 2) }}</div></div></div>
                    <div class="col-md-4"><div class="bg-light rounded p-3 h-100"><div class="text-secondary small">Second-level members</div><div class="fs-5 fw-semibold">{{ $branch['downline_count'] }}</div></div></div>
                    <div class="col-md-4"><div class="bg-light rounded p-3 h-100"><div class="text-secondary small">Second-level active investors</div><div class="fs-5 fw-semibold">{{ $branch['downline_active_count'] }}</div><div class="text-secondary small">Capital: ${{ number_format($branch['downline_capital'], 2) }}</div></div></div>
                  </div>
                  <div>
                    <div class="fw-semibold mb-2">Branch downline</div>
                    @if ($branch['downline_members']->isEmpty())
                      <div class="text-secondary small">No second-level members under this branch yet.</div>
                    @else
                      <div class="row g-2">
                        @foreach ($branch['downline_members'] as $downlineRow)
                          @php($downline = $downlineRow['member'])
                          @php($downlineActive = $downline->investments->where('status', 'active')->where('amount', '>', 0)->isNotEmpty())
                          @php($downlineCapital = (float) $downline->investments->where('status', 'active')->where('amount', '>', 0)->sum('amount'))
                          <div class="col-md-6 col-xl-4">
                            <div class="border rounded p-2 h-100 {{ $powerFrameClasses[$downlineRow['profile_power']['rank_accent']] ?? 'border-primary-subtle' }}">
                              <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap"><div class="fw-semibold">{{ $downline->name }}</div><a href="{{ route('dashboard.investors.show', ['user' => $downline, 'from' => 'network']) }}" class="btn btn-outline-primary btn-xs">Open profile</a></div>
                              <div class="text-secondary small">{{ $downline->email }}</div>
                              <div class="mt-2 d-flex justify-content-between align-items-center gap-2 flex-wrap">
                                <span class="badge {{ $downlineActive ? 'bg-success' : 'bg-secondary' }}">{{ $downlineActive ? 'Investor active' : 'Registered only' }}</span>
                                <span class="text-secondary small">${{ number_format($downlineCapital, 2) }}</span>
                              </div>
                              <div class="mt-2 d-flex gap-2 flex-wrap">
                                <span class="badge bg-light text-dark">Power {{ $downlineRow['profile_power']['score'] }}/100</span>
                                <span class="badge {{ $powerBadgeClasses[$downlineRow['profile_power']['rank_accent']] ?? 'bg-primary-subtle text-primary' }}">{{ $downlineRow['profile_power']['rank_label'] }}</span>
                                @foreach (($downlineRow['reward_cap_badges'] ?? []) as $rewardCapBadge)
                                  <span class="badge {{ $rewardCapBadge['class'] }}">{{ $rewardCapBadge['label'] }}</span>
                                @endforeach
                              </div>
                            </div>
                          </div>
                        @endforeach
                      </div>
                    @endif
                  </div>
                </div>
              </div>
            @endforeach
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
            <h5 class="mb-1">Reward ledger</h5>
            <p class="text-secondary mb-0">Registration, subscription, and team bonuses paid into your wallet.</p>
          </div>
          <span class="badge bg-success">{{ $referralRewards->count() }} rewards in {{ $rewardFilters[$rewardFilter] }}</span>
        </div>
        <div class="row g-3 mb-4">
          @foreach ($rewardBreakdown as $breakdownKey => $breakdown)
            <div class="col-md-6">
              <div class="border rounded p-3 h-100 {{ $rewardFilter === $breakdownKey ? 'border-primary bg-primary-subtle' : 'bg-light' }}">
                <div class="text-secondary small">{{ $breakdown['label'] }}</div>
                <div class="fw-semibold fs-4">${{ number_format((float) $breakdown['amount'], 2) }}</div>
                <div class="text-secondary small">{{ $breakdown['count'] }} entries</div>
              </div>
            </div>
          @endforeach
        </div>
        <div class="d-flex flex-wrap gap-2 mb-3">
          @foreach ($rewardFilters as $filterKey => $filterLabel)
            <a href="{{ route('dashboard.network', ['reward_filter' => $filterKey, 'pipeline_filter' => $pipelineFilter]) }}" class="btn btn-sm {{ $rewardFilter === $filterKey ? 'btn-primary' : 'btn-outline-primary' }}">{{ $filterLabel }}</a>
          @endforeach
        </div>
        @if ($referralRewards->isEmpty())
          <p class="text-secondary mb-0">No team rewards yet. As your branch grows, rewards will start appearing here.</p>
        @else
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Reward level</th>
                  <th>Source</th>
                  <th>Status</th>
                  <th>Amount</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($referralRewards as $reward)
                  @php($rewardLevel = str($formatRewardSource($reward->source))->startsWith('Level ') ? str($formatRewardSource($reward->source))->before(' Team Bonus') : 'Direct')
                  <tr>
                    <td>{{ $reward->earned_on?->format('M d, Y') }}</td>
                    <td><span class="badge bg-light text-dark">{{ $rewardLevel }}</span></td>
                    <td>
                      <div class="fw-semibold">{{ $formatRewardSource($reward->source) }}</div>
                      <div class="text-secondary small">{{ $reward->notes ?: '—' }}</div>
                    </td>
                    <td><span class="badge {{ $reward->status === 'available' ? 'bg-success' : 'bg-secondary' }}">{{ str($reward->status)->replace('_', ' ')->title() }}</span></td>
                    <td>${{ number_format((float) $reward->amount, 2) }}</td>
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
                  <th>Level</th>
                  <th>Event</th>
                  <th>Member</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($teamEvents as $event)
                  <tr>
                    <td>{{ $event->created_at?->format('M d, Y h:i A') }}</td>
                    <td><span class="badge bg-light text-dark">{{ $formatEventDepth($event->type, $event->title) }}</span></td>
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
          <span class="badge bg-primary">{{ $friendInvitations->count() }} contacts in {{ $pipelineFilters[$pipelineFilter] }}</span>
        </div>
        <div class="row g-3 mb-4">
          <div class="col-md-3"><div class="border rounded p-3 h-100 {{ $pipelineFilter === 'verified' ? 'border-primary bg-primary-subtle' : 'bg-light' }}"><div class="text-secondary small">Verified</div><div class="fw-semibold fs-4">{{ $pipelineBreakdown['verified'] }}</div></div></div>
          <div class="col-md-3"><div class="border rounded p-3 h-100 {{ $pipelineFilter === 'registered' ? 'border-primary bg-primary-subtle' : 'bg-light' }}"><div class="text-secondary small">Registered</div><div class="fw-semibold fs-4">{{ $pipelineBreakdown['registered'] }}</div></div></div>
          <div class="col-md-3"><div class="border rounded p-3 h-100 {{ $pipelineFilter === 'active_investor' ? 'border-primary bg-primary-subtle' : 'bg-light' }}"><div class="text-secondary small">Active investors</div><div class="fw-semibold fs-4">{{ $pipelineBreakdown['active_investor'] }}</div></div></div>
          <div class="col-md-3"><div class="border rounded p-3 h-100 {{ $pipelineFilter === 'pending' ? 'border-primary bg-primary-subtle' : 'bg-light' }}"><div class="text-secondary small">Pending</div><div class="fw-semibold fs-4">{{ $pipelineBreakdown['pending'] }}</div></div></div>
        </div>
        <div class="d-flex flex-wrap gap-2 mb-3">
          @foreach ($pipelineFilters as $filterKey => $filterLabel)
            <a href="{{ route('dashboard.network', ['reward_filter' => $rewardFilter, 'pipeline_filter' => $filterKey]) }}" class="btn btn-sm {{ $pipelineFilter === $filterKey ? 'btn-primary' : 'btn-outline-primary' }}">{{ $filterLabel }}</a>
          @endforeach
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
                  @php($isActiveInvestor = $activeInvestorEmails->contains($friendInvitation->email))
                  <tr>
                    <td>{{ $friendInvitation->name }}</td>
                    <td>{{ $friendInvitation->email }}</td>
                    <td><span class="badge {{ $friendInvitation->verified_at ? 'bg-info' : 'bg-secondary' }}">{{ $friendInvitation->verified_at ? 'Yes' : 'No' }}</span></td>
                    <td><span class="badge {{ $friendInvitation->registered_at ? 'bg-success' : 'bg-secondary' }}">{{ $friendInvitation->registered_at ? 'Yes' : 'No' }}</span></td>
                    <td><span class="badge {{ $isActiveInvestor ? 'bg-primary' : 'bg-secondary' }}">{{ $isActiveInvestor ? 'Yes' : 'No' }}</span></td>
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


