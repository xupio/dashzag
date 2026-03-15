<div class="aside-content">
  <div class="d-flex align-items-center justify-content-between">
    <button class="navbar-toggle btn btn-icon border d-block d-lg-none" data-bs-target=".email-aside-nav" data-bs-toggle="collapse" type="button">
      <span class="icon"><i data-lucide="chevron-down"></i></span>
    </button>
    <div class="order-first">
      <h4>Mail Service</h4>
      <p class="text-secondary">{{ $mailIdentity }}</p>
    </div>
  </div>
  <div class="d-grid my-3">
    <a class="btn btn-primary" href="{{ route('email.compose') }}">Compose Email</a>
  </div>
  <div class="email-aside-nav collapse">
    <ul class="nav flex-column">
      <li class="nav-item {{ $folder === 'inbox' ? 'active' : '' }}">
        <a class="nav-link d-flex align-items-center" href="{{ route('email.inbox') }}">
          <i data-lucide="inbox" class="icon-lg me-2"></i>
          Inbox
          <span class="badge bg-danger fw-bolder ms-auto">{{ $messageCounts['unread'] }}</span>
        </a>
      </li>
      <li class="nav-item {{ $folder === 'starred' ? 'active' : '' }}">
        <a class="nav-link d-flex align-items-center" href="{{ route('email.starred') }}">
          <i data-lucide="star" class="icon-lg me-2"></i>
          Starred
          <span class="badge bg-warning text-dark fw-bolder ms-auto">{{ $messageCounts['starred'] }}</span>
        </a>
      </li>
      <li class="nav-item {{ $folder === 'archived' ? 'active' : '' }}">
        <a class="nav-link d-flex align-items-center" href="{{ route('email.archived') }}">
          <i data-lucide="archive" class="icon-lg me-2"></i>
          Archive
          <span class="badge bg-secondary fw-bolder ms-auto">{{ $messageCounts['archived'] }}</span>
        </a>
      </li>
      <li class="nav-item {{ $folder === 'trash' ? 'active' : '' }}">
        <a class="nav-link d-flex align-items-center" href="{{ route('email.trash') }}">
          <i data-lucide="trash-2" class="icon-lg me-2"></i>
          Trash
          <span class="badge bg-danger fw-bolder ms-auto">{{ $messageCounts['trash'] ?? 0 }}</span>
        </a>
      </li>
      <li class="nav-item {{ $folder === 'drafts' ? 'active' : '' }}">
        <a class="nav-link d-flex align-items-center" href="{{ route('email.drafts') }}">
          <i data-lucide="file-pen-line" class="icon-lg me-2"></i>
          Drafts
          <span class="badge bg-info text-dark fw-bolder ms-auto">{{ $messageCounts['drafts'] }}</span>
        </a>
      </li>
      <li class="nav-item {{ $folder === 'sent' ? 'active' : '' }}">
        <a class="nav-link d-flex align-items-center" href="{{ route('email.sent') }}">
          <i data-lucide="send" class="icon-lg me-2"></i>
          Sent Mail
          <span class="badge bg-secondary fw-bolder ms-auto">{{ $messageCounts['sent'] }}</span>
        </a>
      </li>
      <li class="nav-item {{ $folder === 'compose' ? 'active' : '' }}">
        <a class="nav-link d-flex align-items-center" href="{{ route('email.compose') }}">
          <i data-lucide="square-pen" class="icon-lg me-2"></i>
          Compose
        </a>
      </li>
    </ul>
    <p class="text-secondary fs-12px fw-bolder text-uppercase mb-2 mt-4">Message stats</p>
    <ul class="nav flex-column">
      <li class="nav-item">
        <a class="nav-link d-flex align-items-center" href="javascript:;">
          <i data-lucide="mail-open" class="text-primary icon-lg me-2"></i>
          Total inbox
          <span class="ms-auto">{{ $messageCounts['inbox'] }}</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link d-flex align-items-center" href="javascript:;">
          <i data-lucide="mail-warning" class="text-warning icon-lg me-2"></i>
          Unread
          <span class="ms-auto">{{ $messageCounts['unread'] }}</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link d-flex align-items-center" href="javascript:;">
          <i data-lucide="star" class="text-warning icon-lg me-2"></i>
          Starred
          <span class="ms-auto">{{ $messageCounts['starred'] }}</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link d-flex align-items-center" href="javascript:;">
          <i data-lucide="archive" class="text-secondary icon-lg me-2"></i>
          Archived
          <span class="ms-auto">{{ $messageCounts['archived'] }}</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link d-flex align-items-center" href="javascript:;">
          <i data-lucide="file-pen-line" class="text-info icon-lg me-2"></i>
          Drafts
          <span class="ms-auto">{{ $messageCounts['drafts'] }}</span>
        </a>
      </li>
    </ul>
  </div>
</div>
