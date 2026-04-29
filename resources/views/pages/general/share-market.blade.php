@extends('layout.master')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin gap-3">
  <div>
    <h4 class="mb-1">Share Market</h4>
    <p class="text-secondary mb-0">Manage your miner holdings, list shares for resale, and buy from active listings.</p>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <div class="text-end">
      <div class="small text-secondary">Available wallet balance</div>
      <div class="fw-semibold">${{ number_format((float) ($walletSummary['available'] ?? 0), 2) }}</div>
    </div>
    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">Back to dashboard</a>
  </div>
</div>

@if (session('status'))
  <div class="alert alert-success">{{ session('status') }}</div>
@endif

@if ($errors->has('share_market'))
  <div class="alert alert-danger">{{ $errors->first('share_market') }}</div>
@endif

<div class="row">
  <div class="col-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h6 class="card-title mb-1">Miner Market Overview</h6>
            <p class="text-secondary small mb-0">Track sell-through, maturity timing, and which miners are already open for share resale.</p>
          </div>
          <span class="text-secondary small">{{ $miners->count() }} miner{{ $miners->count() === 1 ? '' : 's' }}</span>
        </div>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>Miner</th>
                <th>Status</th>
                <th>Shares sold</th>
                <th>Progress</th>
                <th>Maturity</th>
                <th>Secondary market</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($miners as $miner)
                <tr>
                  <td>
                    <div class="fw-semibold">{{ $miner->name }}</div>
                    <div class="text-secondary small">${{ number_format((float) $miner->share_price, 2) }} per share</div>
                  </td>
                  <td>
                    @php
                      $statusClass = match ($miner->status) {
                        'secondary_market_open' => 'bg-success-subtle text-success',
                        'sold_out', 'mature' => 'bg-info-subtle text-info',
                        'nearly_full' => 'bg-warning-subtle text-warning',
                        default => 'bg-light text-dark',
                      };
                    @endphp
                    <span class="badge {{ $statusClass }}">{{ ucfirst($miner->status_label) }}</span>
                  </td>
                  <td>{{ number_format($miner->shares_sold) }} / {{ number_format($miner->total_shares) }}</td>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <div class="progress flex-grow-1" style="height: 8px; min-width: 120px;">
                        <div
                          class="progress-bar {{ $miner->sold_percent >= 100 ? 'bg-success' : ($miner->sold_percent >= $miner->near_capacity_threshold_percent ? 'bg-warning' : 'bg-primary') }}"
                          role="progressbar"
                          style="width: {{ min(100, $miner->sold_percent) }}%;"
                          aria-valuenow="{{ $miner->sold_percent }}"
                          aria-valuemin="0"
                          aria-valuemax="100"
                        ></div>
                      </div>
                      <span class="text-secondary small">{{ number_format($miner->sold_percent, 1) }}%</span>
                    </div>
                  </td>
                  <td>
                    @if ($miner->status === 'sold_out' && $miner->maturity_due_at)
                      <div class="fw-semibold">{{ $miner->maturity_due_at->toFormattedDateString() }}</div>
                      <div class="text-secondary small">{{ $miner->days_until_maturity }} day{{ $miner->days_until_maturity === 1 ? '' : 's' }} remaining</div>
                    @elseif ($miner->status === 'mature' || $miner->status === 'secondary_market_open')
                      <div class="fw-semibold">Matured</div>
                      <div class="text-secondary small">{{ optional($miner->matured_at)->toFormattedDateString() ?: 'Ready' }}</div>
                    @else
                      <span class="text-secondary small">Waiting for sell out</span>
                    @endif
                  </td>
                  <td>
                    @if ($miner->is_tradable)
                      <div class="fw-semibold text-success">Open</div>
                      <div class="text-secondary small">Fee: {{ number_format((float) $miner->secondary_market_fee_percent, 2) }}%</div>
                    @else
                      <div class="fw-semibold text-secondary">Not open yet</div>
                      <div class="text-secondary small">
                        @if ($miner->status === 'mature')
                          Opening on next lifecycle sync
                        @elseif ($miner->status === 'sold_out')
                          Opens after maturity
                        @else
                          Primary sales phase
                        @endif
                      </div>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center text-secondary py-4">No miners available yet.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-4 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-3">Create Listing</h6>
        <p class="text-secondary small mb-3">Listings are available only for miners whose secondary market is already open.</p>
        <form method="POST" action="{{ route('share-market.listings.store') }}" class="d-grid gap-3">
          @csrf
          <div>
            <label for="miner_id" class="form-label">Miner</label>
            <select id="miner_id" name="miner_id" class="form-select" required>
              <option value="">Choose a miner</option>
              @foreach ($holdings as $holding)
                <option value="{{ $holding->miner_id }}" @selected(old('miner_id') == $holding->miner_id)>
                  {{ $holding->miner?->name }} ({{ str_replace('_', ' ', $holding->miner?->status ?? 'unknown') }}, Transferable: {{ $holding->transferable_quantity }})
                </option>
              @endforeach
            </select>
          </div>
          <div class="row g-3">
            <div class="col-6">
              <label for="quantity" class="form-label">Quantity</label>
              <input id="quantity" type="number" min="1" step="1" name="quantity" value="{{ old('quantity') }}" class="form-control" required>
            </div>
            <div class="col-6">
              <label for="price_per_share" class="form-label">Price / Share</label>
              <input id="price_per_share" type="number" min="0.01" step="0.01" name="price_per_share" value="{{ old('price_per_share') }}" class="form-control" required>
            </div>
          </div>
          <button type="submit" class="btn btn-primary">Create listing</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-xl-8 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="card-title mb-0">My Holdings</h6>
          <span class="text-secondary small">{{ $holdings->count() }} position{{ $holdings->count() === 1 ? '' : 's' }}</span>
        </div>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>Miner</th>
                <th>Total shares</th>
                <th>Locked</th>
                <th>Transferable</th>
                <th>Avg buy price</th>
                <th>Market state</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($holdings as $holding)
                <tr>
                  <td>{{ $holding->miner?->name }}</td>
                  <td>{{ number_format($holding->quantity) }}</td>
                  <td>{{ number_format($holding->locked_quantity) }}</td>
                  <td>{{ number_format($holding->transferable_quantity) }}</td>
                  <td>${{ number_format((float) $holding->avg_buy_price, 2) }}</td>
                  <td>
                    @if ($holding->miner?->status === 'secondary_market_open')
                      <span class="badge bg-success-subtle text-success">Tradable</span>
                    @elseif ($holding->miner?->status === 'sold_out')
                      <span class="badge bg-info-subtle text-info">Maturing</span>
                    @elseif ($holding->miner?->status === 'nearly_full')
                      <span class="badge bg-warning-subtle text-warning">Nearly full</span>
                    @else
                      <span class="badge bg-light text-dark">{{ ucfirst(str_replace('_', ' ', $holding->miner?->status ?? 'open')) }}</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center text-secondary py-4">No holdings yet.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-xl-5 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="card-title mb-0">My Listings</h6>
          <span class="text-secondary small">{{ $myListings->count() }} total</span>
        </div>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>Miner</th>
                <th>Remaining</th>
                <th>Price</th>
                <th>Status</th>
                <th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($myListings as $listing)
                <tr>
                  <td>{{ $listing->miner?->name }}</td>
                  <td>{{ number_format($listing->remaining_quantity) }}</td>
                  <td>${{ number_format((float) $listing->price_per_share, 2) }}</td>
                  <td><span class="badge bg-light text-dark">{{ str_replace('_', ' ', $listing->status) }}</span></td>
                  <td class="text-end">
                    @if (in_array($listing->status, ['active', 'partially_sold'], true))
                      <form method="POST" action="{{ route('share-market.listings.cancel', $listing) }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm">Cancel</button>
                      </form>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center text-secondary py-4">No listings yet.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-7 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="card-title mb-0">Active Market Listings</h6>
          <div class="text-end">
            <div class="text-secondary small">{{ $activeListings->count() }} open</div>
            <div class="text-secondary small">Buys use available wallet earnings only</div>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>Miner</th>
                <th>Seller</th>
                <th>Available</th>
                <th>Price / Share</th>
                <th>Total</th>
                <th>Fee</th>
                <th class="text-end">Buy</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($activeListings as $listing)
                <tr>
                  <td>{{ $listing->miner?->name }}</td>
                  <td>{{ $listing->seller?->name }}</td>
                  <td>{{ number_format($listing->remaining_quantity) }}</td>
                  <td>${{ number_format((float) $listing->price_per_share, 2) }}</td>
                  <td>${{ number_format((float) $listing->listing_total, 2) }}</td>
                  <td>{{ number_format((float) $listing->platform_fee_percent, 2) }}%</td>
                  <td class="text-end">
                    @if ($listing->seller_user_id !== auth()->id())
                      <div class="d-flex flex-column align-items-end gap-1">
                        <form method="POST" action="{{ route('share-market.listings.buy', $listing) }}" class="d-flex justify-content-end gap-2">
                          @csrf
                          <input
                            type="number"
                            min="1"
                            max="{{ max(1, $listing->max_affordable_quantity) }}"
                            name="quantity"
                            value="{{ min(1, max(1, $listing->max_affordable_quantity)) }}"
                            class="form-control form-control-sm"
                            style="max-width: 90px;"
                            @disabled($listing->max_affordable_quantity < 1)
                          >
                          <button type="submit" class="btn btn-primary btn-sm" @disabled($listing->max_affordable_quantity < 1)>Buy</button>
                        </form>
                        @if ($listing->max_affordable_quantity < 1)
                          <span class="text-danger small">Not enough available earnings</span>
                        @elseif ($listing->max_affordable_quantity < $listing->remaining_quantity)
                          <span class="text-secondary small">You can currently afford up to {{ $listing->max_affordable_quantity }} share{{ $listing->max_affordable_quantity === 1 ? '' : 's' }}</span>
                        @else
                          <span class="text-success small">Fully affordable from wallet</span>
                        @endif
                      </div>
                    @else
                      <span class="text-secondary small">Your listing</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center text-secondary py-4">No active listings.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
