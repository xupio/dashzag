@extends('layout.master')

@push('plugin-scripts')
  <script src="{{ asset('build/plugins/apexcharts/apexcharts.min.js') }}"></script>
@endpush

@section('content')
<div class="row">
  <div class="col-12 grid-margin">
    <div class="card overflow-hidden">
      <div class="position-relative" style="background: linear-gradient(135deg, #eef3ff 0%, #dfe9ff 100%); min-height: 220px;">
        <div class="position-absolute top-0 start-0 w-100 h-100" style="background: radial-gradient(circle at top right, rgba(39, 70, 144, 0.14), transparent 38%), radial-gradient(circle at bottom left, rgba(101, 113, 255, 0.16), transparent 42%);"></div>
        <div class="position-relative p-4 p-md-5 d-flex justify-content-between align-items-end flex-wrap gap-4" style="min-height: 220px;">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center border border-3 border-white shadow-sm text-white fw-bold" style="width: 84px; height: 84px; background: linear-gradient(135deg, #274690 0%, #6571ff 100%); font-size: 28px;">
              {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div>
              <div class="text-uppercase text-secondary small mb-2">Personal Profile</div>
              <h2 class="mb-1 text-dark">{{ $user->name }}</h2>
              <div class="text-secondary">{{ $user->email }}</div>
              <div class="d-flex gap-3 flex-wrap mt-3 text-secondary small">
                <span><i data-lucide="mail-check" class="icon-sm me-1"></i>{{ $user->hasVerifiedEmail() ? 'Verified email' : 'Verification pending' }}</span>
                <span><i data-lucide="badge-dollar-sign" class="icon-sm me-1"></i>Current level: {{ $displayTierName }}</span>
                <span><i data-lucide="calendar-days" class="icon-sm me-1"></i>Member since {{ optional($user->created_at)->format('M d, Y') }}</span>
              </div>
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
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-12">
    <div class="card border-0 shadow-sm overflow-hidden">
      <div class="card-body p-0">
        <div class="row g-0">
          <div class="col-xl-4">
            <div class="h-100 p-4 p-lg-5 text-white" style="background: linear-gradient(135deg, #274690 0%, #6571ff 100%);">
              <div class="text-uppercase small opacity-75 mb-2">Profile power</div>
              <div class="d-flex align-items-end gap-3 mb-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-white bg-opacity-10 border border-white border-opacity-25" style="width: 58px; height: 58px;">
                  <i data-lucide="{{ $profilePower['rank_icon'] }}" class="icon-lg"></i>
                </div>
                <div class="display-4 fw-bold mb-0">{{ $profilePower['score'] }}</div>
                <div class="pb-2">/ 100</div>
              </div>
              <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
                <span class="badge bg-white text-dark">{{ $profilePower['rank_label'] }}</span>
                <span class="small opacity-75">{{ $profilePower['active_direct_investors'] }} active team investors</span>
              </div>
              <p class="mb-4 opacity-75">Your profile becomes stronger as you verify more invites, convert more direct investors, and build a stronger mining account.</p>
              <div class="small opacity-75 mb-1">Next rank target</div>
              <div class="fw-semibold mb-2">
                {{ $profilePower['next_rank_label'] }}
                @if ($profilePower['points_to_next_rank'] > 0)
                  <span class="fw-normal opacity-75">- {{ $profilePower['points_to_next_rank'] }} points to go</span>
                @endif
              </div>
              <div class="progress bg-white bg-opacity-25" style="height: 8px;">
                <div class="progress-bar bg-white" role="progressbar" style="width: {{ $profilePower['progress_within_rank'] }}%"></div>
              </div>
            </div>
          </div>
          <div class="col-xl-8">
            <div class="p-4 p-lg-5">
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
                <div>
                  <h5 class="mb-1">Power components</h5>
                  <p class="text-secondary mb-0">These are the exact pieces that strengthen your account profile inside ZagChain.</p>
                </div>
                <a href="{{ route('dashboard.network') }}" class="btn btn-outline-primary btn-sm">Grow network power</a>
              </div>
              <div class="row g-3 mb-4">
                <div class="col-md-4">
                  <div class="border rounded p-3 h-100 bg-light">
                    <div class="text-secondary small mb-1">Verified invites</div>
                    <div class="fw-semibold fs-4">{{ $profilePower['verified_invites'] }}</div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="border rounded p-3 h-100 bg-light">
                    <div class="text-secondary small mb-1">Registered referrals</div>
                    <div class="fw-semibold fs-4">{{ $profilePower['registered_referrals'] }}</div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="border rounded p-3 h-100 bg-light">
                    <div class="text-secondary small mb-1">Active packages</div>
                    <div class="fw-semibold fs-4">{{ $profilePower['active_package_count'] }}</div>
                  </div>
                </div>
              </div>
              <div class="d-flex flex-column gap-3">
                @foreach ($profilePower['components'] as $component)
                  <div class="border rounded p-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                      <div>
                        <div class="fw-semibold">{{ $component['label'] }}</div>
                        <div class="text-secondary small">{{ $component['description'] }}</div>
                      </div>
                      <div class="text-end">
                        <div class="fw-semibold">{{ $component['display'] }}</div>
                        <div class="text-secondary small">+{{ $component['value'] }} pts</div>
                      </div>
                    </div>
                    <div class="progress" style="height: 8px;">
                      <div class="progress-bar bg-primary" role="progressbar" style="width: {{ min(($component['value'] / 30) * 100, 100) }}%"></div>
                    </div>
                  </div>
                @endforeach
              </div>
              <div class="row g-3 mt-1">
                <div class="col-lg-6">
                  <div class="border rounded p-3 h-100">
                    <div class="d-flex align-items-center gap-2 mb-2">
                      <i data-lucide="{{ $profilePower['rank_icon'] }}" class="icon-md text-primary"></i>
                      <h6 class="mb-0">Achievement badges</h6>
                    </div>
                    <p class="text-secondary small mb-3">Visible wins that show how strong your account journey has become.</p>
                    <div class="d-flex flex-column gap-2">
                      @foreach ($profilePower['achievements'] as $achievement)
                        <div class="d-flex align-items-start justify-content-between gap-3 border rounded p-3 {{ $achievement['unlocked'] ? 'bg-success bg-opacity-10 border-success-subtle' : 'bg-light' }}">
                          <div class="d-flex align-items-start gap-2">
                            <div class="rounded-circle d-flex align-items-center justify-content-center {{ $achievement['unlocked'] ? 'bg-success text-white' : 'bg-white text-secondary border' }}" style="width: 38px; height: 38px;">
                              <i data-lucide="{{ $achievement['icon'] }}" class="icon-sm"></i>
                            </div>
                            <div>
                              <div class="fw-semibold">{{ $achievement['title'] }}</div>
                              <div class="text-secondary small">{{ $achievement['description'] }}</div>
                            </div>
                          </div>
                          <span class="badge {{ $achievement['unlocked'] ? 'bg-success' : 'bg-light text-dark' }}">{{ $achievement['unlocked'] ? 'Unlocked' : 'Locked' }}</span>
                        </div>
                      @endforeach
                    </div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="border rounded p-3 h-100">
                    <h6 class="mb-1">Milestone unlocks</h6>
                    <p class="text-secondary small mb-3">Track the next checkpoints that change your account strength.</p>
                    <div class="d-flex flex-column gap-2">
                      @foreach ($profilePower['milestones'] as $milestone)
                        @php
                          $milestoneBadgeClasses = match ($milestone['status']) {
                              'completed' => 'bg-success',
                              'ready' => 'bg-info',
                              'in_progress' => 'bg-warning text-dark',
                              default => 'bg-light text-dark',
                          };
                          $milestoneLabel = match ($milestone['status']) {
                              'completed' => 'Completed',
                              'ready' => 'Ready',
                              'in_progress' => 'In progress',
                              default => 'Locked',
                          };
                        @endphp
                        <div class="border rounded p-3">
                          <div class="d-flex align-items-start justify-content-between gap-3 mb-1">
                            <div>
                              <div class="fw-semibold">{{ $milestone['title'] }}</div>
                              <div class="text-secondary small">{{ $milestone['description'] }}</div>
                            </div>
                            <span class="badge {{ $milestoneBadgeClasses }}">{{ $milestoneLabel }}</span>
                          </div>
                          @if (!empty($milestone['current']))
                            <div class="text-secondary small">{{ $milestone['current'] }}</div>
                          @endif
                        </div>
                      @endforeach
                    </div>
                  </div>
                </div>
              </div>
              <div class="border rounded p-3 mt-3">
                <h6 class="mb-1">How to gain power faster</h6>
                <p class="text-secondary small mb-3">These are the next best actions to strengthen your rank as fast as possible.</p>
                <div class="row g-3">
                  @foreach ($profilePower['recommended_actions'] as $action)
                    <div class="col-lg-6">
                      <div class="border rounded p-3 h-100 bg-light">
                        <div class="d-flex align-items-start gap-3">
                          <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary text-white" style="width: 40px; height: 40px;">
                            <i data-lucide="{{ $action['icon'] }}" class="icon-sm"></i>
                          </div>
                          <div class="flex-grow-1">
                            <div class="fw-semibold mb-1">{{ $action['title'] }}</div>
                            <div class="text-secondary small mb-2">{{ $action['description'] }}</div>
                            <div class="small fw-semibold mb-3">Target: {{ $action['target'] }}</div>
                            <a href="{{ $action['route'] }}" class="btn btn-sm btn-outline-primary">{{ $action['route_label'] }}</a>
                          </div>
                        </div>
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>
              <div class="row g-3 mt-1">
                <div class="col-lg-5">
                  <div class="border rounded p-3 h-100">
                    <h6 class="mb-1">Rank perks</h6>
                    <p class="text-secondary small mb-3">Your current rank already gives you these visible advantages.</p>
                    <div class="d-flex flex-column gap-2">
                      @foreach ($profilePower['rank_perks'] as $perk)
                        <div class="d-flex align-items-start gap-2">
                          <i data-lucide="shield-check" class="icon-sm text-success mt-1"></i>
                          <div class="small">{{ $perk }}</div>
                        </div>
                      @endforeach
                    </div>
                  </div>
                </div>
                <div class="col-lg-7">
                  <div class="border rounded p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                      <div>
                        <h6 class="mb-1">Power leaderboard</h6>
                        <p class="text-secondary small mb-0">See where your account stands among the strongest verified profiles.</p>
                      </div>
                      @if ($leaderboardPosition)
                        <span class="badge bg-primary">Your position: #{{ $leaderboardPosition }}</span>
                      @endif
                    </div>
                    <div class="d-flex flex-column gap-2">
                      @foreach ($profilePowerLeaderboard as $leaderRow)
                        <div class="d-flex justify-content-between align-items-center gap-3 border rounded p-3 {{ $leaderRow['user']->is($user) ? 'border-primary bg-primary-subtle' : 'bg-light' }}">
                          <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-semibold {{ $leaderRow['user']->is($user) ? 'bg-primary text-white' : 'bg-white border text-dark' }}" style="width: 36px; height: 36px;">
                              {{ $loop->iteration }}
                            </div>
                            <div>
                              <div class="fw-semibold">{{ $leaderRow['user']->name }}{{ $leaderRow['user']->is($user) ? ' (You)' : '' }}</div>
                              <div class="text-secondary small">{{ $leaderRow['summary']['rank_label'] }}</div>
                            </div>
                          </div>
                          <div class="text-end">
                            <div class="fw-semibold">{{ $leaderRow['summary']['score'] }}/100</div>
                            <div class="text-secondary small">{{ $leaderRow['summary']['verified_invites'] }} verified invites</div>
                          </div>
                        </div>
                      @endforeach
                    </div>
                  </div>
                </div>
              </div>
              <div class="border rounded p-3 mt-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                  <div>
                    <h6 class="mb-1">Weekly momentum</h6>
                    <p class="text-secondary small mb-0">Your current movement over the last 7 days.</p>
                  </div>
                  <span class="badge bg-info">Momentum {{ $weeklyMomentum['score'] }}/100</span>
                </div>
                <div class="row g-3">
                  <div class="col-md-3">
                    <div class="border rounded p-3 h-100 bg-light">
                      <div class="text-secondary small mb-1">Verified this week</div>
                      <div class="fw-semibold fs-4">{{ $weeklyMomentum['verified_invites'] }}</div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="border rounded p-3 h-100 bg-light">
                      <div class="text-secondary small mb-1">Registered this week</div>
                      <div class="fw-semibold fs-4">{{ $weeklyMomentum['registered_referrals'] }}</div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="border rounded p-3 h-100 bg-light">
                      <div class="text-secondary small mb-1">New active investors</div>
                      <div class="fw-semibold fs-4">{{ $weeklyMomentum['new_active_direct_investors'] }}</div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="border rounded p-3 h-100 bg-light">
                      <div class="text-secondary small mb-1">Mining streak</div>
                      <div class="fw-semibold fs-4">{{ $weeklyMomentum['streak_days'] }} day{{ $weeklyMomentum['streak_days'] === 1 ? '' : 's' }}</div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="row g-3 mt-1">
                <div class="col-lg-5">
                  <div class="border rounded p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                      <div>
                        <h6 class="mb-1">Monthly champion push</h6>
                        <p class="text-secondary small mb-0">Your broader month-to-date momentum across team and mining activity.</p>
                      </div>
                      <span class="badge bg-warning text-dark">{{ $monthlyMomentum['score'] }}/100</span>
                    </div>
                    <div class="d-flex flex-column gap-2">
                      <div class="small"><span class="fw-semibold">{{ $monthlyMomentum['verified_invites'] }}</span> verified invites this month</div>
                      <div class="small"><span class="fw-semibold">{{ $monthlyMomentum['registered_referrals'] }}</span> registered referrals this month</div>
                      <div class="small"><span class="fw-semibold">{{ $monthlyMomentum['new_active_direct_investors'] }}</span> new active direct investors this month</div>
                      <div class="small"><span class="fw-semibold">${{ number_format($monthlyMomentum['mining_income'], 2) }}</span> miner daily-share income this month</div>
                    </div>
                  </div>
                </div>
                <div class="col-lg-7">
                  <div class="border rounded p-3 h-100">
                    <h6 class="mb-1">Recent weekly history</h6>
                    <p class="text-secondary small mb-3">Rolling weekly history resets automatically as each new week begins.</p>
                    <div class="d-flex flex-column gap-2">
                      @foreach ($weeklyMomentumHistory as $historyRow)
                        <div class="d-flex justify-content-between align-items-center gap-3 border rounded p-3 {{ $loop->first ? 'bg-info-subtle border-info-subtle' : 'bg-light' }}">
                          <div>
                            <div class="fw-semibold">Week of {{ $historyRow['week_label'] }}</div>
                            <div class="text-secondary small">{{ $historyRow['verified_invites'] }} verified · {{ $historyRow['registered_referrals'] }} registered · {{ $historyRow['new_active_direct_investors'] }} investors</div>
                          </div>
                          <div class="text-end">
                            <div class="fw-semibold">{{ $historyRow['score'] }}/100</div>
                            <div class="text-secondary small">${{ number_format($historyRow['mining_income'], 2) }}</div>
                          </div>
                        </div>
                      @endforeach
                    </div>
                  </div>
                </div>
              </div>
              <div class="border rounded p-3 mt-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                  <div>
                    <h6 class="mb-1">Champion wins</h6>
                    <p class="text-secondary small mb-0">Your saved Hall of Fame victories across weekly momentum and monthly champion runs.</p>
                  </div>
                  <div class="d-flex gap-2 flex-wrap">
                    <span class="badge bg-success">Weekly wins: {{ $hallOfFameWinCounts['weekly'] }}</span>
                    <span class="badge bg-warning text-dark">Monthly wins: {{ $hallOfFameWinCounts['monthly'] }}</span>
                  </div>
                </div>
                @if ($recentHallOfFameWins->isEmpty())
                  <div class="text-secondary small">Your Hall of Fame wins will appear here as soon as you land in the weekly or monthly top spot.</div>
                @else
                  <div class="row g-3">
                    @foreach ($recentHallOfFameWins as $win)
                      @php($isMonthlyWin = ($win->data['event_key'] ?? null) === 'hall_of_fame_monthly_winner')
                      <div class="col-lg-6">
                        <div class="border rounded p-3 h-100 {{ $isMonthlyWin ? 'bg-warning bg-opacity-10 border-warning-subtle' : 'bg-success bg-opacity-10 border-success-subtle' }}">
                          <div class="d-flex align-items-start gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center {{ $isMonthlyWin ? 'bg-warning text-dark' : 'bg-success text-white' }}" style="width: 42px; height: 42px;">
                              <i data-lucide="{{ $win->data['rank_icon'] ?? 'trophy' }}" class="icon-sm"></i>
                            </div>
                            <div>
                              <div class="fw-semibold">{{ $win->data['subject'] ?? 'Champion win' }}</div>
                              <div class="text-secondary small mb-2">{{ $win->data['message'] ?? '' }}</div>
                              <div class="small">{{ $win->data['context_value'] ?? '' }}</div>
                              <div class="small fw-semibold mt-2">{{ $win->created_at?->format('M d, Y') }}</div>
                            </div>
                          </div>
                        </div>
                      </div>
                    @endforeach
                  </div>
                @endif
              </div>
              <div class="border rounded p-3 mt-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                  <div>
                    <h6 class="mb-1">Recent celebrations</h6>
                    <p class="text-secondary small mb-0">Your latest profile-rank wins and milestone moments.</p>
                  </div>
                  <span class="badge bg-light text-dark">{{ $recentRankCelebrations->count() }} recent</span>
                </div>
                @if ($recentRankCelebrations->isEmpty())
                  <div class="text-secondary small">Your next rank celebration will appear here when your profile power crosses a new tier.</div>
                @else
                  <div class="row g-3">
                    @foreach ($recentRankCelebrations as $celebration)
                      <div class="col-lg-4">
                        <div class="border rounded p-3 h-100 bg-success bg-opacity-10 border-success-subtle">
                          <div class="d-flex align-items-start gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center bg-success text-white" style="width: 42px; height: 42px;">
                              <i data-lucide="{{ $celebration->data['rank_icon'] ?? 'award' }}" class="icon-sm"></i>
                            </div>
                            <div>
                              <div class="fw-semibold">{{ $celebration->data['subject'] ?? 'New profile rank unlocked' }}</div>
                              <div class="text-secondary small mb-2">{{ $celebration->data['message'] ?? '' }}</div>
                              <div class="small fw-semibold">{{ $celebration->created_at?->format('M d, Y') }}</div>
                            </div>
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
      </div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="text-secondary small mb-1">Total invested</div>
        <h3 class="mb-1">${{ number_format($totalInvested, 2) }}</h3>
        <div class="text-secondary small">Across all active mining packages</div>
      </div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="text-secondary small mb-1">Expected monthly earnings</div>
        <h3 class="mb-1">${{ number_format($expectedMonthlyEarnings, 2) }}</h3>
        <div class="text-secondary small">Projected from your active investments</div>
      </div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="text-secondary small mb-1">Wallet balance</div>
        <h3 class="mb-1">${{ number_format($availableEarnings, 2) }}</h3>
        <div class="text-secondary small">Available in your earnings wallet</div>
      </div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="text-secondary small mb-1">Team bonus rate</div>
        <h3 class="mb-1">{{ number_format((float) $teamBonusRate * 100, 2) }}%</h3>
        <div class="text-secondary small">Improves returns as your network grows</div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12 grid-margin stretch-card">
    <div class="card rounded w-100">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
          <div>
            <h6 class="card-title mb-1">Visual summary</h6>
            <p class="text-secondary mb-0">A simpler view of your personal investment mix and account balances.</p>
          </div>
        </div>
        <div class="row g-3">
          <div class="col-xl-6">
            <div class="border rounded p-3 h-100">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                  <div class="fw-semibold">Investment allocation</div>
                  <div class="text-secondary small">How your active capital is distributed across miners.</div>
                </div>
                <span class="badge bg-light text-dark">{{ $activeInvestments->count() }} active</span>
              </div>
              <div id="profileInvestmentChart"></div>
            </div>
          </div>
          <div class="col-xl-6">
            <div class="border rounded p-3 h-100">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                  <div class="fw-semibold">Account momentum</div>
                  <div class="text-secondary small">Capital, projected monthly return, and wallet balance.</div>
                </div>
                <span class="badge bg-light text-dark">Personal</span>
              </div>
              <div id="profileFinanceChart"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 grid-margin stretch-card">
    <div class="card rounded w-100">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
          <div>
            <h6 class="card-title mb-1">Referral pipeline</h6>
            <p class="text-secondary mb-0">Your current invite journey from pending to registered friends.</p>
          </div>
          <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('dashboard.friends') }}" class="btn btn-outline-primary btn-sm">Manage friends</a>
            <a href="{{ route('dashboard.network') }}" class="btn btn-outline-secondary btn-sm">Open network</a>
          </div>
        </div>
        <div class="border rounded p-3">
          <div id="profileReferralChart"></div>
        </div>
      </div>
    </div>
  </div>

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
              <p class="text-secondary mb-1">Current level</p>
              <div class="fw-semibold mb-2">{{ $displayTierName }}</div>
              <div class="text-secondary small">Base bonus: {{ number_format((float) $level->bonus_rate * 100, 2) }}%.</div>
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
@endsection

@push('custom-scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (!window.ApexCharts) {
        return;
      }

      const investmentElement = document.querySelector('#profileInvestmentChart');
      if (investmentElement) {
        new ApexCharts(investmentElement, {
          chart: { type: 'donut', height: 280, toolbar: { show: false } },
          labels: @json($profileInvestmentLabels),
          series: @json($profileInvestmentSeries),
          colors: ['#6571ff', '#05a34a', '#00acc1', '#fbbc06', '#ff3366'],
          dataLabels: { enabled: false },
          legend: { position: 'bottom' },
          stroke: { width: 0 },
          plotOptions: { pie: { donut: { size: '64%', labels: { show: false } } } },
          tooltip: {
            y: { formatter: function (value) { return '$' + Number(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); } }
          }
        }).render();
      }

      const financeElement = document.querySelector('#profileFinanceChart');
      if (financeElement) {
        new ApexCharts(financeElement, {
          chart: { type: 'bar', height: 280, toolbar: { show: false } },
          series: [{ name: 'USD', data: @json($profileFinanceSeries) }],
          xaxis: { categories: @json($profileFinanceLabels) },
          colors: ['#274690'],
          dataLabels: { enabled: false },
          plotOptions: { bar: { borderRadius: 8, columnWidth: '48%' } },
          tooltip: {
            y: { formatter: function (value) { return '$' + Number(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); } }
          }
        }).render();
      }

      const referralElement = document.querySelector('#profileReferralChart');
      if (referralElement) {
        new ApexCharts(referralElement, {
          chart: { type: 'area', height: 260, toolbar: { show: false } },
          series: [{ name: 'Contacts', data: @json($profileReferralSeries) }],
          xaxis: { categories: @json($profileReferralLabels) },
          colors: ['#05a34a'],
          dataLabels: { enabled: false },
          stroke: { curve: 'smooth', width: 3 },
          fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.05 } }
        }).render();
      }
    });
  </script>
@endpush

