@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h4 class="mb-1">Users</h4>
        <p class="text-secondary mb-0">Manage registered users, monitor their level and investment totals, and update admin access.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('dashboard.users.export', ['search' => $search, 'role' => $selectedRole, 'account_type' => $selectedAccountType, 'verification' => $selectedVerification, 'reward_cap' => $selectedRewardCap]) }}" class="btn btn-outline-success btn-icon-text">
          <i data-lucide="download" class="btn-icon-prepend"></i> Export CSV
        </a>
        <a href="{{ route('dashboard.operations') }}" class="btn btn-outline-primary btn-icon-text">
          <i data-lucide="briefcase-business" class="btn-icon-prepend"></i> Operations
        </a>
      </div>
    </div>
  </div>
</div>

@if (session('users_success'))
  <div class="alert alert-success">{{ session('users_success') }}</div>
@endif

<div class="row mb-4">
  <div class="col-md-3 grid-margin stretch-card"><div class="card"><div class="card-body"><p class="text-secondary mb-1">Admins</p><h4 class="mb-0">{{ $userBreakdown['admins'] }}</h4></div></div></div>
  <div class="col-md-3 grid-margin stretch-card"><div class="card"><div class="card-body"><p class="text-secondary mb-1">Standard users</p><h4 class="mb-0">{{ $userBreakdown['users'] }}</h4></div></div></div>
  <div class="col-md-3 grid-margin stretch-card"><div class="card"><div class="card-body"><p class="text-secondary mb-1">Verified</p><h4 class="mb-0">{{ $userBreakdown['verified'] }}</h4></div></div></div>
  <div class="col-md-3 grid-margin stretch-card"><div class="card"><div class="card-body"><p class="text-secondary mb-1">Shareholders</p><h4 class="mb-0">{{ $userBreakdown['shareholders'] }}</h4></div></div></div>
</div>

<div class="row mb-4">
  @foreach ($rewardCapBreakdown as $capKey => $cap)
    <div class="col-md-4 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <a href="{{ route('dashboard.users', array_filter(['search' => $search, 'role' => $selectedRole, 'account_type' => $selectedAccountType, 'verification' => $selectedVerification, 'reward_cap' => $capKey])) }}" class="text-decoration-none">
            <p class="text-secondary mb-1">{{ $cap['label'] }}</p>
            <h5 class="mb-1">{{ $cap['count'] }}</h5>
            <div class="small {{ $selectedRewardCap === $capKey ? 'text-primary fw-semibold' : 'text-secondary' }}">{{ $cap['short'] }}</div>
          </a>
        </div>
      </div>
    </div>
  @endforeach
</div>

<div class="row mb-4">
  <div class="col-12 stretch-card">
    <div class="card">
      <div class="card-body">
        <form method="GET" action="{{ route('dashboard.users') }}" class="row g-3 align-items-end">
          <div class="col-md-4">
            <label class="form-label">Search</label>
            <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Name or email">
          </div>
          <div class="col-md-2">
            <label class="form-label">Role</label>
            <select name="role" class="form-select">
              <option value="">All roles</option>
              <option value="user" @selected($selectedRole === 'user')>User</option>
              <option value="admin" @selected($selectedRole === 'admin')>Admin</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Account type</label>
            <select name="account_type" class="form-select">
              <option value="">All account types</option>
              <option value="user" @selected($selectedAccountType === 'user')>User</option>
              <option value="shareholder" @selected($selectedAccountType === 'shareholder')>Shareholder</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Verification</label>
            <select name="verification" class="form-select">
              <option value="">All</option>
              <option value="verified" @selected($selectedVerification === 'verified')>Verified</option>
              <option value="unverified" @selected($selectedVerification === 'unverified')>Unverified</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Reward cap</label>
            <select name="reward_cap" class="form-select">
              <option value="all" @selected($selectedRewardCap === 'all')>All caps</option>
              <option value="basic" @selected($selectedRewardCap === 'basic')>4% cap</option>
              <option value="growth" @selected($selectedRewardCap === 'growth')>6% cap</option>
              <option value="scale" @selected($selectedRewardCap === 'scale')>7% cap</option>
            </select>
          </div>
          <div class="col-md-1 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Apply</button>
          </div>
        </form>
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
            <h5 class="mb-1">Registered users</h5>
            <p class="text-secondary mb-0">Operational overview of every registered account in the mining platform.</p>
          </div>
          <span class="badge bg-primary">{{ $users->count() }} users</span>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>User</th>
                <th>Role</th>
                <th>Verification</th>
                <th>Level</th>
                <th>Account type</th>
                <th>Reward caps</th>
                <th>Total invested</th>
                <th>Available earnings</th>
                <th>Joined</th>
                <th>Update role</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($users as $listedUser)
                <tr>
                  <td>
                    <div class="fw-semibold">{{ $listedUser->name }}</div>
                    <div class="text-secondary small">{{ $listedUser->email }}</div>
                  </td>
                  <td><span class="badge {{ $listedUser->role === 'admin' ? 'bg-danger' : 'bg-secondary' }}">{{ ucfirst($listedUser->role) }}</span></td>
                  <td><span class="badge {{ $listedUser->email_verified_at ? 'bg-success' : 'bg-warning text-dark' }}">{{ $listedUser->email_verified_at ? 'Verified' : 'Pending' }}</span></td>
                  <td>{{ $listedUser->userLevel?->name ?? 'Starter' }}</td>
                  <td class="text-capitalize">{{ $listedUser->account_type }}</td>
                  <td>
                    @php($caps = $userRewardCaps[$listedUser->id] ?? [])
                    @if (! empty($caps))
                      <div class="d-flex flex-wrap gap-1">
                        @foreach ($caps as $cap)
                          <span class="badge bg-info-subtle text-info border border-info-subtle">{{ $cap['short'] }}</span>
                        @endforeach
                      </div>
                    @else
                      <span class="text-secondary">—</span>
                    @endif
                  </td>
                  <td>${{ number_format((float) $listedUser->investments->where('status', 'active')->sum('amount'), 2) }}</td>
                  <td>${{ number_format((float) $listedUser->earnings->where('status', 'available')->sum('amount'), 2) }}</td>
                  <td>{{ $listedUser->created_at?->format('M d, Y') }}</td>
                  <td>
                    <form method="POST" action="{{ route('dashboard.users.role', $listedUser) }}" class="d-flex gap-2 align-items-center">
                      @csrf
                      <select name="role" class="form-select form-select-sm" style="min-width: 110px;">
                        <option value="user" @selected($listedUser->role === 'user')>User</option>
                        <option value="admin" @selected($listedUser->role === 'admin')>Admin</option>
                      </select>
                      <button type="submit" class="btn btn-sm btn-primary">Save</button>
                    </form>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
