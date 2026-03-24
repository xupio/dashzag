<nav class="sidebar">
  <div class="sidebar-header">
    <a href="#" class="sidebar-brand">
      <img src="{{ asset('branding/zagchain-logo-sidebar.png') }}" alt="ZagChain" style="display: block; max-width: 235px; max-height: 50px; width: auto; height: auto; object-fit: contain;">
    </a>
    <div class="sidebar-toggler not-active">
      <span></span>
      <span></span>
      <span></span>
    </div>
  </div>
  <div class="sidebar-body">
    <ul class="nav" id="sidebarNav">
      <li class="nav-item nav-category">Dashboard</li>
      <li class="nav-item {{ active_class(['dashboard', 'dashboard/profile', 'dashboard/hall-of-fame', 'dashboard/miner-report', 'dashboard/notifications', 'dashboard/notification-preferences', 'dashboard/investment-orders', 'dashboard/investments', 'dashboard/network', 'dashboard/wallet', 'dashboard/friends', 'dashboard/buy-shares']) }}">
        <a class="nav-link" data-bs-toggle="collapse" href="#dashboardMenu" role="button" aria-expanded="{{ is_active_route(['dashboard', 'dashboard/profile', 'dashboard/hall-of-fame', 'dashboard/miner-report', 'dashboard/notifications', 'dashboard/notification-preferences', 'dashboard/investment-orders', 'dashboard/investments', 'dashboard/network', 'dashboard/wallet', 'dashboard/friends', 'dashboard/buy-shares']) }}" aria-controls="dashboardMenu">
          <i class="link-icon" data-lucide="home"></i>
          <span class="link-title">Dashboard</span>
          <i class="link-arrow" data-lucide="chevron-down"></i>
        </a>
        <div class="collapse {{ show_class(['dashboard', 'dashboard/profile', 'dashboard/hall-of-fame', 'dashboard/miner-report', 'dashboard/notifications', 'dashboard/notification-preferences', 'dashboard/investment-orders', 'dashboard/investments', 'dashboard/network', 'dashboard/wallet', 'dashboard/friends', 'dashboard/buy-shares']) }}" data-bs-parent="#sidebarNav" id="dashboardMenu">
          <ul class="nav sub-menu">
            <li class="nav-item">
              <a href="{{ route('dashboard') }}" class="nav-link {{ active_class(['dashboard']) }}">Overview</a>
            </li>
            <li class="nav-item">
              <a href="{{ route('dashboard.profile') }}" class="nav-link {{ active_class(['dashboard/profile']) }}">Personal Profile</a>
            </li>
            <li class="nav-item">
              <a href="{{ route('dashboard.notifications') }}" class="nav-link {{ active_class(['dashboard/notifications']) }}">Notifications</a>
            </li>
            <li class="nav-item">
              <a href="{{ route('dashboard.notification-preferences') }}" class="nav-link {{ active_class(['dashboard/notification-preferences']) }}">Notification Preferences</a>
            </li>
            <li class="nav-item">
              <a href="{{ route('dashboard.investment-orders') }}" class="nav-link {{ active_class(['dashboard/investment-orders']) }}">Investment Orders</a>
            </li>
            <li class="nav-item">
              <a href="{{ route('dashboard.investments') }}" class="nav-link {{ active_class(['dashboard/investments']) }}">Investments</a>
            </li>
            <li class="nav-item">
              <a href="{{ route('dashboard.buy-shares') }}" class="nav-link {{ active_class(['dashboard/buy-shares']) }}">Buy Shares</a>
            </li>
            <li class="nav-item">
              <a href="{{ route('dashboard.miner-report') }}" class="nav-link {{ active_class(['dashboard/miner-report']) }}">Daily Miner Report</a>
            </li>
            <li class="nav-item">
              <a href="{{ route('dashboard.network') }}" class="nav-link {{ active_class(['dashboard/network']) }}">My Network</a>
            </li>
            <li class="nav-item">
              <a href="{{ route('dashboard.hall-of-fame') }}" class="nav-link {{ active_class(['dashboard/hall-of-fame']) }}">Hall of Fame</a>
            </li>
            <li class="nav-item">
              <a href="{{ route('dashboard.wallet') }}" class="nav-link {{ active_class(['dashboard/wallet']) }}">Wallet</a>
            </li>
            <li class="nav-item">
              <a href="{{ route('dashboard.friends') }}" class="nav-link {{ active_class(['dashboard/friends']) }}">Friends</a>
            </li>
          </ul>
        </div>
      </li>

      <li class="nav-item nav-category">Communication</li>
      <li class="nav-item {{ active_class(['email/*']) }}">
        <a class="nav-link" data-bs-toggle="collapse" href="#email" role="button" aria-expanded="{{ is_active_route(['email/*']) }}" aria-controls="email">
          <i class="link-icon" data-lucide="mail"></i>
          <span class="link-title">Email</span>
          <i class="link-arrow" data-lucide="chevron-down"></i>
        </a>
        <div class="collapse {{ show_class(['email/*']) }}" data-bs-parent="#sidebarNav" id="email">
          <ul class="nav sub-menu">
            <li class="nav-item">
              <a href="{{ url('/email/inbox') }}" class="nav-link {{ active_class(['email/inbox']) }}">Inbox</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/email/read') }}" class="nav-link {{ active_class(['email/read']) }}">Read</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/email/compose') }}" class="nav-link {{ active_class(['email/compose']) }}">Compose</a>
            </li>
          </ul>
        </div>
      </li>

      @if (auth()->user()?->isAdmin())
        <li class="nav-item nav-category">Admin</li>
        <li class="nav-item {{ active_class(['dashboard/analytics', 'dashboard/digests', 'dashboard/network-admin', 'dashboard/shareholders', 'dashboard/users', 'dashboard/operations', 'dashboard/rewards', 'dashboard/settings', 'dashboard/notification-rules', 'dashboard/notification-templates', 'dashboard/packages', 'dashboard/miners', 'dashboard/miner', 'dashboard/mock-manager']) }}">
          <a class="nav-link" data-bs-toggle="collapse" href="#adminMenu" role="button" aria-expanded="{{ is_active_route(['dashboard/analytics', 'dashboard/digests', 'dashboard/network-admin', 'dashboard/shareholders', 'dashboard/users', 'dashboard/operations', 'dashboard/rewards', 'dashboard/settings', 'dashboard/notification-rules', 'dashboard/notification-templates', 'dashboard/packages', 'dashboard/miners', 'dashboard/miner', 'dashboard/mock-manager']) }}" aria-controls="adminMenu">
            <i class="link-icon" data-lucide="shield"></i>
            <span class="link-title">Admin</span>
            <i class="link-arrow" data-lucide="chevron-down"></i>
          </a>
          <div class="collapse {{ show_class(['dashboard/analytics', 'dashboard/digests', 'dashboard/network-admin', 'dashboard/shareholders', 'dashboard/users', 'dashboard/operations', 'dashboard/rewards', 'dashboard/settings', 'dashboard/notification-rules', 'dashboard/notification-templates', 'dashboard/packages', 'dashboard/miners', 'dashboard/miner', 'dashboard/mock-manager']) }}" data-bs-parent="#sidebarNav" id="adminMenu">
            <ul class="nav sub-menu">
              <li class="nav-item">
                <a href="{{ route('dashboard.analytics') }}" class="nav-link {{ active_class(['dashboard/analytics']) }}">Analytics</a>
              </li>
              <li class="nav-item">
                <a href="{{ route('dashboard.digests') }}" class="nav-link {{ active_class(['dashboard/digests']) }}">Digests</a>
              </li>
              <li class="nav-item">
                <a href="{{ route('dashboard.network-admin') }}" class="nav-link {{ active_class(['dashboard/network-admin']) }}">Network Admin</a>
              </li>
              <li class="nav-item">
                <a href="{{ route('dashboard.shareholders') }}" class="nav-link {{ active_class(['dashboard/shareholders']) }}">Shareholders</a>
              </li>
              <li class="nav-item">
                <a href="{{ route('dashboard.users') }}" class="nav-link {{ active_class(['dashboard/users']) }}">Users</a>
              </li>
              <li class="nav-item">
                <a href="{{ route('dashboard.operations') }}" class="nav-link {{ active_class(['dashboard/operations']) }}">Operations</a>
              </li>
              <li class="nav-item">
                <a href="{{ route('dashboard.rewards') }}" class="nav-link {{ active_class(['dashboard/rewards']) }}">Rewards</a>
              </li>
              <li class="nav-item">
                <a href="{{ route('dashboard.settings') }}" class="nav-link {{ active_class(['dashboard/settings']) }}">Settings</a>
              </li>
              <li class="nav-item">
                <a href="{{ route('dashboard.notification-rules') }}" class="nav-link {{ active_class(['dashboard/notification-rules']) }}">Notification Rules</a>
              </li>
              <li class="nav-item">
                <a href="{{ route('dashboard.notification-templates') }}" class="nav-link {{ active_class(['dashboard/notification-templates']) }}">Notification Templates</a>
              </li>
              <li class="nav-item">
                <a href="{{ route('dashboard.packages') }}" class="nav-link {{ active_class(['dashboard/packages']) }}">Packages</a>
              </li>
              <li class="nav-item">
                <a href="{{ route('dashboard.miners') }}" class="nav-link {{ active_class(['dashboard/miners']) }}">Miners</a>
              </li>
              <li class="nav-item">
                <a href="{{ route('dashboard.miner') }}" class="nav-link {{ active_class(['dashboard/miner']) }}">Miner</a>
              </li>
              <li class="nav-item">
                <a href="{{ route('dashboard.mock-manager') }}" class="nav-link {{ active_class(['dashboard/mock-manager']) }}">Mock Manager</a>
              </li>
            </ul>
          </div>
        </li>
      @endif
    </ul>
  </div>
</nav>























