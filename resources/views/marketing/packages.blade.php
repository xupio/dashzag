@extends('marketing.layout')

@php($pageTitle = 'Packages')

@section('content')
<section class="section">
    <div class="shell">
        <div class="section-head">
            <div>
                <div class="section-kicker">Package lineup</div>
                <h1 style="font-size:clamp(2.2rem, 4vw, 4rem);">Choose the package that fits your pace</h1>
            </div>
            <p class="section-copy">Clear pricing, clear share count, and a cleaner way to understand how each level sits inside the ZagChain journey.</p>
        </div>
        <div class="package-grid">
            @foreach ($packages as $package)
                <article class="package-card {{ $loop->index === 1 ? 'featured' : '' }}">
                    <span class="tag">{{ $package->shares_count }} shares</span>
                    <h3 style="margin-top:16px;">{{ $package->name }}</h3>
                    <div class="price">${{ number_format((float) $package->price, 0) }}</div>
                    <p class="section-copy">Built on the featured miner rate of {{ number_format($featuredMiner->base_monthly_return_rate * 100, 2) }}% plus the uplift for this level.</p>
                    <div class="metric-list">
                        <div class="metric-row"><span>Monthly return</span><strong>{{ number_format($package->monthly_return_rate * 100, 2) }}%</strong></div>
                        <div class="metric-row"><span>Units limit</span><strong>{{ $package->units_limit }}</strong></div>
                        <div class="metric-row"><span>Package uplift</span><strong>{{ number_format((($package->monthly_return_rate - $featuredMiner->base_monthly_return_rate) * 100), 2) }}%</strong></div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="section">
    <div class="shell page-grid">
        <div class="panel page-copy">
            <div class="section-kicker">How to read the packages</div>
            <div class="timeline">
                <div class="timeline-step">Price shows how much capital enters the miner</div>
                <div class="timeline-step">Shares show what portion of the miner is held</div>
                <div class="timeline-step">Monthly return shows the current projected structure</div>
                <div class="timeline-step">Package uplift explains why higher tiers return more</div>
            </div>
        </div>
        <div class="panel page-copy">
            <div class="section-kicker">Conversion strategy</div>
            <p>Packages should do more than show numbers. They should help the visitor understand the path from free entry to stronger ownership as confidence grows.</p>
            <div class="metric-list">
                <div class="metric-row"><span>Starter Free</span><strong>Entry and referral unlock</strong></div>
                <div class="metric-row"><span>Basic 100</span><strong>First paid ownership step</strong></div>
                <div class="metric-row"><span>Growth / Scale</span><strong>Stronger capital and return profile</strong></div>
            </div>
        </div>
    </div>
</section>
@endsection
