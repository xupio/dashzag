@extends('layout.master')

@section('content')
<nav class="page-breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="#">Special pages</a></li>
    <li class="breadcrumb-item active" aria-current="page">Sell Products</li>
  </ol>
</nav>

@if (session('subscription_success'))
  <div class="row">
    <div class="col-12">
      <div class="alert alert-success d-flex align-items-center justify-content-between" role="alert">
        <span>{{ session('subscription_success') }}</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    </div>
  </div>
@endif

<div class="row mb-4">
  <div class="col-12">
    <div class="card">
      <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
          <h4 class="mb-1">Buy shares in {{ $miner->name }}</h4>
          <p class="text-secondary mb-0">Choose one of the live share packages below. Every package represents real shares from the selected active miner.</p>
        </div>
        <div class="text-md-end">
          <div class="fw-semibold">Account type: <span class="text-primary text-capitalize">{{ $user->account_type }}</span></div>
          <div class="text-secondary small">Current level: {{ $level->name }} | Bonus {{ number_format((float) $level->bonus_rate * 100, 2) }}%</div>
          <div class="text-secondary small">Current package on this miner: {{ $activeInvestment?->package?->name ?? 'No active package yet' }}</div>
        </div>
      </div>
    </div>
  </div>
</div>

@if (($miners ?? collect())->count() > 1)
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div>
            <h6 class="mb-1">Choose miner</h6>
            <p class="text-secondary mb-0">Each miner has its own share pool, packages, and projected return profile.</p>
          </div>
          <div class="d-flex flex-wrap gap-2">
            @foreach ($miners as $networkMiner)
              <a href="{{ route('general.sell-products') }}?miner={{ $networkMiner->slug }}" class="btn {{ $networkMiner->id === $miner->id ? 'btn-primary' : 'btn-outline-primary' }} btn-sm">
                {{ $networkMiner->name }}
              </a>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>
@endif

<div class="row mb-4">
  <div class="col-md-4 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <p class="text-secondary mb-1">Miner share price</p>
        <h4 class="mb-0">${{ number_format((float) $miner->share_price, 2) }}</h4>
      </div>
    </div>
  </div>
  <div class="col-md-4 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <p class="text-secondary mb-1">Shares sold</p>
        <h4 class="mb-0">{{ number_format($sharesSold) }} / {{ number_format($miner->total_shares) }}</h4>
      </div>
    </div>
  </div>
  <div class="col-md-4 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <p class="text-secondary mb-1">Shares available</p>
        <h4 class="mb-0">{{ number_format($availableShares) }}</h4>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <p class="text-secondary text-center mb-4 pb-2">Each package below maps directly to a fixed number of {{ $miner->name }} shares and a projected monthly return rate.</p>
    <div class="container">
      <div class="row">
        @foreach ($packages as $index => $package)
          @php
            $accent = ['primary', 'success', 'warning'][$index] ?? 'primary';
            $icon = ['award', 'trending-up', 'briefcase'][$index] ?? 'award';
            $isCurrent = $activeInvestment?->package_id === $package->id;
          @endphp
          <div class="col-md-4 stretch-card {{ $index < count($packages) - 1 ? 'grid-margin grid-margin-md-0' : '' }}">
            <div class="card {{ $isCurrent ? 'border border-' . $accent : '' }}">
              <div class="card-body">
                <h4 class="text-center mt-3 mb-4">{{ $package->name }}</h4>
                <i data-lucide="{{ $icon }}" class="text-{{ $accent }} icon-xxl d-block mx-auto my-3"></i>
                <h1 class="text-center">${{ number_format((float) $package->price, 0) }}</h1>
                <p class="text-secondary text-center mb-4 fw-light">one-time share purchase</p>
                <h5 class="text-{{ $accent }} text-center mb-4">{{ $package->shares_count }} shares in {{ $miner->name }}</h5>
                <table class="mx-auto">
                  <tr><td><i data-lucide="check" class="icon-md text-primary me-2"></i></td><td><p class="mb-2">Projected monthly return {{ number_format((float) $package->monthly_return_rate * 100, 2) }}%</p></td></tr>
                  <tr><td><i data-lucide="check" class="icon-md text-primary me-2"></i></td><td><p class="mb-2">Equivalent units {{ $package->units_limit }}</p></td></tr>
                  <tr><td><i data-lucide="check" class="icon-md text-primary me-2"></i></td><td><p class="mb-2">Active miner: {{ $miner->name }}</p></td></tr>
                  <tr><td><i data-lucide="check" class="icon-md text-primary me-2"></i></td><td><p class="mb-2">Level and team bonuses apply after purchase</p></td></tr>
                </table>
                <div class="d-grid mt-4">
                  <form method="POST" action="{{ route('general.sell-products.subscribe') }}">
                    @csrf
                    <input type="hidden" name="package" value="{{ $package->slug }}">
                    <button class="btn btn-{{ $accent }}" type="submit">{{ $isCurrent ? 'Buy again' : 'Subscribe' }}</button>
                  </form>
                </div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
</div>

@if ($activeInvestment)
  <div class="row mt-4">
    <div class="col-12">
      <div class="card">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div>
            <h5 class="mb-1">Latest active investment on {{ $miner->name }}</h5>
            <p class="text-secondary mb-0">{{ $activeInvestment->package?->name }} | {{ $activeInvestment->shares_owned }} shares | {{ number_format(((float) $activeInvestment->monthly_return_rate + (float) $activeInvestment->level_bonus_rate) * 100, 2) }}% total monthly return target</p>
          </div>
          <a href="{{ route('dashboard') }}?miner={{ $miner->slug }}" class="btn btn-outline-primary">View dashboard</a>
        </div>
      </div>
    </div>
  </div>
@endif
@endsection

