@php
  $chartTitle = $chartTitle ?? 'Sponsor tree';
  $chartDescription = $chartDescription ?? 'Interactive sponsor tree';
  $tree = $tree ?? collect();
@endphp

@once
  <style>
    .network-org-card {
      border-radius: 1.5rem;
      background: radial-gradient(circle at top, rgba(118, 129, 255, 0.28), rgba(27, 21, 72, 0.96) 42%, rgba(10, 10, 31, 1) 100%);
      border: 1px solid rgba(129, 140, 248, 0.22);
      padding: 1.25rem;
      box-shadow: 0 22px 44px rgba(15, 23, 42, 0.22);
      overflow: hidden;
    }

    .network-org-shell {
      overflow-x: auto;
      padding: 0.5rem 0 1rem;
    }

    .network-org-shell::-webkit-scrollbar {
      height: 8px;
    }

    .network-org-shell::-webkit-scrollbar-thumb {
      background: rgba(255, 255, 255, 0.2);
      border-radius: 999px;
    }

    .network-org-title {
      color: #fff;
      font-size: 1.1rem;
      font-weight: 700;
      margin-bottom: 0.2rem;
      text-align: center;
    }

    .network-org-subtitle {
      color: rgba(255, 255, 255, 0.76);
      font-size: 0.86rem;
      text-align: center;
      margin-bottom: 1.1rem;
    }

    .network-org-legend {
      display: flex;
      justify-content: center;
      gap: 0.7rem;
      flex-wrap: wrap;
      margin-bottom: 1rem;
    }

    .network-org-legend-item {
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      color: rgba(255, 255, 255, 0.9);
      font-size: 0.74rem;
      padding: 0.35rem 0.7rem;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.12);
    }

    .network-org-legend-dot {
      width: 0.7rem;
      height: 0.7rem;
      border-radius: 999px;
      display: inline-block;
    }

    .network-org-tree,
    .network-org-tree ul {
      margin: 0;
      padding: 0;
      list-style: none;
      text-align: center;
    }

    .network-org-tree {
      min-width: max-content;
    }

    .network-org-tree ul {
      position: relative;
      padding-top: 1.7rem;
      display: flex;
      justify-content: center;
      gap: 0.35rem;
    }

    .network-org-tree ul::before {
      content: "";
      position: absolute;
      top: 0.8rem;
      left: 50%;
      width: 2px;
      height: 0.95rem;
      background: rgba(255, 255, 255, 0.22);
      transform: translateX(-50%);
    }

    .network-org-tree li {
      position: relative;
      padding: 0 0.2rem;
      display: inline-flex;
      flex-direction: column;
      align-items: center;
    }

    .network-org-tree li::before,
    .network-org-tree li::after {
      content: "";
      position: absolute;
      top: 0.8rem;
      width: 50%;
      height: 2px;
      background: rgba(255, 255, 255, 0.22);
    }

    .network-org-tree li::before {
      right: 50%;
    }

    .network-org-tree li::after {
      left: 50%;
    }

    .network-org-tree li:only-child::before,
    .network-org-tree li:only-child::after {
      display: none;
    }

    .network-org-tree li:first-child::before,
    .network-org-tree li:last-child::after {
      display: none;
    }

    .network-org-tree > li::before,
    .network-org-tree > li::after {
      display: none;
    }

    .network-org-node {
      width: 136px;
      min-height: 88px;
      border: 0;
      border-radius: 0.85rem;
      padding: 0.72rem 0.58rem 0.58rem;
      color: #fff;
      text-align: center;
      position: relative;
      cursor: pointer;
      box-shadow: 0 16px 30px rgba(8, 8, 25, 0.28);
      transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .network-org-node:hover {
      transform: translateY(-3px);
      box-shadow: 0 20px 36px rgba(8, 8, 25, 0.34);
    }

    .network-org-health-dot {
      position: absolute;
      top: 0.55rem;
      right: 0.55rem;
      width: 0.62rem;
      height: 0.62rem;
      border-radius: 999px;
      border: 2px solid rgba(255, 255, 255, 0.7);
      box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.12);
    }

    .network-org-health-dot.severity-healthy {
      background: #22c55e;
    }

    .network-org-health-dot.severity-watch {
      background: #f59e0b;
    }

    .network-org-health-dot.severity-risk {
      background: #ef4444;
    }

    .network-org-health-dot.severity-early {
      background: #3b82f6;
    }

    .network-org-node.depth-1 {
      background: linear-gradient(180deg, #ffb323, #f58b15);
    }

    .network-org-node.depth-2 {
      background: linear-gradient(180deg, #ff6c73, #ef4444);
    }

    .network-org-node.depth-3 {
      background: linear-gradient(180deg, #6f63ff, #5548e8);
    }

    .network-org-node.depth-4,
    .network-org-node.depth-5,
    .network-org-node.depth-6 {
      background: linear-gradient(180deg, #2fa6d6, #0f8ab7);
    }

    .network-org-avatar {
      width: 2.35rem;
      height: 2.35rem;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.96);
      color: #1f2937;
      font-weight: 800;
      font-size: 0.8rem;
      display: grid;
      place-items: center;
      margin: -1.55rem auto 0.45rem;
      border: 2px solid rgba(255, 255, 255, 0.5);
      box-shadow: 0 6px 14px rgba(15, 23, 42, 0.18);
    }

    .network-org-name {
      font-size: 0.77rem;
      font-weight: 700;
      line-height: 1.2;
      margin-bottom: 0.12rem;
    }

    .network-org-role {
      font-size: 0.6rem;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      color: rgba(255, 255, 255, 0.82);
      margin-bottom: 0.35rem;
    }

    .network-org-mini {
      display: grid;
      gap: 0.05rem;
      font-size: 0.64rem;
      color: rgba(255, 255, 255, 0.92);
    }

    .network-org-mini strong {
      color: #fff;
      font-weight: 700;
    }

    .network-org-helper {
      color: rgba(255, 255, 255, 0.78);
      font-size: 0.82rem;
      margin-top: 0.9rem;
      text-align: center;
    }

    .network-org-health-card {
      transition: background-color 0.2s ease, border-color 0.2s ease;
    }

    .network-org-health-card.severity-healthy {
      background: #ecfdf3 !important;
      border-color: #86efac !important;
    }

    .network-org-health-card.severity-watch {
      background: #fff7ed !important;
      border-color: #fdba74 !important;
    }

    .network-org-health-card.severity-risk {
      background: #fef2f2 !important;
      border-color: #fca5a5 !important;
    }

    .network-org-health-card.severity-early {
      background: #eff6ff !important;
      border-color: #93c5fd !important;
    }

    .network-org-health-card.severity-healthy [data-org-modal-health] {
      color: #15803d;
    }

    .network-org-health-card.severity-watch [data-org-modal-health] {
      color: #c2410c;
    }

    .network-org-health-card.severity-risk [data-org-modal-health] {
      color: #b91c1c;
    }

    .network-org-health-card.severity-early [data-org-modal-health] {
      color: #1d4ed8;
    }

    @media (max-width: 991.98px) {
      .network-org-node {
        width: 128px;
      }
    }
  </style>

  <div class="modal fade" id="networkOrgChartModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header border-0 pb-0">
          <div>
            <p class="text-secondary small mb-1">Investor branch quick view</p>
            <h5 class="modal-title mb-0" id="networkOrgChartModalTitle">Investor</h5>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body pt-3">
          <div class="d-flex gap-2 flex-wrap mb-3">
            <span class="badge bg-primary" data-org-modal-rank>Rank</span>
            <span class="badge bg-light text-dark" data-org-modal-level>Level</span>
            <span class="badge bg-dark" data-org-modal-priority>Priority</span>
          </div>
          <div class="d-flex gap-2 flex-wrap mb-3" data-org-modal-reward-caps></div>
          <div class="border rounded p-3 mb-3 bg-light network-org-health-card" data-org-modal-health-card>
            <div class="text-secondary small">Branch health</div>
            <div class="fw-semibold mb-1" data-org-modal-health></div>
            <div class="text-secondary small mb-0" data-org-modal-action-hint></div>
          </div>
          <p class="mb-3 text-secondary" data-org-modal-description></p>
          <div class="row g-3">
            <div class="col-6"><div class="border rounded p-3 h-100 bg-light"><div class="text-secondary small">Sponsor</div><div class="fw-semibold" data-org-modal-sponsor></div></div></div>
            <div class="col-6"><div class="border rounded p-3 h-100 bg-light"><div class="text-secondary small">Power</div><div class="fw-semibold" data-org-modal-power></div></div></div>
            <div class="col-6"><div class="border rounded p-3 h-100 bg-light"><div class="text-secondary small">Direct team</div><div class="fw-semibold" data-org-modal-team></div></div></div>
            <div class="col-6"><div class="border rounded p-3 h-100 bg-light"><div class="text-secondary small">Active direct investors</div><div class="fw-semibold" data-org-modal-active-direct></div></div></div>
            <div class="col-6"><div class="border rounded p-3 h-100 bg-light"><div class="text-secondary small">Active capital</div><div class="fw-semibold" data-org-modal-capital></div></div></div>
            <div class="col-6"><div class="border rounded p-3 h-100 bg-light"><div class="text-secondary small">Verified invites</div><div class="fw-semibold" data-org-modal-invites></div></div></div>
            <div class="col-6"><div class="border rounded p-3 h-100 bg-light"><div class="text-secondary small">Visible descendants</div><div class="fw-semibold" data-org-modal-descendants></div></div></div>
            <div class="col-6"><div class="border rounded p-3 h-100 bg-light"><div class="text-secondary small">Branch investors</div><div class="fw-semibold" data-org-modal-branch-investors></div></div></div>
            <div class="col-12"><div class="border rounded p-3 h-100 bg-light"><div class="text-secondary small">Branch capital</div><div class="fw-semibold" data-org-modal-branch-capital></div></div></div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <div class="d-flex flex-wrap gap-2 w-100 justify-content-end">
            <a href="#" class="btn btn-outline-secondary" data-org-modal-users-link>Users record</a>
            <a href="#" class="btn btn-outline-secondary" data-org-modal-shareholders-link>Shareholder records</a>
            <a href="#" class="btn btn-primary" data-org-modal-profile-link>Open full profile</a>
          </div>
        </div>
      </div>
    </div>
  </div>
@endonce

<div class="network-org-card">
  <div class="network-org-title">{{ $chartTitle }}</div>
  <div class="network-org-subtitle">{{ $chartDescription }}</div>

  <div class="network-org-legend">
    <span class="network-org-legend-item"><span class="network-org-legend-dot" style="background:#f59e0b;"></span>Root leaders</span>
    <span class="network-org-legend-item"><span class="network-org-legend-dot" style="background:#ef4444;"></span>Direct branches</span>
    <span class="network-org-legend-item"><span class="network-org-legend-dot" style="background:#5548e8;"></span>Growth layer</span>
    <span class="network-org-legend-item"><span class="network-org-legend-dot" style="background:#0f8ab7;"></span>Deeper levels</span>
  </div>

  <div class="network-org-shell">
    <ul class="network-org-tree">
      @foreach ($tree as $node)
        @include('pages.general.partials.network-org-chart-node', ['node' => $node])
      @endforeach
    </ul>
  </div>

  <div class="network-org-helper">Click any node for branch details, investor strength, and a direct profile shortcut.</div>
</div>

@once
  @push('custom-scripts')
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const modalElement = document.getElementById('networkOrgChartModal');

        if (!modalElement || typeof bootstrap === 'undefined') {
          return;
        }

        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        const nodeModalFields = {
          title: modalElement.querySelector('#networkOrgChartModalTitle'),
          rank: modalElement.querySelector('[data-org-modal-rank]'),
          level: modalElement.querySelector('[data-org-modal-level]'),
          priority: modalElement.querySelector('[data-org-modal-priority]'),
          healthCard: modalElement.querySelector('[data-org-modal-health-card]'),
          health: modalElement.querySelector('[data-org-modal-health]'),
          actionHint: modalElement.querySelector('[data-org-modal-action-hint]'),
          description: modalElement.querySelector('[data-org-modal-description]'),
          sponsor: modalElement.querySelector('[data-org-modal-sponsor]'),
          power: modalElement.querySelector('[data-org-modal-power]'),
          team: modalElement.querySelector('[data-org-modal-team]'),
          activeDirect: modalElement.querySelector('[data-org-modal-active-direct]'),
          capital: modalElement.querySelector('[data-org-modal-capital]'),
          descendants: modalElement.querySelector('[data-org-modal-descendants]'),
          branchInvestors: modalElement.querySelector('[data-org-modal-branch-investors]'),
          branchCapital: modalElement.querySelector('[data-org-modal-branch-capital]'),
          invites: modalElement.querySelector('[data-org-modal-invites]'),
          users: modalElement.querySelector('[data-org-modal-users-link]'),
          shareholders: modalElement.querySelector('[data-org-modal-shareholders-link]'),
          profile: modalElement.querySelector('[data-org-modal-profile-link]'),
          rewardCaps: modalElement.querySelector('[data-org-modal-reward-caps]'),
        };

        document.querySelectorAll('[data-network-org-node]').forEach(function (node) {
          if (node.dataset.bound === 'true') {
            return;
          }

          node.dataset.bound = 'true';

          node.addEventListener('click', function () {
            const health = node.dataset.health || 'Branch health';
            const severityClass = (function () {
              if (health === 'Healthy branch') return 'severity-healthy';
              if (health === 'Needs activation') return 'severity-watch';
              if (health === 'Invite-heavy, low conversion' || health === 'Capital without depth') return 'severity-risk';
              return 'severity-early';
            })();

            nodeModalFields.title.textContent = node.dataset.name || 'Investor';
            nodeModalFields.rank.textContent = node.dataset.rank || 'Rank';
            nodeModalFields.level.textContent = node.dataset.level || 'Level';
            nodeModalFields.priority.textContent = node.dataset.priority || 'Priority';
            nodeModalFields.healthCard.classList.remove('severity-healthy', 'severity-watch', 'severity-risk', 'severity-early');
            nodeModalFields.healthCard.classList.add(severityClass);
            nodeModalFields.health.textContent = health;
            nodeModalFields.actionHint.textContent = node.dataset.actionHint || '';
            nodeModalFields.description.textContent = node.dataset.description || '';
            nodeModalFields.sponsor.textContent = node.dataset.sponsor || 'Top-level';
            nodeModalFields.power.textContent = node.dataset.power || 'N/A';
            nodeModalFields.team.textContent = node.dataset.team || '0';
            nodeModalFields.activeDirect.textContent = node.dataset.activeDirect || '0';
            nodeModalFields.capital.textContent = node.dataset.capital || '$0.00';
            nodeModalFields.descendants.textContent = node.dataset.descendants || '0';
            nodeModalFields.branchInvestors.textContent = node.dataset.branchInvestors || '0';
            nodeModalFields.branchCapital.textContent = node.dataset.branchCapital || '$0.00';
            nodeModalFields.invites.textContent = node.dataset.invites || '0';
            nodeModalFields.users.href = node.dataset.usersLink || '#';
            nodeModalFields.shareholders.href = node.dataset.shareholdersLink || '#';
            nodeModalFields.profile.href = node.dataset.profile || '#';
            nodeModalFields.users.classList.toggle('d-none', !node.dataset.usersLink);
            nodeModalFields.shareholders.classList.toggle('d-none', !node.dataset.shareholdersLink);
            nodeModalFields.profile.classList.toggle('d-none', !node.dataset.profile);
            nodeModalFields.rewardCaps.innerHTML = '';

            if (node.dataset.rewardCaps) {
              node.dataset.rewardCaps.split('|').filter(Boolean).forEach(function (cap) {
                const badge = document.createElement('span');
                badge.className = 'badge bg-info-subtle text-info border border-info-subtle';
                badge.textContent = cap;
                nodeModalFields.rewardCaps.appendChild(badge);
              });
            }

            nodeModalFields.rewardCaps.classList.toggle('d-none', !node.dataset.rewardCaps);
            modal.show();
          });
        });
      });
    </script>
  @endpush
@endonce
