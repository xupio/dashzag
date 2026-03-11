@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h4 class="mb-1">Users</h4>
        <p class="text-secondary mb-0">Manage registered users, monitor their level and investment totals, and update admin access.</p>
      </div>
      <a href="{{ route('dashboard.operations') }}" class="btn btn-outline-primary btn-icon-text">
        <i data-lucide="briefcase-business" class="btn-icon-prepend"></i> Operations
      </a>
    </div>
  </div>
</div>

@if (session('users_success'))
  <div class="alert alert-success">{{ session('users_success') }}</div>
@endif

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
                <th>Level</th>
                <th>Account type</th>
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
                  <td>{{ $listedUser->userLevel?->name ?? 'Starter' }}</td>
                  <td class="text-capitalize">{{ $listedUser->account_type }}</td>
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
