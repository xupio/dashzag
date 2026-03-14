@extends('marketing.layout')

@php($pageTitle = 'Media')

@section('content')
<section class="section">
    <div class="shell">
        <div class="section-head">
            <div>
                <div class="section-kicker">Media library</div>
                <h1 style="font-size:clamp(2.2rem, 4vw, 4rem);">Pictures, videos, and animated explainers</h1>
            </div>
            <p class="section-copy">This page is the public media destination for miner visuals, onboarding videos, and animated explainers that help the visitor understand the subscription model before they register.</p>
        </div>

        <div class="media-showcase">
            <article class="media-feature">
                <img src="{{ asset('build/images/others/placeholder.jpg') }}" alt="ZagChain featured media visual">
                <div class="play-button"><i data-lucide="play"></i></div>
                <div class="media-feature-copy">
                    <div class="section-kicker" style="color:#c7d2fe; margin-bottom:8px;">Featured video slot</div>
                    <h3 style="font-size:2rem; margin-bottom:10px;">ZagChain miner and platform overview</h3>
                    <p>Use this large area for your hero explainer video, mining facility footage, or a combined business presentation for new visitors.</p>
                </div>
            </article>
            <div class="media-stack">
                <article class="media-mini">
                    <img src="{{ asset('build/images/others/logo-placeholder.png') }}" alt="ZagChain dashboard media visual">
                    <div class="media-mini-copy">
                        <strong>Dashboard walkthrough</strong>
                        <span>Show the investor experience, portfolio screens, and network tools.</span>
                    </div>
                </article>
                <article class="media-mini">
                    <img src="{{ asset('build/images/others/placeholder.jpg') }}" alt="ZagChain network media visual">
                    <div class="media-mini-copy">
                        <strong>Referral animation</strong>
                        <span>Use this block for animated growth explainers, reward flows, and upgrade logic.</span>
                    </div>
                </article>
            </div>
        </div>

        <div class="media-library-grid">
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
    <div class="shell page-grid">
        <div class="panel page-copy">
            <div class="section-kicker">Recommended media blocks</div>
            <div class="timeline">
                <div class="timeline-step">Miner overview video introducing the business in under 90 seconds</div>
                <div class="timeline-step">Subscription walkthrough showing how shares and returns are explained</div>
                <div class="timeline-step">Referral animation showing upgrades, rewards, and team growth</div>
                <div class="timeline-step">Operations video or screenshots showing proof upload and review</div>
            </div>
        </div>
        <div class="panel page-copy">
            <div class="section-kicker">What to add next</div>
            <p>This structure is ready for real assets. You can replace the placeholders with your actual miner photos, embedded YouTube or Vimeo links, short animation clips, and screenshots from the live dashboard.</p>
            <div class="metric-list">
                <div class="metric-row"><span>Miner photos</span><strong>Infrastructure trust</strong></div>
                <div class="metric-row"><span>Explainer video</span><strong>Fast onboarding</strong></div>
                <div class="metric-row"><span>Animation</span><strong>Product education</strong></div>
                <div class="metric-row"><span>Dashboard screenshots</span><strong>Platform credibility</strong></div>
            </div>
        </div>
    </div>
</section>
@endsection