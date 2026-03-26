<nav class="navbar">
  <div class="navbar-content">
    @php
      $headerPendingProofOrder = auth()->check()
          ? auth()->user()->investmentOrders()
              ->with(['miner', 'package'])
              ->whereIn('status', ['pending', 'rejected'])
              ->whereNull('payment_proof_path')
              ->latest('submitted_at')
              ->first()
          : null;
    @endphp
    @once
      <style>
        .toolbar-payment-indicator {
          position: relative;
        }

        .toolbar-payment-indicator .toolbar-payment-badge {
          position: absolute;
          top: -4px;
          right: -8px;
          min-width: 18px;
          height: 18px;
          padding: 0 5px;
          border-radius: 999px;
          background: #f59e0b;
          color: #111827;
          font-size: 10px;
          font-weight: 700;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          animation: toolbarPaymentPulse 1.1s ease-in-out infinite;
          box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.45);
        }

        @keyframes toolbarPaymentPulse {
          0% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.45);
          }
          50% {
            transform: scale(1.08);
            box-shadow: 0 0 0 10px rgba(245, 158, 11, 0);
          }
          100% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(245, 158, 11, 0);
          }
        }
      </style>
    @endonce

    <div class="logo-mini-wrapper">
      <img src="{{ asset('branding/zag-smal.png') }}" class="logo-mini logo-mini-light" alt="ZagChain" style="width: 42px; height: 42px; object-fit: contain;">
      <img src="{{ asset('branding/zag-smal.png') }}" class="logo-mini logo-mini-dark" alt="ZagChain" style="width: 42px; height: 42px; object-fit: contain;">
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
            <p class="mb-0 fw-bold">Quick Access</p>
          </div>
          <div class="row g-0 p-1">
            <div class="col-6 text-center">
              <a href="{{ route('email.inbox') }}" class="dropdown-item d-flex flex-column align-items-center justify-content-center w-70px h-70px"><i data-lucide="mail" class="icon-lg mb-1"></i><p class="fs-12px">Email</p></a>
            </div>
            <div class="col-6 text-center">
              <a href="{{ route('dashboard.profile') }}" class="dropdown-item d-flex flex-column align-items-center justify-content-center w-70px h-70px"><i data-lucide="instagram" class="icon-lg mb-1"></i><p class="fs-12px">Profile</p></a>
            </div>
          </div>
        </div>
      </li>
      @if ($headerPendingProofOrder)
        <li class="nav-item">
          <a
            class="nav-link toolbar-payment-indicator"
            href="{{ route('dashboard.buy-shares', ['miner' => $headerPendingProofOrder->miner?->slug]) }}"
            title="Finish your pending payment for {{ $headerPendingProofOrder->package?->name }}"
          >
            <i data-lucide="badge-dollar-sign"></i>
            <span class="toolbar-payment-badge">1</span>
            <span class="visually-hidden">Finish your pending payment</span>
          </a>
        </li>
      @endif
      @php
        $headerMailbox = auth()->check()
            ? auth()->user()->receivedMessageRecords()
                ->with(['message.sender'])
                ->whereNull('deleted_at')
                ->whereNull('trashed_at')
                ->latest('created_at')
                ->take(5)
                ->get()
            : collect();
        $headerUnreadMailboxCount = auth()->check()
            ? auth()->user()->receivedMessageRecords()
                ->whereNull('deleted_at')
                ->whereNull('trashed_at')
                ->whereNull('read_at')
                ->count()
            : 0;
      @endphp
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="messageDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <i data-lucide="mail"></i>
          @if ($headerUnreadMailboxCount > 0)
            <div class="indicator">
              <div class="circle"></div>
            </div>
          @endif
        </a>
        <div class="dropdown-menu p-0" aria-labelledby="messageDropdown">
          <div class="px-3 py-2 d-flex align-items-center justify-content-between border-bottom">
            <p>{{ $headerUnreadMailboxCount }} New Message{{ $headerUnreadMailboxCount === 1 ? '' : 's' }}</p>
            <a href="{{ route('email.inbox') }}" class="text-secondary">Open inbox</a>
          </div>
          <div class="p-1">
            @forelse ($headerMailbox as $mailRecord)
              @php
                $mailMessage = $mailRecord->message;
                $mailSender = $mailMessage?->sender;
                $mailSenderName = $mailSender?->name ?? 'System';
                $mailSenderPhoto = $mailSender ? $mailSender->profilePhotoUrl() : asset('branding/zag-smal.png');
                $mailPreview = $mailMessage && $mailMessage->label
                    ? ucfirst($mailMessage->label).' message'
                    : 'Internal message';
                $mailCreatedAt = $mailMessage?->created_at?->diffForHumans();
              @endphp
              <a href="{{ route('email.read', $mailRecord) }}" class="dropdown-item d-flex align-items-center py-2">
                <div class="me-3">
                  <img class="w-30px h-30px rounded-circle" src="{{ $mailSenderPhoto }}" alt="{{ $mailSenderName }}" style="object-fit: cover;">
                </div>
                <div class="d-flex justify-content-between flex-grow-1 align-items-start gap-2">
                  <div class="me-2">
                    <p>{{ $mailSenderName }}</p>
                    <p class="fs-12px text-secondary">{{ $mailPreview }}</p>
                  </div>
                  <div class="text-end">
                    <p class="fs-12px text-secondary mb-1">{{ $mailCreatedAt }}</p>
                    @if (! $mailRecord->read_at)
                      <span class="badge bg-danger">New</span>
                    @endif
                  </div>
                </div>
              </a>
            @empty
              <div class="px-3 py-4 text-center">
                <p class="mb-1">No messages yet</p>
                <p class="fs-12px text-secondary mb-0">Your internal mailbox messages will appear here.</p>
              </div>
            @endforelse
          </div>
          <div class="px-3 py-2 d-flex align-items-center justify-content-center border-top">
            <a href="{{ route('email.inbox') }}">View all</a>
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
          <img class="w-30px h-30px ms-1 rounded-circle" src="{{ Auth::user()->profilePhotoUrl() }}" alt="{{ Auth::user()->name }}" style="object-fit: cover;">
        </a>
        <div class="dropdown-menu p-0" aria-labelledby="profileDropdown">
          <div class="d-flex flex-column align-items-center border-bottom px-5 py-3">
            <div class="mb-3">
              <img class="w-80px h-80px rounded-circle" src="{{ Auth::user()->profilePhotoUrl() }}" alt="{{ Auth::user()->name }}" style="object-fit: cover;">
            </div>
            <div class="text-center">
              <p class="fs-16px fw-bolder">{{ Auth::user()->name }}</p>
              <p class="fs-12px text-secondary">{{ Auth::user()->displayEmail() }}</p>
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
                <i class="me-2 icon-md" data-lucide="settings"></i>
                <span>Account Settings</span>
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

