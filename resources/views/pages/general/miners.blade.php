@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h4 class="mb-1">Miner Catalog</h4>
        <p class="text-secondary mb-0">Create new mining units, review share capacity, and jump into detailed miner operations.</p>
      </div>
      <a href="{{ route('dashboard.miner') }}" class="btn btn-outline-primary btn-icon-text">
        <i data-lucide="cpu" class="btn-icon-prepend"></i> Open selected miner
      </a>
    </div>
  </div>
</div>

@if (session('miners_success'))
  <div class="alert alert-success">{{ session('miners_success') }}</div>
@endif

<div class="row mb-4">
  <div class="col-xl-5 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h5 class="mb-3">Create miner</h5>
        <form method="POST" action="{{ route('dashboard.miners.store') }}">
          @csrf
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Name</label>
              <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
              @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label">Slug</label>
              <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug') }}" placeholder="optional-custom-slug">
              @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
              @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label">Total shares</label>
              <input type="number" min="1" name="total_shares" class="form-control @error('total_shares') is-invalid @enderror" value="{{ old('total_shares', 1000) }}" required>
              @error('total_shares')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label">Share price</label>
              <input type="number" step="0.01" min="1" name="share_price" class="form-control @error('share_price') is-invalid @enderror" value="{{ old('share_price', 100) }}" required>
              @error('share_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label">Daily output (USD)</label>
              <input type="number" step="0.01" min="0" name="daily_output_usd" class="form-control @error('daily_output_usd') is-invalid @enderror" value="{{ old('daily_output_usd', 1200) }}" required>
              @error('daily_output_usd')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label">Monthly output (USD)</label>
              <input type="number" step="0.01" min="0" name="monthly_output_usd" class="form-control @error('monthly_output_usd') is-invalid @enderror" value="{{ old('monthly_output_usd', 36000) }}" required>
              @error('monthly_output_usd')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label">Base monthly return rate</label>
              <input type="number" step="0.0001" min="0" max="1" name="base_monthly_return_rate" class="form-control @error('base_monthly_return_rate') is-invalid @enderror" value="{{ old('base_monthly_return_rate', 0.08) }}" required>
              @error('base_monthly_return_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label">Status</label>
              <select name="status" class="form-select @error('status') is-invalid @enderror">
                @foreach (['active', 'maintenance', 'paused'] as $status)
                  <option value="{{ $status }}" @selected(old('status', 'active') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
              </select>
              @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-primary">Create miner</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-xl-7 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h5 class="mb-1">Active miners</h5>
            <p class="text-secondary mb-0">Every miner below is available for dashboard tracking and share sales.</p>
          </div>
          <span class="badge bg-primary">{{ $miners->count() }} miners</span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Miner</th>
                <th>Status</th>
                <th>Share price</th>
                <th>Shares sold</th>
                <th>Packages</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @foreach ($miners as $miner)
                <tr>
                  <td>
                    <div class="fw-semibold">{{ $miner->name }}</div>
                    <div class="text-secondary small">{{ $miner->slug }}</div>
                  </td>
                  <td><span class="badge bg-light text-dark text-capitalize">{{ $miner->status }}</span></td>
                  <td>${{ number_format((float) $miner->share_price, 2) }}</td>
                  <td>{{ number_format((int) $miner->investments->where('status', 'active')->sum('shares_owned')) }} / {{ number_format($miner->total_shares) }}</td>
                  <td>{{ $miner->packages->count() }}</td>
                  <td class="text-end">
                    <a href="{{ route('dashboard.miner') }}?miner={{ $miner->slug }}" class="btn btn-sm btn-outline-primary">Manage</a>
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
