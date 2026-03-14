@extends('marketing.layout')

@php($pageTitle = 'References')

@section('content')
<section class="section">
    <div class="shell">
        <div class="section-head">
            <div>
                <div class="section-kicker">References</div>
                <h1 style="font-size:clamp(2.2rem, 4vw, 4rem);">Trust anchors for the public website</h1>
            </div>
            <p class="section-copy">This page is where the business can present social proof, reference statements, supporting quotes, and future partner logos or testimonials in a cleaner way than the dashboard.</p>
        </div>

        <div class="trust-showcase">
            <article class="trust-feature">
                <div class="section-kicker" style="color:#c7d2fe; margin-bottom:10px;">Featured trust story</div>
                <h2 style="font-size:clamp(2rem, 3vw, 3rem); margin-bottom:12px;">ZagChain turns miner performance into a story investors can actually understand.</h2>
                <p>The public trust layer should feel bigger than a few quotes. It should show that the business has structure, operating discipline, and a clear message about how subscriptions, shares, referrals, and payment review all connect.</p>
                <div class="visual-chip-row">
                    <span class="visual-chip" style="background:rgba(255,255,255,0.12); color:#fff;">Operations-first</span>
                    <span class="visual-chip" style="background:rgba(255,255,255,0.12); color:#fff;">Miner-backed</span>
                    <span class="visual-chip" style="background:rgba(255,255,255,0.12); color:#fff;">Network-ready</span>
                </div>
            </article>
            <div class="trust-stack">
                <article class="trust-stack-card">
                    <div class="section-kicker">Positioning</div>
                    <div class="trust-metric">3 pillars</div>
                    <p>Miner visibility, subscription clarity, and referral growth are the main trust pillars the visitor should feel on this page.</p>
                </article>
                <article class="trust-stack-card">
                    <div class="section-kicker">Future social proof</div>
                    <div class="trust-metric">4 content types</div>
                    <p>Testimonials, partner logos, case studies, and investor story videos can all live here cleanly as ZagChain grows.</p>
                </article>
            </div>
        </div>

        <div class="reference-grid">
            @foreach ($references as $reference)
                <article class="reference-card">
                    <div class="quote">"{{ $reference['quote'] }}"</div>
                    <div class="reference-meta"><strong>{{ $reference['name'] }}</strong><br>{{ $reference['role'] }}</div>
                </article>
            @endforeach
        </div>

        <div class="section" style="padding-bottom:0;">
            <div class="panel">
                <div class="section-head" style="margin-bottom:18px;">
                    <div>
                        <div class="section-kicker">Logo-ready strip</div>
                        <h3>Partner and proof areas</h3>
                    </div>
                </div>
                <div class="logo-strip">
                    <div class="logo-pill">Partner Logo</div>
                    <div class="logo-pill">Investor Group</div>
                    <div class="logo-pill">Operations Team</div>
                    <div class="logo-pill">Media Mention</div>
                </div>
            </div>
        </div>

        <div class="section" style="padding-bottom:0;">
            <div class="panel">
                <h3>What can be added next</h3>
                <div class="timeline">
                    <div class="timeline-step">Partner and company logos</div>
                    <div class="timeline-step">Investor story videos</div>
                    <div class="timeline-step">Operational photography of the mining infrastructure</div>
                    <div class="timeline-step">Case studies for referrals and shareholder growth</div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection