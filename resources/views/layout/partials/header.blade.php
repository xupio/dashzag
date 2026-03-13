<nav class="navbar">
  <div class="navbar-content">

    <div class="logo-mini-wrapper">
      <img src="{{ url('build/images/logo-mini-light.png') }}" class="logo-mini logo-mini-light" alt="logo">
      <img src="{{ url('build/images/logo-mini-dark.png') }}" class="logo-mini logo-mini-dark" alt="logo">
    </div>

    <form class="search-form">
      <div class="input-group">
        <div class="input-group-text">
          <i data-lucide="search"></i>
        </div>
        <input type="text" class="form-control" id="navbarForm" placeholder="Search here...">
      </div>
    </form>

    <ul class="navbar-nav">
      <li class="theme-switcher-wrapper nav-item">
        <input type="checkbox" value="" id="theme-switcher">
        <label for="theme-switcher">
          <div class="box">
            <div class="ball"></div>
            <div class="icons">
              <i class="link-icon" data-lucide="sun"></i>
              <i class="link-icon" data-lucide="moon"></i>
            </div>
          </div>
        </label>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle d-flex" href="#" id="languageDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <img src="{{ url('build/images/flags/us.svg') }}" class="w-20px" title="us" alt="flag">
          <span class="ms-2 d-none d-md-inline-block">English</span>
        </a>
        <div class="dropdown-menu" aria-labelledby="languageDropdown">
          <a href="javascript:;" class="dropdown-item py-2 d-flex"><img src="{{ url('build/images/flags/us.svg') }}" class="w-20px" title="us" alt="us"> <span class="ms-2"> English </span></a>
          <a href="javascript:;" class="dropdown-item py-2 d-flex"><img src="{{ url('build/images/flags/fr.svg') }}" class="w-20px" title="fr" alt="fr"> <span class="ms-2"> French </span></a>
          <a href="javascript:;" class="dropdown-item py-2 d-flex"><img src="{{ url('build/images/flags/de.svg') }}" class="w-20px" title="de" alt="de"> <span class="ms-2"> German </span></a>
          <a href="javascript:;" class="dropdown-item py-2 d-flex"><img src="{{ url('build/images/flags/pt.svg') }}" class="w-20px" title="pt" alt="pt"> <span class="ms-2"> Portuguese </span></a>
          <a href="javascript:;" class="dropdown-item py-2 d-flex"><img src="{{ url('build/images/flags/es.svg') }}" class="w-20px" title="es" alt="es"> <span class="ms-2"> Spanish </span></a>
        </div>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="appsDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <i data-lucide="layout-grid"></i>
        </a>
        <div class="dropdown-menu p-0" aria-labelledby="appsDropdown">
          <div class="px-3 py-2 d-flex align-items-center justify-content-between border-bottom">
            <p class="mb-0 fw-bold">Web Apps</p>
            <a href="javascript:;" class="text-secondary">Edit</a>
          </div>
          <div class="row g-0 p-1">
            <div class="col-3 text-center">
              <a href="{{ url('/apps/chat') }}" class="dropdown-item d-flex flex-column align-items-center justify-content-center w-70px h-70px"><i data-lucide="message-square" class="icon-lg mb-1"></i><p class="fs-12px">Chat</p></a>
            </div>
            <div class="col-3 text-center">
              <a href="{{ url('/apps/calendar') }}" class="dropdown-item d-flex flex-column align-items-center justify-content-center w-70px h-70px"><i data-lucide="calendar" class="icon-lg mb-1"></i><p class="fs-12px">Calendar</p></a>
            </div>
            <div class="col-3 text-center">
              <a href="{{ url('/email/inbox') }}" class="dropdown-item d-flex flex-column align-items-center justify-content-center w-70px h-70px"><i data-lucide="mail" class="icon-lg mb-1"></i><p class="fs-12px">Email</p></a>
            </div>
            <div class="col-3 text-center">
              <a href="{{ route('dashboard.profile') }}" class="dropdown-item d-flex flex-column align-items-center justify-content-center w-70px h-70px"><i data-lucide="instagram" class="icon-lg mb-1"></i><p class="fs-12px">Profile</p></a>
            </div>
          </div>
          <div class="px-3 py-2 d-flex align-items-center justify-content-center border-top">
            <a href="javascript:;">View all</a>
          </div>
        </div>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="messageDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <i data-lucide="mail"></i>
        </a>
        <div class="dropdown-menu p-0" aria-labelledby="messageDropdown">
          <div class="px-3 py-2 d-flex align-items-center justify-content-between border-bottom">
            <p>9 New Messages</p>
            <a href="javascript:;" class="text-secondary">Clear all</a>
          </div>
          <div class="p-1">
            <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
              <div class="me-3">
                <img class="w-30px h-30px rounded-circle" src="{{ url('https://placehold.co/30x30') }}" alt="userr">
              </div>
              <div class="d-flex justify-content-between flex-grow-1">
                <div class="me-4">
                  <p>Leonardo Payne</p>
                  <p class="fs-12px text-secondary">Project status</p>
                </div>
                <p class="fs-12px text-secondary">2 min ago</p>
              </div>
            </a>
            <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
              <div class="me-3">
                <img class="w-30px h-30px rounded-circle" src="{{ url('https://placehold.co/30x30') }}" alt="userr">
              </div>
              <div class="d-flex justify-content-between flex-grow-1">
                <div class="me-4">
                  <p>Carl Henson</p>
                  <p class="fs-12px text-secondary">Client meeting</p>
                </div>
                <p class="fs-12px text-secondary">30 min ago</p>
              </div>
            </a>
            <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
              <div class="me-3">
                <img class="w-30px h-30px rounded-circle" src="{{ url('https://placehold.co/30x30') }}" alt="userr">
              </div>
              <div class="d-flex justify-content-between flex-grow-1">
                <div class="me-4">
                  <p>Jensen Combs</p>
                  <p class="fs-12px text-secondary">Project updates</p>
                </div>
                <p class="fs-12px text-secondary">1 hrs ago</p>
              </div>
            </a>
            <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
              <div class="me-3">
                <img class="w-30px h-30px rounded-circle" src="{{ url('https://placehold.co/30x30') }}" alt="userr">
              </div>
              <div class="d-flex justify-content-between flex-grow-1">
                <div class="me-4">
                  <p>Amiah Burton</p>
                  <p class="fs-12px text-secondary">Project deadline</p>
                </div>
                <p class="fs-12px text-secondary">2 hrs ago</p>
              </div>
            </a>
            <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
              <div class="me-3">
                <img class="w-30px h-30px rounded-circle" src="{{ url('https://placehold.co/30x30') }}" alt="userr">
              </div>
              <div class="d-flex justify-content-between flex-grow-1">
                <div class="me-4">
                  <p>Yaretzi Mayo</p>
                  <p class="fs-12px text-secondary">New record</p>
                </div>
                <p class="fs-12px text-secondary">5 hrs ago</p>
              </div>
            </a>
          </div>
          <div class="px-3 py-2 d-flex align-items-center justify-content-center border-top">
            <a href="javascript:;">View all</a>
          </div>
        </div>
      </li>
      @php
        $headerNotifications = auth()->check() ? auth()->user()->notifications()->latest()->take(5)->get() : collect();
        $headerUnreadCount = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
      @endphp
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <i data-lucide="bell"></i>
          @if ($headerUnreadCount > 0)
            <div class="indicator">
              <div class="circle"></div>
            </div>
          @endif
        </a>
        <div class="dropdown-menu p-0" aria-labelledby="notificationDropdown">
          <div class="px-3 py-2 d-flex align-items-center justify-content-between border-bottom">
            <p>{{ $headerUnreadCount }} New Notifications</p>
            <a href="{{ route('dashboard.notifications') }}" class="text-secondary">Open feed</a>
          </div>
          <div class="p-1">
            @forelse ($headerNotifications as $notification)
              @php($data = $notification->data)
              @php($status = $data['status'] ?? 'info')
              @php($statusClass = in_array($status, ['paid', 'approved']) ? 'bg-primary' : (in_array($status, ['success', 'active']) ? 'bg-success' : ($status === 'reward' ? 'bg-info' : 'bg-warning')))
              <a href="{{ route('dashboard.notifications') }}" class="dropdown-item d-flex align-items-center py-2">
                <div class="w-30px h-30px d-flex align-items-center justify-content-center {{ $statusClass }} rounded-circle me-3">
                  <i class="icon-sm text-white" data-lucide="bell-ring"></i>
                </div>
                <div class="flex-grow-1 me-2">
                  <p>{{ $data['subject'] ?? 'Notification' }}</p>
                  <p class="fs-12px text-secondary">{{ $notification->created_at?->diffForHumans() }}</p>
                </div>
                @if (! $notification->read_at)
                  <span class="badge bg-danger">New</span>
                @endif
              </a>
            @empty
              <div class="px-3 py-4 text-center">
                <p class="mb-1">No notifications yet</p>
                <p class="fs-12px text-secondary mb-0">Payout and account updates will appear here.</p>
              </div>
            @endforelse
          </div>
          <div class="px-3 py-2 d-flex align-items-center justify-content-center border-top">
            <a href="{{ route('dashboard.notifications') }}">View all</a>
          </div>
        </div>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <img class="w-30px h-30px ms-1 rounded-circle" src="{{ url('https://placehold.co/30x30') }}" alt="profile">
        </a>
        <div class="dropdown-menu p-0" aria-labelledby="profileDropdown">
          <div class="d-flex flex-column align-items-center border-bottom px-5 py-3">
            <div class="mb-3">
              <img class="w-80px h-80px rounded-circle" src="{{ url('https://placehold.co/80x80') }}" alt="">
            </div>
            <div class="text-center">
              <p class="fs-16px fw-bolder">{{ Auth::user()->name }}</p>
              <p class="fs-12px text-secondary">{{ Auth::user()->email }}</p>
            </div>
          </div>
          <ul class="list-unstyled p-1">
            <li>
              <a href="{{ route('dashboard.profile') }}" class="dropdown-item py-2 text-body ms-0">
                <i class="me-2 icon-md" data-lucide="user"></i>
                <span>Profile</span>
              </a>
            </li>
            <li>
              <a href="{{ route('profile.edit') }}" class="dropdown-item py-2 text-body ms-0">
                <i class="me-2 icon-md" data-lucide="edit"></i>
                <span>Edit Profile</span>
              </a>
            </li>
            <li>
              <a href="javascript:;" class="dropdown-item py-2 text-body ms-0">
                <i class="me-2 icon-md" data-lucide="repeat"></i>
                <span>Switch User</span>
              </a>
            </li>
            <li>
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="dropdown-item py-2 text-body ms-0">
                  <i class="me-2 icon-md" data-lucide="log-out"></i>
                  <span>Log Out</span>
                </button>
              </form>
            </li>
          </ul>
        </div>
      </li>
    </ul>

    <a href="#" class="sidebar-toggler">
      <i data-lucide="menu"></i>
    </a>

  </div>
</nav>
