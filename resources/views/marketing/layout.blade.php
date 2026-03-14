<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ($pageTitle ?? 'Cloud Mining Platform') . ' | ' . config('app.name', 'ZagChain') }}</title>
    <meta name="description" content="Cloud mining subscriptions, miner performance visibility, and referral-powered growth.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="shortcut icon" href="{{ asset('/favicon.ico') }}">
    <style>
        :root {
            --bg: #f4f7fb;
            --bg-soft: #ffffff;
            --ink: #060c17;
            --muted: #7987a1;
            --line: rgba(121, 135, 161, 0.22);
            --brand: #6571ff;
            --brand-deep: #4753d8;
            --brand-soft: rgba(101, 113, 255, 0.12);
            --accent: #060c17;
            --success: #05a34a;
            --shadow: 0 .5rem 1rem rgba(183, 192, 206, .2);
            --radius: 26px;
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            font-family: "Roboto", Helvetica, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, rgba(101, 113, 255, 0.14), transparent 28%),
                radial-gradient(circle at top right, rgba(6, 12, 23, 0.08), transparent 24%),
                linear-gradient(180deg, #f7f9fc 0%, #f4f7fb 44%, #eef2f7 100%);
        }
        a { color: inherit; text-decoration: none; }
        img { max-width: 100%; display: block; }
        .shell { width: min(1180px, calc(100% - 32px)); margin: 0 auto; }
        .marketing-header {
            position: sticky; top: 0; z-index: 40;
            backdrop-filter: blur(16px);
            background: rgba(255, 255, 255, 0.92);
            border-bottom: 1px solid rgba(121, 135, 161, 0.16);
        }
        .header-row {
            display: flex; align-items: center; justify-content: space-between;
            gap: 18px; min-height: 78px;
        }
        .brand-mark {
            display: inline-flex; align-items: center; gap: 12px; font-weight: 800; letter-spacing: 0.02em;
        }
        .brand-badge {
            width: 44px; height: 44px; border-radius: 14px;
            display: grid; place-items: center; color: white;
            background: linear-gradient(135deg, var(--brand), var(--brand-deep));
            box-shadow: 0 16px 30px rgba(101, 113, 255, 0.22);
        }
        .header-nav { display: flex; align-items: center; gap: 18px; flex-wrap: wrap; }
        .nav-link { color: var(--muted); font-size: 0.95rem; }
        .nav-link:hover, .nav-link.active { color: var(--ink); }
        .header-actions { display: flex; gap: 12px; flex-wrap: wrap; justify-content: flex-end; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            padding: 12px 18px; border-radius: 999px; font-weight: 700; border: 1px solid transparent;
            transition: 0.2s ease; cursor: pointer;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary { background: linear-gradient(135deg, var(--brand), var(--brand-deep)); color: white; box-shadow: 0 16px 28px rgba(101, 113, 255, 0.22); }
        .btn-soft { background: rgba(255,255,255,0.72); border-color: var(--line); color: var(--ink); }
        .hero { padding: 42px 0 26px; }
        .hero-grid { display: grid; grid-template-columns: 1.15fr 0.85fr; gap: 28px; align-items: stretch; }
        .hero-card, .panel, .media-card, .reference-card, .faq-card {
            background: rgba(255,255,255,0.72);
            border: 1px solid rgba(255,255,255,0.68);
            box-shadow: var(--shadow);
            border-radius: var(--radius);
        }
        .hero-card { padding: 40px; position: relative; overflow: hidden; }
        .hero-card::after {
            content: ""; position: absolute; inset: auto -60px -80px auto; width: 230px; height: 230px;
            border-radius: 50%; background: radial-gradient(circle, rgba(101,113,255,0.18), transparent 70%);
        }
        .eyebrow {
            display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 999px;
            background: var(--brand-soft); color: var(--brand-deep); font-size: 0.84rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.08em;
        }
        h1, h2, h3 { margin: 0; line-height: 1.02; }
        h1 { font-size: clamp(2.6rem, 5vw, 5.2rem); margin-top: 16px; }
        .lead { margin: 18px 0 0; font-size: 1.08rem; line-height: 1.7; color: var(--muted); max-width: 60ch; }
        .hero-actions, .stat-grid, .section-grid, .media-grid, .reference-grid, .faq-grid, .page-grid {
            display: grid; gap: 18px;
        }
        .hero-actions { grid-template-columns: repeat(2, max-content); margin-top: 28px; }
        .stat-grid { grid-template-columns: repeat(3, 1fr); margin-top: 28px; }
        .stat-card {
            padding: 18px; border-radius: 20px; background: rgba(255,255,255,0.72); border: 1px solid var(--line);
        }
        .stat-label { font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted); }
        .stat-value { font-size: 1.75rem; font-weight: 800; margin-top: 8px; }
        .hero-side { padding: 24px; display: flex; flex-direction: column; gap: 18px; }
        .hero-media-stage {
            position: relative;
            min-height: 520px;
            padding: 22px;
            border-radius: 24px;
            overflow: hidden;
            background: linear-gradient(160deg, rgba(6,12,23,0.98), rgba(33,42,58,0.96));
            color: white;
        }
        .hero-media-stage::before {
            content: "";
            position: absolute;
            inset: 14px;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.08);
            pointer-events: none;
        }
        .hero-video-card {
            position: relative;
            min-height: 280px;
            border-radius: 22px;
            overflow: hidden;
            background: linear-gradient(135deg, rgba(101,113,255,0.28), rgba(6,12,23,0.12)), url("{{ asset('build/images/others/placeholder.jpg') }}") center/cover;
            box-shadow: 0 24px 48px rgba(0,0,0,0.24);
        }
        .hero-video-slide {
            position: absolute;
            inset: 0;
            opacity: 0;
            transform: scale(1.02);
            transition: opacity 0.6s ease, transform 0.6s ease;
            background-size: cover;
            background-position: center;
        }
        .hero-video-slide.active {
            opacity: 1;
            transform: scale(1);
        }
        .hero-video-slide::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(6,12,23,0.15), rgba(6,12,23,0.76));
        }
        .hero-video-overlay {
            position: absolute;
            inset: 0;
            z-index: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 22px;
        }
        .hero-video-meta {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
        }
        .hero-video-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.14);
            font-size: 0.82rem;
            font-weight: 700;
        }
        .hero-video-title {
            max-width: 280px;
        }
        .hero-video-title h3 {
            font-size: 1.7rem;
            margin-bottom: 8px;
        }
        .hero-video-title p {
            margin: 0;
            color: rgba(255,255,255,0.74);
            line-height: 1.65;
        }
        .hero-play-button {
            width: 84px;
            height: 84px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: rgba(255,255,255,0.16);
            border: 1px solid rgba(255,255,255,0.28);
            backdrop-filter: blur(6px);
            box-shadow: 0 18px 34px rgba(0,0,0,0.22);
        }
        .hero-slider-dots {
            position: absolute;
            right: 18px;
            bottom: 18px;
            z-index: 3;
            display: flex;
            gap: 8px;
        }
        .hero-slider-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,0.5);
            background: rgba(255,255,255,0.22);
            cursor: pointer;
            transition: transform 0.2s ease, background 0.2s ease;
        }
        .hero-slider-dot.active {
            background: #ffffff;
            transform: scale(1.08);
        }
        .hero-media-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-top: 16px;
        }
        .hero-media-thumb {
            position: relative;
            min-height: 150px;
            border-radius: 18px;
            overflow: hidden;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 16px 28px rgba(0,0,0,0.18);
        }
        .hero-media-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: saturate(1.02);
        }
        .hero-media-thumb::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(6,12,23,0.05), rgba(6,12,23,0.62));
        }
        .hero-thumb-caption {
            position: absolute;
            left: 14px;
            right: 14px;
            bottom: 14px;
            z-index: 1;
        }
        .hero-thumb-caption strong {
            display: block;
            font-size: 0.96rem;
            margin-bottom: 4px;
        }
        .hero-thumb-caption span {
            display: block;
            color: rgba(255,255,255,0.72);
            font-size: 0.82rem;
            line-height: 1.45;
        }
        .hero-miner {
            padding: 22px; border-radius: 22px;
            background: linear-gradient(160deg, rgba(6,12,23,0.98), rgba(33,42,58,0.96));
            color: white;
        }
        .chip-row { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 16px; }
        .chip {
            padding: 8px 12px; border-radius: 999px; background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.92);
            font-size: 0.9rem;
        }
        .section { padding: 32px 0; }
        .section-head { display: flex; align-items: end; justify-content: space-between; gap: 18px; margin-bottom: 22px; }
        .section-kicker { color: var(--brand-deep); text-transform: uppercase; letter-spacing: 0.08em; font-size: 0.84rem; font-weight: 700; margin-bottom: 10px; }
        .section-copy { color: var(--muted); max-width: 62ch; line-height: 1.7; }
        .section-grid { grid-template-columns: repeat(3, 1fr); }
        .panel { padding: 24px; }
        .panel h3 { font-size: 1.25rem; margin-bottom: 12px; }
        .metric-list, .timeline { display: grid; gap: 12px; margin-top: 18px; }
        .metric-row, .timeline-step {
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
            padding: 12px 14px; border-radius: 16px; background: rgba(255,255,255,0.62); border: 1px solid var(--line);
        }
        .timeline-step::before {
            content: ""; width: 12px; height: 12px; border-radius: 50%; background: linear-gradient(135deg, var(--brand), var(--brand-deep));
            box-shadow: 0 0 0 6px rgba(101,113,255,0.12);
        }
        .package-grid, .media-grid, .reference-grid, .faq-grid { grid-template-columns: repeat(3, 1fr); }
        .package-card {
            padding: 28px; border-radius: 24px; background: rgba(255,255,255,0.75); border: 1px solid var(--line); box-shadow: var(--shadow);
            position: relative; overflow: hidden;
        }
        .package-card.featured { border-color: rgba(101,113,255,0.35); transform: translateY(-4px); }
        .package-card .price { font-size: 2.8rem; font-weight: 800; margin: 14px 0; }
        .tag {
            display: inline-flex; padding: 6px 10px; border-radius: 999px; background: rgba(101,113,255,0.12); color: var(--accent); font-size: 0.82rem; font-weight: 700;
        }
        .media-card, .reference-card, .faq-card { padding: 24px; }
        .media-frame {
            aspect-ratio: 16 / 10; border-radius: 18px; overflow: hidden; margin-bottom: 18px;
            background: linear-gradient(140deg, rgba(6,12,23,0.98), rgba(101,113,255,0.92));
            position: relative;
        }
        .media-frame img { width: 100%; height: 100%; object-fit: cover; }
        .play-button {
            position: absolute; inset: 50% auto auto 50%; transform: translate(-50%, -50%);
            width: 76px; height: 76px; border-radius: 50%; background: rgba(255,255,255,0.18); border: 1px solid rgba(255,255,255,0.4);
            display: grid; place-items: center; color: white; font-size: 1.4rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.16);
        }
        .motion-layers { position: absolute; inset: 18px; display: grid; gap: 12px; }
        .motion-bar {
            border-radius: 16px; background: rgba(255,255,255,0.18); border: 1px solid rgba(255,255,255,0.22);
            animation: pulse 2.8s ease-in-out infinite;
        }
        .motion-bar:nth-child(2) { animation-delay: 0.25s; }
        .motion-bar:nth-child(3) { animation-delay: 0.5s; }
        @keyframes pulse {
            0%, 100% { transform: translateX(0); opacity: 0.7; }
            50% { transform: translateX(10px); opacity: 1; }
        }
        .visual-band {
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            gap: 18px;
            align-items: stretch;
        }
        .visual-gallery {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 16px;
        }
        .visual-gallery-main,
        .visual-gallery-stack > div,
        .visual-stat-card {
            border-radius: 22px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.72);
            box-shadow: var(--shadow);
            background: rgba(255,255,255,0.74);
        }
        .visual-gallery-main {
            min-height: 340px;
            position: relative;
            background: linear-gradient(145deg, rgba(6,12,23,0.96), rgba(101,113,255,0.84));
        }
        .visual-gallery-main img,
        .visual-gallery-stack img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .visual-gallery-main::after,
        .visual-gallery-stack > div::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(6,12,23,0.08), rgba(6,12,23,0.68));
        }
        .visual-gallery-main-copy,
        .visual-gallery-stack-copy {
            position: absolute;
            left: 18px;
            right: 18px;
            bottom: 18px;
            z-index: 1;
            color: #fff;
        }
        .visual-gallery-main-copy strong,
        .visual-gallery-stack-copy strong {
            display: block;
            font-size: 1.1rem;
            margin-bottom: 6px;
        }
        .visual-gallery-main-copy span,
        .visual-gallery-stack-copy span {
            color: rgba(255,255,255,0.76);
            line-height: 1.5;
            font-size: 0.9rem;
        }
        .visual-gallery-stack {
            display: grid;
            gap: 16px;
        }
        .visual-gallery-stack > div {
            min-height: 162px;
            position: relative;
        }
        .visual-stats {
            display: grid;
            gap: 16px;
        }
        .visual-stat-card {
            padding: 22px;
            position: relative;
        }
        .visual-stat-card::before {
            content: "";
            position: absolute;
            inset: auto 18px 18px 18px;
            height: 6px;
            border-radius: 999px;
            background: linear-gradient(90deg, rgba(101,113,255,0.18), rgba(101,113,255,0.72));
        }
        .visual-stat-card h3 {
            font-size: 2.4rem;
            margin-bottom: 6px;
        }
        .visual-stat-card p {
            margin: 0;
            color: var(--muted);
            line-height: 1.7;
        }
        .visual-chip-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 14px;
        }
        .visual-chip {
            display: inline-flex;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(101,113,255,0.1);
            color: var(--brand-deep);
            font-size: 0.8rem;
            font-weight: 700;
        }
        .media-showcase {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 18px;
            margin-bottom: 18px;
        }
        .media-feature {
            position: relative;
            min-height: 420px;
            border-radius: 26px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.78);
            box-shadow: var(--shadow);
            background: linear-gradient(140deg, rgba(6,12,23,0.98), rgba(101,113,255,0.92));
        }
        .media-feature img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .media-feature::after,
        .media-mini::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(6,12,23,0.08), rgba(6,12,23,0.76));
        }
        .media-feature-copy,
        .media-mini-copy {
            position: absolute;
            left: 22px;
            right: 22px;
            bottom: 22px;
            z-index: 1;
            color: #fff;
        }
        .media-feature-copy h3,
        .media-mini-copy strong {
            margin-bottom: 8px;
        }
        .media-feature-copy p,
        .media-mini-copy span {
            color: rgba(255,255,255,0.78);
            line-height: 1.65;
        }
        .media-stack {
            display: grid;
            gap: 18px;
        }
        .media-mini {
            position: relative;
            min-height: 200px;
            border-radius: 24px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.78);
            box-shadow: var(--shadow);
            background: linear-gradient(140deg, rgba(6,12,23,0.98), rgba(101,113,255,0.92));
        }
        .media-mini img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .media-library-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }
        .trust-showcase {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 18px;
            margin-bottom: 18px;
        }
        .trust-feature,
        .trust-stack-card,
        .logo-pill {
            border-radius: 24px;
            border: 1px solid rgba(255,255,255,0.78);
            box-shadow: var(--shadow);
            background: rgba(255,255,255,0.74);
        }
        .trust-feature {
            padding: 30px;
            background: linear-gradient(145deg, rgba(6,12,23,0.98), rgba(71,83,216,0.94));
            color: #fff;
        }
        .trust-feature p {
            color: rgba(255,255,255,0.8);
            line-height: 1.8;
        }
        .trust-stack {
            display: grid;
            gap: 18px;
        }
        .trust-stack-card {
            padding: 22px;
        }
        .trust-metric {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 8px;
            color: var(--ink);
        }
        .logo-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-top: 18px;
        }
        .logo-pill {
            min-height: 96px;
            display: grid;
            place-items: center;
            font-size: 0.95rem;
            font-weight: 800;
            color: var(--muted);
            background: linear-gradient(180deg, rgba(255,255,255,0.9), rgba(244,247,251,0.98));
        }
        .quote { font-size: 1.02rem; line-height: 1.8; color: var(--ink); }
        .reference-meta { margin-top: 18px; color: var(--muted); font-size: 0.92rem; }
        .cta {
            margin: 30px 0 56px; padding: 30px; border-radius: 30px; color: white;
            background: linear-gradient(135deg, rgba(6,12,23,0.98), rgba(71,83,216,0.95));
            box-shadow: 0 30px 70px rgba(18, 28, 45, 0.24);
        }
        .cta-grid { display: grid; grid-template-columns: 1fr auto; gap: 18px; align-items: center; }
        .marketing-footer { padding: 24px 0 42px; color: var(--muted); }
        .footer-row { display: flex; justify-content: space-between; gap: 18px; flex-wrap: wrap; }
        .page-grid { grid-template-columns: 0.9fr 1.1fr; }
        .page-copy { padding: 28px; }
        .page-copy p { color: var(--muted); line-height: 1.75; }
        @media (max-width: 1080px) {
            .hero-grid, .page-grid, .section-grid, .package-grid, .media-grid, .reference-grid, .faq-grid, .cta-grid, .hero-media-grid, .visual-band, .visual-gallery, .media-showcase, .media-library-grid, .trust-showcase, .logo-strip { grid-template-columns: 1fr; }
            .stat-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 760px) {
            .header-row { padding: 14px 0; align-items: flex-start; }
            .header-nav { display: none; }
            .hero-card, .panel, .package-card, .media-card, .reference-card, .faq-card, .page-copy, .cta { padding: 22px; }
            .hero-actions { grid-template-columns: 1fr; }
            .header-actions, .hero-actions .btn { width: 100%; }
        }
    </style>
</head>
<body>
    <header class="marketing-header">
        <div class="shell header-row">
            <a href="{{ route('landing') }}" class="brand-mark">
                <span class="brand-badge">ZC</span>
                <span>
                    <span style="display:block; font-size:1rem;">{{ config('app.name', 'ZagChain') }}</span>
                    <span style="display:block; font-size:0.82rem; color:var(--muted); font-weight:600;">Miner-backed subscription platform</span>
                </span>
            </a>
            <nav class="header-nav">
                <a href="{{ route('landing') }}" class="nav-link {{ request()->routeIs('landing') ? 'active' : '' }}">Home</a>
                <a href="{{ route('marketing.about') }}" class="nav-link {{ request()->routeIs('marketing.about') ? 'active' : '' }}">About</a>
                <a href="{{ route('marketing.packages') }}" class="nav-link {{ request()->routeIs('marketing.packages') ? 'active' : '' }}">Packages</a>
                <a href="{{ route('marketing.media') }}" class="nav-link {{ request()->routeIs('marketing.media') ? 'active' : '' }}">Media</a>
                <a href="{{ route('marketing.references') }}" class="nav-link {{ request()->routeIs('marketing.references') ? 'active' : '' }}">References</a>
            </nav>
            <div class="header-actions">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-soft">Open dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-soft">Log in</a>
                    <a href="{{ route('register') }}" class="btn btn-primary">Start free</a>
                @endauth
            </div>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="marketing-footer">
        <div class="shell footer-row">
            <div>
                <strong>{{ config('app.name', 'ZagChain') }}</strong><br>
                Transparent miner performance, subscription packages, and referral-powered growth.
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a href="{{ route('marketing.about') }}">About</a>
                <span>•</span>
                <a href="{{ route('marketing.packages') }}">Packages</a>
                <span>•</span>
                <a href="{{ route('marketing.media') }}">Media</a>
                <span>•</span>
                <a href="{{ route('marketing.references') }}">References</a>
            </div>
        </div>
    </footer>

    <script src="{{ asset('build/plugins/lucide/lucide.min.js') }}"></script>
    <script>window.lucide && lucide.createIcons();</script>
    @stack('marketing-scripts')
</body>
</html>


