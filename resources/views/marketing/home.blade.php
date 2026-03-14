@extends('marketing.layout')

@section('content')
<section class="hero">
    <div class="shell hero-grid">
        <div class="hero-card">
            <span class="eyebrow">Cloud mining subscriptions</span>
            <h1>Build confidence first, then grow investors into shareholders.</h1>
            <p class="lead">{{ $featuredMiner->name }} is presented as a transparent mining product with visible performance, share-based subscription packages, referral-powered growth, and a dashboard journey that makes the business easy to understand from the first visit.</p>
            <div class="hero-actions">
                <a href="{{ route('register') }}" class="btn btn-primary">Start with Free Starter</a>
                <a href="{{ route('marketing.packages') }}" class="btn btn-soft">Explore packages</a>
            </div>
            <div class="stat-grid">
                <div class="stat-card">
                    <div class="stat-label">Base monthly return</div>
                    <div class="stat-value">{{ number_format($featuredMiner->base_monthly_return_rate * 100, 2) }}%</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Sold shares</div>
                    <div class="stat-value">{{ number_format($sharesSold) }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Available shares</div>
                    <div class="stat-value">{{ number_format($availableShares) }}</div>
                </div>
            </div>
        </div>
        <div class="hero-card hero-side">
            <div class="hero-media-stage">
                <div class="hero-video-card" id="marketingHeroSlider">
                    <div class="hero-video-slide active" style="background-image: linear-gradient(135deg, rgba(101,113,255,0.24), rgba(6,12,23,0.12)), url('{{ asset('build/images/others/placeholder.jpg') }}');">
                        <div class="hero-video-overlay">
                            <div class="hero-video-meta">
                                <span class="hero-video-pill">ZagChain media</span>
                                <span class="hero-video-pill">Miner story</span>
                            </div>
                            <div style="display:flex; justify-content:center; align-items:center; flex:1;">
                                <div class="hero-play-button">
                                    <i data-lucide="play"></i>
                                </div>
                            </div>
                            <div class="hero-video-title">
                                <h3>{{ $featuredMiner->name }} infrastructure preview</h3>
                                <p>A hero slot for real miner footage, equipment visuals, or a short brand intro video.</p>
                            </div>
                        </div>
                    </div>
                    <div class="hero-video-slide" style="background-image: linear-gradient(135deg, rgba(71,83,216,0.35), rgba(6,12,23,0.18)), url('{{ asset('build/images/others/logo-placeholder.png') }}');">
                        <div class="hero-video-overlay">
                            <div class="hero-video-meta">
                                <span class="hero-video-pill">ZagChain product</span>
                                <span class="hero-video-pill">Dashboard walkthrough</span>
                            </div>
                            <div style="display:flex; justify-content:center; align-items:center; flex:1;">
                                <div class="hero-play-button">
                                    <i data-lucide="monitor-play"></i>
                                </div>
                            </div>
                            <div class="hero-video-title">
                                <h3>Shareholder dashboard preview</h3>
                                <p>Use this state for screenshots, UI walkthrough clips, and platform feature highlights.</p>
                            </div>
                        </div>
                    </div>
                    <div class="hero-video-slide" style="background-image: linear-gradient(135deg, rgba(5,163,74,0.28), rgba(6,12,23,0.18)), url('{{ asset('build/images/others/placeholder.jpg') }}');">
                        <div class="hero-video-overlay">
                            <div class="hero-video-meta">
                                <span class="hero-video-pill">ZagChain network</span>
                                <span class="hero-video-pill">Referral growth</span>
                            </div>
                            <div style="display:flex; justify-content:center; align-items:center; flex:1;">
                                <div class="hero-play-button">
                                    <i data-lucide="network"></i>
                                </div>
                            </div>
                            <div class="hero-video-title">
                                <h3>Referral and reward animation</h3>
                                <p>Use this state for animated explainers showing upgrades, network rewards, and team growth.</p>
                            </div>
                        </div>
                    </div>
                    <div class="hero-slider-dots" aria-label="Hero media slider controls">
                        <button type="button" class="hero-slider-dot active" data-slide-index="0" aria-label="Show infrastructure preview"></button>
                        <button type="button" class="hero-slider-dot" data-slide-index="1" aria-label="Show dashboard preview"></button>
                        <button type="button" class="hero-slider-dot" data-slide-index="2" aria-label="Show referral preview"></button>
                    </div>
                </div>
                <div class="hero-media-grid">
                    <div class="hero-media-thumb">
                        <img src="{{ asset('build/images/others/placeholder.jpg') }}" alt="Miner preview image">
                        <div class="hero-thumb-caption">
                            <strong>Infrastructure preview</strong>
                            <span>Use this block for mining facility or equipment visuals.</span>
                        </div>
                    </div>
                    <div class="hero-media-thumb">
                        <img src="{{ asset('build/images/others/logo-placeholder.png') }}" alt="Dashboard preview image">
                        <div class="hero-thumb-caption">
                            <strong>Product walkthrough</strong>
                            <span>Use this block for dashboard screenshots and subscription flow visuals.</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="hero-miner">
                <div class="section-kicker" style="color:#a5b4fc; margin-bottom:8px;">Featured miner</div>
                <h3 style="font-size:2rem; margin-bottom:12px;">{{ $featuredMiner->name }}</h3>
                <p style="margin:0; color:rgba(255,255,255,0.78); line-height:1.75;">This public experience frames the miner as the heart of the business: visible production, visible capacity, share price visibility, and a clear path from visitor to investor.</p>
                <div class="chip-row">
                    <span class="chip">Share price ${{ number_format((float) $featuredMiner->share_price, 2) }}</span>
                    <span class="chip">Daily output ${{ number_format((float) $featuredMiner->daily_output_usd, 2) }}</span>
                    <span class="chip">Monthly output ${{ number_format((float) $featuredMiner->monthly_output_usd, 2) }}</span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="shell">
        <div class="section-head">
            <div>
                <div class="section-kicker">Business story</div>
                <h2>The product is not just a package list. It is a full operating model.</h2>
            </div>
            <p class="section-copy">The landing page now explains the business in four connected layers: miner performance, share ownership, subscription packages, and long-term network growth. That helps visitors understand both the product and the business behind it.</p>
        </div>
        <div class="section-grid">
            <div class="panel">
                <h3>Miner-backed subscriptions</h3>
                <p class="section-copy">Each package is explained as owned shares inside {{ $featuredMiner->name }}, with visible share price, capacity, and a return structure built from the live miner base rate.</p>
            </div>
            <div class="panel">
                <h3>Referral and team growth</h3>
                <p class="section-copy">Visitors learn that the platform is designed for more than passive purchase: referral growth unlocks upgrades, cash rewards, and stronger investment performance over time.</p>
            </div>
            <div class="panel">
                <h3>Operations-first trust</h3>
                <p class="section-copy">Payments are submitted, proof is uploaded after transfer, and the operations team reviews every order before activation. That makes the public message feel safer and more credible.</p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="shell">
        <div class="section-head">
            <div>
                <div class="section-kicker">Visual highlights</div>
                <h2>Give the visitor more to look at than text alone.</h2>
            </div>
            <p class="section-copy">This band adds visual momentum after the hero: a mini gallery for brand storytelling and animated-style metric cards that make the mining model feel active and measurable.</p>
        </div>
        <div class="visual-band">
            <div class="visual-gallery">
                <div class="visual-gallery-main">
                    <img src="{{ asset('build/images/others/placeholder.jpg') }}" alt="ZagChain miner visual">
                    <div class="visual-gallery-main-copy">
                        <strong>Miner story gallery</strong>
                        <span>Use this larger panel for flagship miner photography, operations footage stills, or a key brand visual.</span>
                    </div>
                </div>
                <div class="visual-gallery-stack">
                    <div>
                        <img src="{{ asset('build/images/others/logo-placeholder.png') }}" alt="ZagChain dashboard visual">
                        <div class="visual-gallery-stack-copy">
                            <strong>Dashboard trust</strong>
                            <span>Show the platform experience and investor interface.</span>
                        </div>
                    </div>
                    <div>
                        <img src="{{ asset('build/images/others/placeholder.jpg') }}" alt="ZagChain team growth visual">
                        <div class="visual-gallery-stack-copy">
                            <strong>Network growth</strong>
                            <span>Support the referral story with future team or animation visuals.</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="visual-stats">
                <article class="visual-stat-card">
                    <span class="section-kicker">Live structure</span>
                    <h3>{{ number_format($sharesSold) }}</h3>
                    <p>Shares already framed inside the public miner story, helping visitors understand that packages are tied to real miner capacity.</p>
                    <div class="visual-chip-row">
                        <span class="visual-chip">Sold shares</span>
                        <span class="visual-chip">Miner-backed</span>
                    </div>
                </article>
                <article class="visual-stat-card">
                    <span class="section-kicker">Open capacity</span>
                    <h3>{{ number_format($availableShares) }}</h3>
                    <p>Remaining share capacity can be used as a conversion message: there is room to enter, but the story still feels limited and concrete.</p>
                    <div class="visual-chip-row">
                        <span class="visual-chip">Available now</span>
                        <span class="visual-chip">Investor entry</span>
                    </div>
                </article>
                <article class="visual-stat-card">
                    <span class="section-kicker">Current base</span>
                    <h3>{{ number_format($featuredMiner->base_monthly_return_rate * 100, 2) }}%</h3>
                    <p>The live miner base rate gives the website a measurable anchor before the visitor even reaches the detailed package or dashboard pages.</p>
                    <div class="visual-chip-row">
                        <span class="visual-chip">Return anchor</span>
                        <span class="visual-chip">Package uplift ready</span>
                    </div>
                </article>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="shell page-grid">
        <div class="panel page-copy">
            <div class="section-kicker">How it works</div>
            <h2 style="font-size:clamp(2rem, 3vw, 3.2rem); margin-bottom:14px;">A simple path from visitor to shareholder.</h2>
            <p>The public website should make the journey feel easy: join with Free Starter, understand the miner, choose a package, send payment, upload proof, and enter the shareholder dashboard after approval.</p>
            <div class="timeline">
                <div class="timeline-step">Register and start with Free Starter</div>
                <div class="timeline-step">Review packages built around miner shares</div>
                <div class="timeline-step">Send payment through the selected method</div>
                <div class="timeline-step">Upload proof and wait for operations review</div>
                <div class="timeline-step">Track investment, network, and rewards inside the dashboard</div>
            </div>
        </div>
        <div class="panel page-copy">
            <div class="section-kicker">Why this business can scale</div>
            <h3 style="font-size:1.6rem; margin-bottom:12px;">Three engines working together</h3>
            <div class="metric-list">
                <div class="metric-row"><span>Miner engine</span><strong>Performance visibility</strong></div>
                <div class="metric-row"><span>Subscription engine</span><strong>Packages tied to shares</strong></div>
                <div class="metric-row"><span>Referral engine</span><strong>Growth through network rewards</strong></div>
                <div class="metric-row"><span>Operations engine</span><strong>Proof and review before activation</strong></div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="shell">
        <div class="section-head">
            <div>
                <div class="section-kicker">Subscriptions</div>
                <h2>Packages connected directly to the miner.</h2>
            </div>
            <a href="{{ route('marketing.packages') }}" class="btn btn-soft">Full package page</a>
        </div>
        <div class="package-grid">
            @foreach ($packages->take(3) as $package)
                <div class="package-card {{ $loop->index === 1 ? 'featured' : '' }}">
                    <span class="tag">{{ $package->shares_count }} shares</span>
                    <h3 style="margin-top:16px;">{{ $package->name }}</h3>
                    <div class="price">${{ number_format((float) $package->price, 0) }}</div>
                    <p class="section-copy">Built on the live miner base rate with a package uplift of {{ number_format((($package->monthly_return_rate - $featuredMiner->base_monthly_return_rate) * 100), 2) }}%.</p>
                    <div class="metric-list">
                        <div class="metric-row"><span>Monthly return</span><strong>{{ number_format($package->monthly_return_rate * 100, 2) }}%</strong></div>
                        <div class="metric-row"><span>Equivalent units</span><strong>{{ $package->units_limit }}</strong></div>
                        <div class="metric-row"><span>Miner</span><strong>{{ $featuredMiner->name }}</strong></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="section">
    <div class="shell">
        <div class="section-head">
            <div>
                <div class="section-kicker">Media</div>
                <h2>Pictures, videos, and animated explainers.</h2>
            </div>
            <a href="{{ route('marketing.media') }}" class="btn btn-soft">Open media page</a>
        </div>
        <div class="media-grid">
            @foreach ($mediaGallery as $item)
                <article class="media-card">
                    <div class="media-frame">
                        @if ($item['type'] === 'image')
                            <img src="{{ $item['src'] }}" alt="{{ $item['title'] }}">
                        @elseif ($item['type'] === 'video')
                            <div class="play-button"><i data-lucide="play"></i></div>
                        @else
                            <div class="motion-layers">
                                <div class="motion-bar"></div>
                                <div class="motion-bar"></div>
                                <div class="motion-bar"></div>
                            </div>
                        @endif
                    </div>
                    <h3>{{ $item['title'] }}</h3>
                    <p class="section-copy">{{ $item['description'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="section">
    <div class="shell">
        <div class="section-head">
            <div>
                <div class="section-kicker">References</div>
                <h2>Public trust points and proof anchors.</h2>
            </div>
            <a href="{{ route('marketing.references') }}" class="btn btn-soft">Open references</a>
        </div>
        <div class="reference-grid">
            @foreach ($references as $reference)
                <article class="reference-card">
                    <div class="quote">"{{ $reference['quote'] }}"</div>
                    <div class="reference-meta"><strong>{{ $reference['name'] }}</strong><br>{{ $reference['role'] }}</div>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="section">
    <div class="shell">
        <div class="section-head">
            <div>
                <div class="section-kicker">FAQ</div>
                <h2>Questions the homepage should answer fast.</h2>
            </div>
        </div>
        <div class="faq-grid">
            @foreach ($faqItems as $faq)
                <article class="faq-card">
                    <h3>{{ $faq['question'] }}</h3>
                    <p class="section-copy" style="margin-top:12px;">{{ $faq['answer'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="shell">
    <div class="cta">
        <div class="cta-grid">
            <div>
                <div class="section-kicker" style="color:#c7d2fe; margin-bottom:10px;">Launch path</div>
                <h2 style="margin-bottom:12px;">Give every visitor one clear next step.</h2>
                <p style="margin:0; color:rgba(255,255,255,0.82); line-height:1.8;">Start them with Free Starter, show the miner clearly, explain the package ladder, and then move them into the monitored subscription flow with trust-building media and references.</p>
            </div>
            <div style="display:flex; gap:12px; flex-wrap:wrap; justify-content:flex-end;">
                <a href="{{ route('register') }}" class="btn btn-primary">Create account</a>
                <a href="{{ route('marketing.about') }}" class="btn btn-soft">Learn more</a>
            </div>
        </div>
    </div>
</section>
@endsection


@push('marketing-scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const slider = document.querySelector('#marketingHeroSlider');
        if (! slider) {
            return;
        }

        const slides = Array.from(slider.querySelectorAll('.hero-video-slide'));
        const dots = Array.from(slider.querySelectorAll('.hero-slider-dot'));
        let activeIndex = 0;
        let intervalId = null;

        const setActiveSlide = (index) => {
            activeIndex = index;
            slides.forEach((slide, slideIndex) => {
                slide.classList.toggle('active', slideIndex === index);
            });
            dots.forEach((dot, dotIndex) => {
                dot.classList.toggle('active', dotIndex === index);
            });
        };

        const startRotation = () => {
            if (intervalId) {
                clearInterval(intervalId);
            }
            intervalId = setInterval(() => {
                const nextIndex = (activeIndex + 1) % slides.length;
                setActiveSlide(nextIndex);
            }, 4500);
        };

        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                setActiveSlide(index);
                startRotation();
            });
        });

        setActiveSlide(0);
        startRotation();
    });
</script>
@endpush