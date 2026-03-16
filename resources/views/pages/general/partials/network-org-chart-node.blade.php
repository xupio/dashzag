@php
  $initials = collect(explode(' ', trim($node['user']->name)))
      ->filter()
      ->take(2)
      ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
      ->join('');
  $healthSeverity = match ($node['situation']['health']) {
      'Healthy branch' => 'severity-healthy',
      'Needs activation' => 'severity-watch',
      'Invite-heavy, low conversion', 'Capital without depth' => 'severity-risk',
      default => 'severity-early',
  };
@endphp

<li>
  <button
    type="button"
    class="network-org-node depth-{{ min((int) $node['depth'], 6) }}"
    data-network-org-node
    data-name="{{ $node['user']->name }}"
    data-rank="{{ $node['power_summary']['rank'] }}"
    data-level="{{ $node['level_name'] }}"
    data-priority="{{ $node['situation']['priority'] }}"
    data-health="{{ $node['situation']['health'] }}"
    data-description="{{ $node['situation']['description'] }}"
    data-action-hint="{{ $node['situation']['action_hint'] }}"
    data-sponsor="{{ $node['sponsor_name'] }}"
    data-power="{{ $node['power_summary']['score'] }}/100"
    data-team="{{ $node['direct_team'] }}"
    data-active-direct="{{ $node['active_direct_investors'] }}"
    data-capital="${{ number_format($node['active_capital'], 2) }}"
    data-branch-capital="${{ number_format($node['branch_active_capital'], 2) }}"
    data-descendants="{{ $node['visible_descendants'] }}"
    data-branch-investors="{{ $node['branch_active_investors'] }}"
    data-invites="{{ $node['verified_invites'] }}"
    data-profile="{{ route('dashboard.investors.show', ['user' => $node['user'], 'from' => request()->routeIs('dashboard.network-admin') ? 'network-admin' : 'network']) }}"
    data-users-link="{{ route('dashboard.users', ['search' => $node['user']->email]) }}"
    data-shareholders-link="{{ route('dashboard.shareholders', ['search' => $node['user']->email]) }}"
  >
    <span class="network-org-health-dot {{ $healthSeverity }}" aria-hidden="true"></span>
    <div class="network-org-avatar">{{ $initials ?: 'U' }}</div>
    <div class="network-org-name">{{ $node['user']->name }}</div>
    <div class="network-org-role">{{ $node['situation']['label'] }}</div>
    <div class="network-org-mini">
      <span><strong>{{ $node['power_summary']['score'] }}/100</strong> power - {{ $node['direct_team'] }} team</span>
    </div>
  </button>

  @if ($node['children']->isNotEmpty())
    <ul>
      @foreach ($node['children'] as $childNode)
        @include('pages.general.partials.network-org-chart-node', ['node' => $childNode])
      @endforeach
    </ul>
  @endif
</li>
