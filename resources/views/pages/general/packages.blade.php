@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h4 class="mb-1">Packages</h4>
        <p class="text-secondary mb-0">Manage the public investment packages shown to users on the sell products page.</p>
      </div>
      <a href="{{ route('general.sell-products') }}" class="btn btn-outline-primary btn-icon-text">
        <i data-lucide="shopping-bag" class="btn-icon-prepend"></i> View public packages
      </a>
    </div>
  </div>
</div>

@if (session('packages_success'))
  <div class="alert alert-success">{{ session('packages_success') }}</div>
@endif

@if (session('packages_error'))
  <div class="alert alert-warning">{{ session('packages_error') }}</div>
@endif

<div class="row mb-4">
  <div class="col-12 stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h5 class="mb-1">Create package</h5>
            <p class="text-secondary mb-0">Attach a new package to any miner and make it available immediately.</p>
          </div>
        </div>

        <form method="POST" action="{{ route('dashboard.packages.store') }}" class="row g-3 align-items-end">
          @csrf
          <div class="col-md-3">
            <label class="form-label">Miner</label>
            <select name="miner_id" class="form-select @error('miner_id') is-invalid @enderror" required>
              <option value="">Select miner</option>
              @foreach ($miners as $miner)
                <option value="{{ $miner->id }}" @selected(old('miner_id') == $miner->id)>{{ $miner->name }}</option>
              @endforeach
            </select>
            @error('miner_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-3">
            <label class="form-label">Slug</label>
            <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug') }}" required>
            @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-3">
            <label class="form-label">Price</label>
            <input type="number" step="0.01" min="1" name="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price') }}" required>
            @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-2">
            <label class="form-label">Shares</label>
            <input type="number" min="1" name="shares_count" class="form-control @error('shares_count') is-invalid @enderror" value="{{ old('shares_count', 1) }}" required>
            @error('shares_count')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-2">
            <label class="form-label">Units</label>
            <input type="number" min="1" name="units_limit" class="form-control @error('units_limit') is-invalid @enderror" value="{{ old('units_limit', 1) }}" required>
            @error('units_limit')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-2">
            <label class="form-label">Order</label>
            <input type="number" min="1" name="display_order" class="form-control @error('display_order') is-invalid @enderror" value="{{ old('display_order', 1) }}" required>
            @error('display_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-3">
            <label class="form-label">Return rate</label>
            <input type="number" step="0.0001" min="0" max="1" name="monthly_return_rate" class="form-control @error('monthly_return_rate') is-invalid @enderror" value="{{ old('monthly_return_rate', 0.08) }}" required>
            @error('monthly_return_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-3">
            <div class="form-check mt-4">
              <input class="form-check-input" type="checkbox" value="1" name="is_active" id="new_package_active" @checked(old('is_active', true))>
              <label class="form-check-label" for="new_package_active">Active package</label>
            </div>
          </div>
          <div class="col-md-12">
            <button type="submit" class="btn btn-primary">Create package</button>
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
            <h5 class="mb-1">Package settings</h5>
            <p class="text-secondary mb-0">Edit price, shares, return rate, order, visibility, and cleanup controls for every package.</p>
          </div>
          <span class="badge bg-primary">{{ $packages->count() }} packages</span>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Package</th>
                <th>Miner</th>
                <th>Configuration</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($packages as $package)
                <tr>
                  <td>
                    <div class="fw-semibold">{{ $package->name }}</div>
                    <div class="text-secondary small">{{ $package->slug }}</div>
                  </td>
                  <td>{{ $package->miner?->name ?? '—' }}</td>
                  <td style="min-width: 560px;">
                    <form method="POST" action="{{ route('dashboard.packages.update', $package) }}" class="row g-2 align-items-end">
                      @csrf
                      <div class="col-md-4">
                        <label class="form-label small">Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $package->name) }}" required>
                      </div>
                      <div class="col-md-2">
                        <label class="form-label small">Price</label>
                        <input type="number" step="0.01" min="1" name="price" class="form-control" value="{{ old('price', $package->price) }}" required>
                      </div>
                      <div class="col-md-2">
                        <label class="form-label small">Shares</label>
                        <input type="number" min="1" name="shares_count" class="form-control" value="{{ old('shares_count', $package->shares_count) }}" required>
                      </div>
                      <div class="col-md-2">
                        <label class="form-label small">Units</label>
                        <input type="number" min="1" name="units_limit" class="form-control" value="{{ old('units_limit', $package->units_limit) }}" required>
                      </div>
                      <div class="col-md-2">
                        <label class="form-label small">Order</label>
                        <input type="number" min="1" name="display_order" class="form-control" value="{{ old('display_order', $package->display_order) }}" required>
                      </div>
                      <div class="col-md-3">
                        <label class="form-label small">Return rate</label>
                        <input type="number" step="0.0001" min="0" max="1" name="monthly_return_rate" class="form-control" value="{{ old('monthly_return_rate', $package->monthly_return_rate) }}" required>
                      </div>
                      <div class="col-md-3">
                        <div class="form-check mt-4">
                          <input class="form-check-input" type="checkbox" value="1" name="is_active" id="active_{{ $package->id }}" @checked($package->is_active)>
                          <label class="form-check-label" for="active_{{ $package->id }}">Active package</label>
                        </div>
                      </div>
                      <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">Save package</button>
                      </div>
                    </form>
                  </td>
                  <td>
                    <span class="badge {{ $package->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $package->is_active ? 'Active' : 'Hidden' }}</span>
                    <div class="text-secondary small mt-1">{{ $package->investments->count() }} investments</div>
                  </td>
                  <td>
                    <div class="d-flex gap-2 flex-wrap">
                      <form method="POST" action="{{ route('dashboard.packages.archive', $package) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-warning">Archive</button>
                      </form>
                      <form method="POST" action="{{ route('dashboard.packages.delete', $package) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger" @disabled($package->investments->count() > 0)>Delete</button>
                      </form>
                    </div>
                    <div class="text-secondary small mt-2">
                      {{ $package->investments->count() > 0 ? 'Delete is disabled once investors have used this package.' : 'Unused packages can be deleted permanently.' }}
                    </div>
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
