@once
  <style>
    .network-tree-panel {
      border-radius: 1.35rem;
      background: radial-gradient(circle at top, rgba(101, 113, 255, 0.18), rgba(23, 18, 61, 0.98) 52%, rgba(12, 10, 37, 1) 100%);
      border: 1px solid rgba(129, 140, 248, 0.2);
      padding: 1.25rem;
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05), 0 16px 40px rgba(15, 23, 42, 0.2);
    }

    .network-tree-panel .network-tree-legend {
      display: flex;
      gap: 0.75rem;
      flex-wrap: wrap;
      margin-bottom: 1rem;
    }

    .network-tree-panel .network-tree-legend-item {
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: rgba(255, 255, 255, 0.92);
      font-size: 0.75rem;
      padding: 0.45rem 0.75rem;
    }

    .network-tree-panel .network-tree-legend-dot {
      width: 0.7rem;
      height: 0.7rem;
      border-radius: 999px;
      display: inline-block;
    }

    .network-tree-wrap {
      overflow-x: auto;
      padding-bottom: 1rem;
    }

    .network-tree-list {
      list-style: none;
      margin: 0;
      padding-left: 0;
      display: flex;
      flex-direction: column;
      gap: 1rem;
      min-width: max-content;
    }

    .network-tree-children {
      list-style: none;
      margin: 0;
      padding: 2rem 0 0;
      display: flex;
      justify-content: center;
      align-items: flex-start;
      gap: 1.25rem;
      position: relative;
    }

    .network-tree-children::before {
      content: "";
      position: absolute;
      top: 0.8rem;
      left: 50%;
      width: calc(100% - 2.5rem);
      max-width: 100%;
      transform: translateX(-50%);
      border-top: 2px solid rgba(255, 255, 255, 0.18);
    }

    .network-tree-children > li {
      position: relative;
      flex: 1 1 220px;
      max-width: 250px;
      min-width: 220px;
    }

    .network-tree-children > li::before {
      content: "";
      position: absolute;
      top: -1.2rem;
      left: 50%;
      height: 1.2rem;
      border-left: 2px solid rgba(255, 255, 255, 0.18);
      transform: translateX(-50%);
    }

    .network-tree-card {
      position: relative;
      border: 1px solid rgba(255, 255, 255, 0.18);
      border-radius: 1.1rem;
      background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(238, 242, 255, 0.98));
      box-shadow: 0 14px 36px rgba(3, 7, 18, 0.22);
      padding: 1.2rem 1rem 1rem;
      overflow: hidden;
      cursor: pointer;
      text-align: center;
    }

    .network-tree-card::after {
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, transparent 0%, rgba(255, 255, 255, 0.15) 100%);
      pointer-events: none;
    }

    .network-tree-root {
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .network-tree-card.depth-1 {
      background: linear-gradient(180deg, rgba(255, 198, 46, 0.98), rgba(255, 150, 35, 0.98));
      border-color: rgba(255, 205, 57, 0.65);
    }

    .network-tree-card.depth-2 {
      background: linear-gradient(180deg, rgba(255, 102, 102, 0.98), rgba(239, 68, 68, 0.98));
      border-color: rgba(254, 202, 202, 0.4);
    }

    .network-tree-card.depth-3 {
      background: linear-gradient(180deg, rgba(94, 92, 230, 0.98), rgba(79, 70, 229, 0.98));
      border-color: rgba(199, 210, 254, 0.4);
    }

    .network-tree-card.depth-4,
    .network-tree-card.depth-5,
    .network-tree-card.depth-6 {
      background: linear-gradient(180deg, rgba(14, 165, 233, 0.98), rgba(8, 145, 178, 0.98));
      border-color: rgba(165, 243, 252, 0.4);
    }

    .network-tree-card .text-secondary,
    .network-tree-card .network-tree-metric-label {
      color: rgba(255, 255, 255, 0.78) !important;
    }

    .network-tree-card .fw-semibold,
    .network-tree-card .network-tree-metric-value,
    .network-tree-card .badge {
      color: #fff;
    }

    .network-tree-card .badge.bg-light {
      background: rgba(255, 255, 255, 0.18) !important;
      color: #fff !important;
    }

    .network-tree-card .badge.bg-dark,
    .network-tree-card .badge.bg-primary-subtle {
      background: rgba(15, 23, 42, 0.22) !important;
      color: #fff !important;
    }

    .network-tree-card .btn-light {
      background: rgba(255, 255, 255, 0.12);
      border-color: rgba(255, 255, 255, 0.18);
      color: #fff;
    }

    .network-tree-card .btn-outline-primary {
      border-color: rgba(255, 255, 255, 0.55);
      color: #fff;
    }

    .network-tree-card .btn-outline-primary:hover,
    .network-tree-card .btn-light:hover {
      background: rgba(255, 255, 255, 0.18);
      color: #fff;
    }

    .network-tree-meta {
      display: flex;
      justify-content: center;
      gap: 0.75rem;
      flex-wrap: wrap;
    }

    .network-tree-metric {
      border-radius: 999px;
      background: rgba(15, 23, 42, 0.15);
      border: 1px solid rgba(255, 255, 255, 0.14);
      padding: 0.5rem 0.85rem;
      min-width: 88px;
    }

    .network-tree-metric-label {
      display: block;
      color: #64748b;
      font-size: 0.72rem;
      margin-bottom: 0.15rem;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }

    .network-tree-metric-value {
      font-weight: 600;
    }

    .network-tree-avatar {
      width: 3.4rem;
      height: 3.4rem;
      border-radius: 999px;
      margin: 0 auto 0.8rem;
      display: grid;
      place-items: center;
      background: rgba(255, 255, 255, 0.96);
      border: 3px solid rgba(255, 255, 255, 0.5);
      box-shadow: 0 8px 22px rgba(15, 23, 42, 0.18);
      color: #1e293b;
      font-weight: 800;
      font-size: 1rem;
      letter-spacing: 0.03em;
    }

    .network-tree-card.depth-1 .network-tree-avatar { color: #d97706; }
    .network-tree-card.depth-2 .network-tree-avatar { color: #dc2626; }
    .network-tree-card.depth-3 .network-tree-avatar { color: #4338ca; }
    .network-tree-card.depth-4 .network-tree-avatar,
    .network-tree-card.depth-5 .network-tree-avatar,
    .network-tree-card.depth-6 .network-tree-avatar { color: #0f766e; }

    .network-tree-role {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 999px;
      padding: 0.3rem 0.7rem;
      background: rgba(15, 23, 42, 0.2);
      color: rgba(255, 255, 255, 0.92);
      font-size: 0.72rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 0.8rem;
    }

    .network-tree-header-actions {
      justify-content: center;
      margin-top: 0.9rem;
    }

    .network-tree-title {
      font-size: 1rem;
      line-height: 1.25;
      margin-bottom: 0.2rem;
    }

    .network-tree-subline {
      font-size: 0.78rem;
      margin-bottom: 0.75rem;
    }

    @media (max-width: 991.98px) {
      .network-tree-children {
        flex-direction: column;
        align-items: stretch;
        padding-top: 1rem;
      }

      .network-tree-children::before,
      .network-tree-children > li::before {
        display: none;
      }

      .network-tree-children > li {
        max-width: none;
        min-width: 0;
      }
    }
  </style>

  <div class="modal fade" id="networkTreeNodeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header border-0 pb-0">
          <div>
            <p class="text-secondary small mb-1">Investor branch quick view</p>
            <h5 class="modal-title mb-0" id="networkTreeNodeModalTitle">Investor</h5>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body pt-3">
          <div class="d-flex gap-2 flex-wrap mb-3">
            <span class="badge bg-primary" data-tree-modal-rank>Rank</span>
            <span class="badge bg-light text-dark" data-tree-modal-level>Level</span>
            <span class="badge bg-dark" data-tree-modal-priority>Priority</span>
          </div>
          <p class="mb-3 text-secondary" data-tree-modal-description></p>
          <div class="row g-3">
            <div class="col-6">
              <div class="border rounded p-3 h-100 bg-light">
                <div class="text-secondary small">Sponsor</div>
                <div class="fw-semibold" data-tree-modal-sponsor></div>
              </div>
            </div>
            <div class="col-6">
              <div class="border rounded p-3 h-100 bg-light">
                <div class="text-secondary small">Power</div>
                <div class="fw-semibold" data-tree-modal-power></div>
              </div>
            </div>
            <div class="col-6">
              <div class="border rounded p-3 h-100 bg-light">
                <div class="text-secondary small">Direct team</div>
                <div class="fw-semibold" data-tree-modal-team></div>
              </div>
            </div>
            <div class="col-6">
              <div class="border rounded p-3 h-100 bg-light">
                <div class="text-secondary small">Active direct investors</div>
                <div class="fw-semibold" data-tree-modal-active-direct></div>
              </div>
            </div>
            <div class="col-6">
              <div class="border rounded p-3 h-100 bg-light">
                <div class="text-secondary small">Active capital</div>
                <div class="fw-semibold" data-tree-modal-capital></div>
              </div>
            </div>
            <div class="col-6">
              <div class="border rounded p-3 h-100 bg-light">
                <div class="text-secondary small">Verified invites</div>
                <div class="fw-semibold" data-tree-modal-invites></div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <a href="#" class="btn btn-primary" data-tree-modal-profile-link>Open full profile</a>
        </div>
      </div>
    </div>
  </div>
@endonce

<li class="{{ $node['depth'] === 1 ? 'network-tree-root' : '' }}">
  @php
    $treeNodeId = 'networkTreeNode-'.$node['user']->id.'-'.$node['depth'];
  @endphp
  <div
    class="network-tree-card depth-{{ $node['depth'] }}"
    data-tree-node
    data-tree-name="{{ $node['user']->name }}"
    data-tree-rank="{{ $node['power_summary']['rank'] }}"
    data-tree-level="{{ $node['level_name'] }}"
    data-tree-priority="{{ $node['situation']['priority'] }}"
    data-tree-description="{{ $node['situation']['description'] }}"
    data-tree-sponsor="{{ $node['sponsor_name'] }}"
    data-tree-power="{{ $node['power_summary']['score'] }}/100"
    data-tree-team="{{ $node['direct_team'] }}"
    data-tree-active-direct="{{ $node['active_direct_investors'] }}"
    data-tree-capital="${{ number_format($node['active_capital'], 2) }}"
    data-tree-invites="{{ $node['verified_invites'] }}"
    data-tree-profile="{{ route('dashboard.investors.show', ['user' => $node['user'], 'from' => request()->routeIs('dashboard.network-admin') ? 'network-admin' : 'network']) }}"
  >
    <div class="network-tree-avatar">
      {{ str($node['user']->name)->explode(' ')->take(2)->map(fn ($part) => str($part)->substr(0, 1))->join('') }}
    </div>

    <div class="network-tree-title fw-semibold">{{ $node['user']->name }}</div>
    <div class="text-secondary network-tree-subline">{{ $node['user']->email }}</div>

    <div class="network-tree-role">
      {{ $node['situation']['label'] }}
    </div>

    <div class="network-tree-meta">
      <div class="network-tree-metric">
        <span class="network-tree-metric-label">Power</span>
        <span class="network-tree-metric-value">{{ $node['power_summary']['score'] }}/100</span>
      </div>
      <div class="network-tree-metric">
        <span class="network-tree-metric-label">Team</span>
        <span class="network-tree-metric-value">{{ $node['direct_team'] }}</span>
      </div>
      <div class="network-tree-metric">
        <span class="network-tree-metric-label">Capital</span>
        <span class="network-tree-metric-value">${{ number_format($node['active_capital'], 0) }}</span>
      </div>
    </div>

    <div class="d-flex gap-2 flex-wrap network-tree-header-actions">
      @if ($node['children']->isNotEmpty())
        <button class="btn btn-light btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $treeNodeId }}" aria-expanded="true" aria-controls="{{ $treeNodeId }}" onclick="event.stopPropagation();">
          Branch
        </button>
      @endif
      <a href="{{ route('dashboard.investors.show', ['user' => $node['user'], 'from' => request()->routeIs('dashboard.network-admin') ? 'network-admin' : 'network']) }}" class="btn btn-outline-primary btn-sm" onclick="event.stopPropagation();">
        Profile
      </a>
    </div>
  </div>

  @if ($node['children']->isNotEmpty())
    <div class="collapse show" id="{{ $treeNodeId }}">
      <ul class="network-tree-children">
        @foreach ($node['children'] as $childNode)
          @include('pages.general.partials.network-tree-node', ['node' => $childNode])
        @endforeach
      </ul>
    </div>
  @endif
</li>

@once
  @push('custom-scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const modalElement = document.getElementById('networkTreeNodeModal');
      if (!modalElement || typeof bootstrap === 'undefined') {
        return;
      }

      const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
      const title = modalElement.querySelector('[id="networkTreeNodeModalTitle"]');
      const rank = modalElement.querySelector('[data-tree-modal-rank]');
      const level = modalElement.querySelector('[data-tree-modal-level]');
      const priority = modalElement.querySelector('[data-tree-modal-priority]');
      const description = modalElement.querySelector('[data-tree-modal-description]');
      const sponsor = modalElement.querySelector('[data-tree-modal-sponsor]');
      const power = modalElement.querySelector('[data-tree-modal-power]');
      const team = modalElement.querySelector('[data-tree-modal-team]');
      const activeDirect = modalElement.querySelector('[data-tree-modal-active-direct]');
      const capital = modalElement.querySelector('[data-tree-modal-capital]');
      const invites = modalElement.querySelector('[data-tree-modal-invites]');
      const profileLink = modalElement.querySelector('[data-tree-modal-profile-link]');

      document.querySelectorAll('[data-tree-node]').forEach(function (node) {
        node.addEventListener('click', function () {
          title.textContent = node.dataset.treeName || 'Investor';
          rank.textContent = node.dataset.treeRank || 'Rank';
          level.textContent = node.dataset.treeLevel || 'Level';
          priority.textContent = node.dataset.treePriority || 'Priority';
          description.textContent = node.dataset.treeDescription || '';
          sponsor.textContent = node.dataset.treeSponsor || 'Top-level';
          power.textContent = node.dataset.treePower || '0/100';
          team.textContent = node.dataset.treeTeam || '0';
          activeDirect.textContent = node.dataset.treeActiveDirect || '0';
          capital.textContent = node.dataset.treeCapital || '$0.00';
          invites.textContent = node.dataset.treeInvites || '0';
          profileLink.href = node.dataset.treeProfile || '#';

          modal.show();
        });
      });
    });
  </script>
  @endpush
@endonce
