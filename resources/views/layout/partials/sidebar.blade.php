<nav class="sidebar">
  <div class="sidebar-header">
    <a href="#" class="sidebar-brand">
      Noble<span>UI</span>
    </a>
    <div class="sidebar-toggler not-active">
      <span></span>
      <span></span>
      <span></span>
    </div>
  </div>
  <div class="sidebar-body">
    <ul class="nav" id="sidebarNav">
      <li class="nav-item nav-category">Main</li>
      <li class="nav-item {{ active_class(['dashboard', 'dashboard/profile', 'dashboard/notifications', 'dashboard/notification-preferences', 'dashboard/investment-orders', 'dashboard/investments', 'dashboard/network', 'dashboard/network-admin', 'dashboard/digests', 'dashboard/wallet', 'dashboard/analytics', 'dashboard/shareholders', 'dashboard/users', 'dashboard/operations', 'dashboard/rewards', 'dashboard/settings', 'dashboard/notification-rules', 'dashboard/notification-templates', 'dashboard/packages', 'dashboard/miners', 'dashboard/miner', 'dashboard/friends']) }}">
        <a class="nav-link" data-bs-toggle="collapse" href="#dashboardMenu" role="button" aria-expanded="{{ is_active_route(['dashboard', 'dashboard/profile', 'dashboard/notifications', 'dashboard/notification-preferences', 'dashboard/investment-orders', 'dashboard/investments', 'dashboard/network', 'dashboard/network-admin', 'dashboard/digests', 'dashboard/wallet', 'dashboard/analytics', 'dashboard/shareholders', 'dashboard/users', 'dashboard/operations', 'dashboard/rewards', 'dashboard/settings', 'dashboard/notification-rules', 'dashboard/notification-templates', 'dashboard/packages', 'dashboard/miners', 'dashboard/miner', 'dashboard/friends']) }}" aria-controls="dashboardMenu">
          <i class="link-icon" data-lucide="home"></i>
          <span class="link-title">Dashboard</span>
          <i class="link-arrow" data-lucide="chevron-down"></i>
        </a>
        <div class="collapse {{ show_class(['dashboard', 'dashboard/profile', 'dashboard/notifications', 'dashboard/notification-preferences', 'dashboard/investment-orders', 'dashboard/investments', 'dashboard/network', 'dashboard/network-admin', 'dashboard/digests', 'dashboard/wallet', 'dashboard/analytics', 'dashboard/shareholders', 'dashboard/users', 'dashboard/operations', 'dashboard/rewards', 'dashboard/settings', 'dashboard/notification-rules', 'dashboard/notification-templates', 'dashboard/packages', 'dashboard/miners', 'dashboard/miner', 'dashboard/friends']) }}" data-bs-parent="#sidebarNav" id="dashboardMenu">
          <ul class="nav sub-menu">
            <li class="nav-item">
              <a href="{{ route('dashboard') }}" class="nav-link {{ active_class(['dashboard']) }}">Overview</a>
            </li>
            <li class="nav-item">
              <a href="{{ route('dashboard.profile') }}" class="nav-link {{ active_class(['dashboard/profile']) }}">Profile</a>
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
              <a href="{{ route('dashboard.network') }}" class="nav-link {{ active_class(['dashboard/network']) }}">Network</a>
            </li>
            <li class="nav-item">
              <a href="{{ route('dashboard.wallet') }}" class="nav-link {{ active_class(['dashboard/wallet']) }}">Wallet</a>
            </li>
            @if (auth()->user()?->isAdmin())
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
            @endif
            <li class="nav-item">
              <a href="{{ route('dashboard.friends') }}" class="nav-link {{ active_class(['dashboard/friends']) }}">Friends</a>
            </li>
          </ul>
        </div>
      </li>
      <li class="nav-item nav-category">web apps</li>
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
      <li class="nav-item {{ active_class(['apps/chat']) }}">
        <a href="{{ url('/apps/chat') }}" class="nav-link">
          <i class="link-icon" data-lucide="message-square"></i>
          <span class="link-title">Chat</span>
        </a>
      </li>
      <li class="nav-item {{ active_class(['apps/calendar']) }}">
        <a href="{{ url('/apps/calendar') }}" class="nav-link">
          <i class="link-icon" data-lucide="calendar"></i>
          <span class="link-title">Calendar</span>
        </a>
      </li>
      <li class="nav-item nav-category">Components</li>
      <li class="nav-item {{ active_class(['ui-components/*']) }}">
        <a class="nav-link" data-bs-toggle="collapse" href="#uiComponents" role="button" aria-expanded="{{ is_active_route(['ui-components/*']) }}" aria-controls="uiComponents">
          <i class="link-icon" data-lucide="feather"></i>
          <span class="link-title">UI Kit</span>
          <i class="link-arrow" data-lucide="chevron-down"></i>
        </a>
        <div class="collapse {{ show_class(['ui-components/*']) }}" data-bs-parent="#sidebarNav" id="uiComponents">
          <ul class="nav sub-menu">
            <li class="nav-item">
              <a href="{{ url('/ui-components/accordion') }}" class="nav-link {{ active_class(['ui-components/accordion']) }}">Accordion</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/ui-components/alerts') }}" class="nav-link {{ active_class(['ui-components/alerts']) }}">Alerts</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/ui-components/badges') }}" class="nav-link {{ active_class(['ui-components/badges']) }}">Badges</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/ui-components/breadcrumbs') }}" class="nav-link {{ active_class(['ui-components/breadcrumbs']) }}">Breadcrumbs</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/ui-components/buttons') }}" class="nav-link {{ active_class(['ui-components/buttons']) }}">Buttons</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/ui-components/button-group') }}" class="nav-link {{ active_class(['ui-components/button-group']) }}">Button group</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/ui-components/cards') }}" class="nav-link {{ active_class(['ui-components/cards']) }}">Cards</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/ui-components/carousel') }}" class="nav-link {{ active_class(['ui-components/carousel']) }}">Carousel</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/ui-components/collapse') }}" class="nav-link {{ active_class(['ui-components/collapse']) }}">Collapse</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/ui-components/dropdowns') }}" class="nav-link {{ active_class(['ui-components/dropdowns']) }}">Dropdowns</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/ui-components/list-group') }}" class="nav-link {{ active_class(['ui-components/list-group']) }}">List group</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/ui-components/media-object') }}" class="nav-link {{ active_class(['ui-components/media-object']) }}">Media object</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/ui-components/modal') }}" class="nav-link {{ active_class(['ui-components/modal']) }}">Modal</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/ui-components/navs') }}" class="nav-link {{ active_class(['ui-components/navs']) }}">Navs</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/ui-components/offcanvas') }}" class="nav-link {{ active_class(['ui-components/offcanvas']) }}">Offcanvas</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/ui-components/pagination') }}" class="nav-link {{ active_class(['ui-components/pagination']) }}">Pagination</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/ui-components/placeholders') }}" class="nav-link {{ active_class(['ui-components/placeholders']) }}">Placeholders</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/ui-components/popovers') }}" class="nav-link {{ active_class(['ui-components/popovers']) }}">Popvers</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/ui-components/progress') }}" class="nav-link {{ active_class(['ui-components/progress']) }}">Progress</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/ui-components/scrollbar') }}" class="nav-link {{ active_class(['ui-components/scrollbar']) }}">Scrollbar</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/ui-components/scrollspy') }}" class="nav-link {{ active_class(['ui-components/scrollspy']) }}">Scrollspy</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/ui-components/spinners') }}" class="nav-link {{ active_class(['ui-components/spinners']) }}">Spinners</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/ui-components/tabs') }}" class="nav-link {{ active_class(['ui-components/tabs']) }}">Tabs</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/ui-components/toasts') }}" class="nav-link {{ active_class(['ui-components/toasts']) }}">Toasts</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/ui-components/tooltips') }}" class="nav-link {{ active_class(['ui-components/tooltips']) }}">Tooltips</a>
            </li>
          </ul>
        </div>
      </li>
      <li class="nav-item {{ active_class(['advanced-ui/*']) }}">
        <a class="nav-link" data-bs-toggle="collapse" href="#advanced-ui" role="button" aria-expanded="{{ is_active_route(['advanced-ui/*']) }}" aria-controls="advanced-ui">
          <i class="link-icon" data-lucide="anchor"></i>
          <span class="link-title">Advanced UI</span>
          <i class="link-arrow" data-lucide="chevron-down"></i>
        </a>
        <div class="collapse {{ show_class(['advanced-ui/*']) }}" data-bs-parent="#sidebarNav" id="advanced-ui">
          <ul class="nav sub-menu">
            <li class="nav-item">
              <a href="{{ url('/advanced-ui/cropper') }}" class="nav-link {{ active_class(['advanced-ui/cropper']) }}">Cropper</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/advanced-ui/owl-carousel') }}" class="nav-link {{ active_class(['advanced-ui/owl-carousel']) }}">Owl Carousel</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/advanced-ui/sortablejs') }}" class="nav-link {{ active_class(['advanced-ui/sortablejs']) }}">SortableJs</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/advanced-ui/sweet-alert') }}" class="nav-link {{ active_class(['advanced-ui/sweet-alert']) }}">Sweet Alert</a>
            </li>
          </ul>
        </div>
      </li>
      <li class="nav-item {{ active_class(['forms/*']) }}">
        <a class="nav-link" data-bs-toggle="collapse" href="#forms" role="button" aria-expanded="{{ is_active_route(['forms/*']) }}" aria-controls="forms">
          <i class="link-icon" data-lucide="inbox"></i>
          <span class="link-title">Forms</span>
          <i class="link-arrow" data-lucide="chevron-down"></i>
        </a>
        <div class="collapse {{ show_class(['forms/*']) }}" data-bs-parent="#sidebarNav" id="forms">
          <ul class="nav sub-menu">
            <li class="nav-item">
              <a href="{{ url('/forms/basic-elements') }}" class="nav-link {{ active_class(['forms/basic-elements']) }}">Basic Elements</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/forms/advanced-elements') }}" class="nav-link {{ active_class(['forms/advanced-elements']) }}">Advanced Elements</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/forms/editors') }}" class="nav-link {{ active_class(['forms/editors']) }}">Editors</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/forms/wizard') }}" class="nav-link {{ active_class(['forms/wizard']) }}">Wizard</a>
            </li>
          </ul>
        </div>
      </li>
      <li class="nav-item {{ active_class(['charts/*']) }}">
        <a class="nav-link" data-bs-toggle="collapse" href="#charts" role="button" aria-expanded="{{ is_active_route(['charts/*']) }}" aria-controls="charts">
          <i class="link-icon" data-lucide="pie-chart"></i>
          <span class="link-title">Charts</span>
          <i class="link-arrow" data-lucide="chevron-down"></i>
        </a>
        <div class="collapse {{ show_class(['charts/*']) }}" data-bs-parent="#sidebarNav" id="charts">
          <ul class="nav sub-menu">
            <li class="nav-item">
              <a href="{{ url('/charts/apex') }}" class="nav-link {{ active_class(['charts/apex']) }}">Apex</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/charts/chartjs') }}" class="nav-link {{ active_class(['charts/chartjs']) }}">ChartJs</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/charts/flot') }}" class="nav-link {{ active_class(['charts/flot']) }}">Flot</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/charts/peity') }}" class="nav-link {{ active_class(['charts/peity']) }}">Peity</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/charts/sparkline') }}" class="nav-link {{ active_class(['charts/sparkline']) }}">Sparkline</a>
            </li>
          </ul>
        </div>
      </li>
      <li class="nav-item {{ active_class(['tables/*']) }}">
        <a class="nav-link" data-bs-toggle="collapse" href="#tables" role="button" aria-expanded="{{ is_active_route(['tables/*']) }}" aria-controls="tables">
          <i class="link-icon" data-lucide="layout"></i>
          <span class="link-title">Tables</span>
          <i class="link-arrow" data-lucide="chevron-down"></i>
        </a>
        <div class="collapse {{ show_class(['tables/*']) }}" data-bs-parent="#sidebarNav" id="tables">
          <ul class="nav sub-menu">
            <li class="nav-item">
              <a href="{{ url('/tables/basic-tables') }}" class="nav-link {{ active_class(['tables/basic-tables']) }}">Basic Tables</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/tables/data-table') }}" class="nav-link {{ active_class(['tables/data-table']) }}">Data Table</a>
            </li>
          </ul>
        </div>
      </li>
      <li class="nav-item {{ active_class(['icons/*']) }}">
        <a class="nav-link" data-bs-toggle="collapse" href="#icons" role="button" aria-expanded="{{ is_active_route(['icons/*']) }}" aria-controls="icons">
          <i class="link-icon" data-lucide="smile"></i>
          <span class="link-title">Icons</span>
          <i class="link-arrow" data-lucide="chevron-down"></i>
        </a>
        <div class="collapse {{ show_class(['icons/*']) }}" data-bs-parent="#sidebarNav" id="icons">
          <ul class="nav sub-menu">
            <li class="nav-item">
              <a href="{{ url('/icons/lucide-icons') }}" class="nav-link {{ active_class(['icons/lucide-icons']) }}">Lucide Icons</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/icons/flag-icons') }}" class="nav-link {{ active_class(['icons/flag-icons']) }}">Flag Icons</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/icons/mdi-icons') }}" class="nav-link {{ active_class(['icons/mdi-icons']) }}">Mdi Icons</a>
            </li>
          </ul>
        </div>
      </li>
      <li class="nav-item nav-category">Pages</li>
      <li class="nav-item {{ active_class(['general/*']) }}">
        <a class="nav-link" data-bs-toggle="collapse" href="#general" role="button" aria-expanded="{{ is_active_route(['general/*']) }}" aria-controls="general">
          <i class="link-icon" data-lucide="book"></i>
          <span class="link-title">Special pages</span>
          <i class="link-arrow" data-lucide="chevron-down"></i>
        </a>
        <div class="collapse {{ show_class(['general/*']) }}" data-bs-parent="#sidebarNav" id="general">
          <ul class="nav sub-menu">
            <li class="nav-item">
              <a href="{{ url('/general/blank-page') }}" class="nav-link {{ active_class(['general/blank-page']) }}">Blank page</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/general/faq') }}" class="nav-link {{ active_class(['general/faq']) }}">Faq</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/general/invoice') }}" class="nav-link {{ active_class(['general/invoice']) }}">Invoice</a>
            </li>
            <li class="nav-item">
              <a href="{{ route('dashboard.profile') }}" class="nav-link {{ active_class(['general/profile']) }}">Profile</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/general/pricing') }}" class="nav-link {{ active_class(['general/pricing']) }}">Pricing</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/general/sell-products') }}" class="nav-link {{ active_class(['general/sell-products']) }}">Sell Products</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/general/timeline') }}" class="nav-link {{ active_class(['general/timeline']) }}">Timeline</a>
            </li>
          </ul>
        </div>
      </li>
      <li class="nav-item {{ active_class(['login','register','forgot-password']) }}">
        <a class="nav-link" data-bs-toggle="collapse" href="#auth" role="button" aria-expanded="{{ is_active_route(['login','register','forgot-password']) }}" aria-controls="auth">
          <i class="link-icon" data-lucide="unlock"></i>
          <span class="link-title">Authentication</span>
          <i class="link-arrow" data-lucide="chevron-down"></i>
        </a>
        <div class="collapse {{ show_class(['login','register','forgot-password']) }}" data-bs-parent="#sidebarNav" id="auth">
          <ul class="nav sub-menu">
            @guest
              <li class="nav-item">
                <a href="{{ route('login') }}" class="nav-link {{ active_class(['login']) }}">Login</a>
              </li>
              <li class="nav-item">
                <a href="{{ route('register') }}" class="nav-link {{ active_class(['register']) }}">Register</a>
              </li>
              <li class="nav-item">
                <a href="{{ route('password.request') }}" class="nav-link {{ active_class(['forgot-password']) }}">Forgot Password</a>
              </li>
            @endguest
            @auth
              <li class="nav-item">
                <a href="{{ route('dashboard.profile') }}" class="nav-link {{ active_class(['profile']) }}">My Profile</a>
              </li>
              <li class="nav-item">
                <form method="POST" action="{{ route('logout') }}">
                  @csrf
                  <button type="submit" class="nav-link text-start border-0 bg-transparent w-100">Logout</button>
                </form>
              </li>
            @endauth
          </ul>
        </div>
      </li><li class="nav-item {{ active_class(['error/*']) }}">
        <a class="nav-link" data-bs-toggle="collapse" href="#error" role="button" aria-expanded="{{ is_active_route(['error/*']) }}" aria-controls="error">
          <i class="link-icon" data-lucide="cloud-off"></i>
          <span class="link-title">Error</span>
          <i class="link-arrow" data-lucide="chevron-down"></i>
        </a>
        <div class="collapse {{ show_class(['error/*']) }}" data-bs-parent="#sidebarNav" id="error">
          <ul class="nav sub-menu">
            <li class="nav-item">
              <a href="{{ url('/error/404') }}" class="nav-link {{ active_class(['error/404']) }}">404</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/error/500') }}" class="nav-link {{ active_class(['error/500']) }}">500</a>
            </li>
          </ul>
        </div>
      </li>
      <li class="nav-item nav-category">Docs</li>
      <li class="nav-item">
        <a href="https://nobleui.com/laravel/documentation/docs.html" target="_blank" class="nav-link">
          <i class="link-icon" data-lucide="hash"></i>
          <span class="link-title">Documentation</span>
        </a>
      </li>
    </ul>
  </div>
</nav>
















