@extends('marketing.layout')

@php($pageTitle = 'About')

@section('content')
<section class="section">
    <div class="shell page-grid">
        <div class="hero-card page-copy">
            <div class="section-kicker">About the business</div>
            <h1 style="font-size:clamp(2.2rem, 4vw, 4rem);">A mining platform built around clarity, structure, and growth.</h1>
            <p>The public side of the platform is designed to explain a simple story: the miner exists, shares are visible, packages are structured clearly, and the investor journey is understandable from the first visit to the first approved subscription.</p>
            <p>The business narrative combines three things into one operating model: transparent miner performance, share-based subscriptions, and network growth through referrals and team-building incentives.</p>
            <div class="metric-list">
                <div class="metric-row"><span>Featured miner</span><strong>{{ $featuredMiner->name }}</strong></div>
                <div class="metric-row"><span>Total shares</span><strong>{{ number_format($featuredMiner->total_shares) }}</strong></div>
                <div class="metric-row"><span>Share price</span><strong>${{ number_format((float) $featuredMiner->share_price, 2) }}</strong></div>
            </div>
        </div>
        <div class="panel page-copy">
            <h3>What the visitor should understand</h3>
            <div class="timeline">
                <div class="timeline-step">A package is a number of shares inside the miner</div>
                <div class="timeline-step">Returns follow the miner base rate and package uplift</div>
                <div class="timeline-step">Referral activity improves both rewards and growth</div>
                <div class="timeline-step">Operations review creates a safer investment flow</div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="shell">
        <div class="section-head">
            <div>
                <div class="section-kicker">What makes the model different</div>
                <h2>A public story that matches the real platform flow.</h2>
            </div>
            <p class="section-copy">Instead of showing only package cards, the site explains the business logic behind the product: how the miner works, how the shares are sold, how trust is built, and how network incentives support growth.</p>
        </div>
        <div class="section-grid">
            <div class="panel">
                <h3>Visible performance</h3>
                <p class="section-copy">The miner is not hidden behind generic marketing language. Visitors can see production, share price, capacity, and the return structure that drives packages.</p>
            </div>
            <div class="panel">
                <h3>Clear commercial path</h3>
                <p class="section-copy">Free Starter lowers entry resistance, paid packages create ownership, and the operations review process makes activation feel managed instead of automatic.</p>
            </div>
            <div class="panel">
                <h3>Built-in network expansion</h3>
                <p class="section-copy">The referral engine is not an extra feature. It is part of the business model, helping users unlock upgrades, earn rewards, and grow long-term value.</p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="shell page-grid">
        <div class="panel page-copy">
            <div class="section-kicker">Website purpose</div>
            <h3 style="font-size:1.6rem; margin-bottom:12px;">What the landing experience should achieve</h3>
            <div class="metric-list">
                <div class="metric-row"><span>Explain the miner</span><strong>Reduce confusion</strong></div>
                <div class="metric-row"><span>Explain shares</span><strong>Make packages concrete</strong></div>
                <div class="metric-row"><span>Explain referrals</span><strong>Show long-term upside</strong></div>
                <div class="metric-row"><span>Explain payment review</span><strong>Increase trust</strong></div>
            </div>
        </div>
        <div class="panel page-copy">
            <div class="section-kicker">Growth vision</div>
            <p>This first public website is designed to start with a single featured miner and then scale toward a broader portfolio. The structure already supports more miners, more packages, deeper network incentives, and richer media proof as the business grows.</p>
            <p>That means the public experience is not just promotional. It is meant to become the front door for a larger cloud-mining and shareholder platform.</p>
        </div>
    </div>
</section>
@endsection
