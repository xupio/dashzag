@extends('marketing.legacy-dark-layout', ['pageTitle' => 'ZagChain | Home'])

@push('style')
<style>
    .landing-shell {
        background:
            radial-gradient(circle at top right, rgba(245, 158, 11, 0.18), transparent 28%),
            linear-gradient(180deg, #071022 0%, #0b1430 52%, #ffffff 52%, #ffffff 100%);
    }

    .landing-hero {
        overflow: hidden;
        padding: 120px 0 90px;
        position: relative;
    }

    .landing-hero::before {
        background:
            radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.1), transparent 32%),
            radial-gradient(circle at 85% 15%, rgba(245, 158, 11, 0.22), transparent 24%);
        content: "";
        inset: 0;
        pointer-events: none;
        position: absolute;
    }

    .landing-hero-copy,
    .landing-hero-visual {
        position: relative;
        z-index: 1;
    }

    .landing-kicker {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 999px;
        color: #f8fafc;
        display: inline-flex;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.14em;
        margin-bottom: 24px;
        padding: 10px 18px;
        text-transform: uppercase;
    }

    .landing-hero h1 {
        color: #fff;
        font-size: 64px;
        font-weight: 800;
        letter-spacing: -0.03em;
        line-height: 1.02;
        margin-bottom: 18px;
        max-width: 620px;
    }

    .landing-hero h1 span {
        color: #f59e0b;
    }

    .landing-hero p {
        color: rgba(255, 255, 255, 0.76);
        font-size: 18px;
        line-height: 1.8;
        margin-bottom: 0;
        max-width: 560px;
    }

    .landing-hero-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        margin-top: 32px;
    }

    .landing-outline-btn {
        background: transparent !important;
        border: 1px solid rgba(255, 255, 255, 0.18) !important;
        color: #fff !important;
    }

    .landing-outline-btn:hover {
        background: rgba(255, 255, 255, 0.08) !important;
        border-color: rgba(255, 255, 255, 0.3) !important;
    }

    .landing-proof-row {
        display: grid;
        gap: 16px;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        margin-top: 34px;
        max-width: 620px;
    }

    .landing-proof-card {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 18px;
        padding: 18px 18px 16px;
    }

    .landing-proof-card strong {
        color: #fff;
        display: block;
        font-size: 30px;
        line-height: 1;
        margin-bottom: 8px;
    }

    .landing-proof-card span {
        color: rgba(255, 255, 255, 0.68);
        display: block;
        font-size: 13px;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .landing-visual-panel {
        background: linear-gradient(180deg, rgba(17, 24, 39, 0.95), rgba(10, 16, 34, 0.98));
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 28px;
        box-shadow: 0 28px 80px rgba(2, 6, 23, 0.45);
        margin-left: auto;
        max-width: 440px;
        overflow: hidden;
        padding: 26px;
    }

    .landing-visual-top {
        align-items: center;
        display: flex;
        justify-content: space-between;
        margin-bottom: 26px;
    }

    .landing-visual-top strong {
        color: #fff;
        display: block;
        font-size: 20px;
    }

    .landing-visual-top span,
    .landing-visual-line span,
    .landing-mini-card span,
    .landing-cta-panel p {
        color: rgba(255, 255, 255, 0.66);
    }

    .landing-status-pill {
        background: rgba(16, 185, 129, 0.14);
        border: 1px solid rgba(16, 185, 129, 0.22);
        border-radius: 999px;
        color: #6ee7b7;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.08em;
        padding: 8px 12px;
        text-transform: uppercase;
    }

    .landing-visual-chart {
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.02));
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 22px;
        margin-bottom: 18px;
        padding: 22px;
    }

    .landing-visual-line {
        align-items: end;
        display: flex;
        gap: 10px;
        height: 165px;
        margin: 18px 0 8px;
    }

    .landing-visual-line i {
        background: linear-gradient(180deg, #facc15, #f59e0b);
        border-radius: 999px 999px 10px 10px;
        display: block;
        flex: 1;
    }

    .landing-visual-line i:nth-child(1) { height: 24%; opacity: 0.5; }
    .landing-visual-line i:nth-child(2) { height: 36%; opacity: 0.62; }
    .landing-visual-line i:nth-child(3) { height: 52%; opacity: 0.76; }
    .landing-visual-line i:nth-child(4) { height: 68%; opacity: 0.88; }
    .landing-visual-line i:nth-child(5) { height: 86%; }

    .landing-visual-grid {
        display: grid;
        gap: 14px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .landing-mini-card {
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 18px;
        padding: 18px;
    }

    .landing-mini-card strong {
        color: #fff;
        display: block;
        font-size: 24px;
        margin-bottom: 6px;
    }

    .landing-light-section {
        padding: 85px 0;
    }

    .landing-section-head {
        margin-bottom: 34px;
        text-align: center;
    }

    .landing-section-head span {
        color: #f59e0b;
        display: block;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.16em;
        margin-bottom: 14px;
        text-transform: uppercase;
    }

    .landing-section-head h2 {
        color: #0f172a;
        font-size: 42px;
        line-height: 1.1;
        margin-bottom: 0;
    }

    .landing-section-head p {
        color: #64748b;
        font-size: 17px;
        margin: 18px auto 0;
        max-width: 640px;
    }

    .landing-compact-grid {
        display: grid;
        gap: 22px;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .landing-compact-card {
        background: #fff;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 24px;
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.07);
        padding: 28px;
    }

    .landing-compact-card strong {
        align-items: center;
        background: rgba(245, 158, 11, 0.12);
        border-radius: 14px;
        color: #d97706;
        display: inline-flex;
        font-size: 16px;
        font-weight: 800;
        height: 46px;
        justify-content: center;
        margin-bottom: 18px;
        width: 46px;
    }

    .landing-compact-card h3 {
        color: #0f172a;
        font-size: 24px;
        margin-bottom: 12px;
    }

    .landing-compact-card p {
        color: #64748b;
        margin-bottom: 0;
    }

    .landing-compare-wrap {
        display: grid;
        gap: 22px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .landing-compare-card {
        border-radius: 26px;
        padding: 34px;
    }

    .landing-compare-card.is-soft {
        background: #eef2ff;
        border: 1px solid rgba(99, 102, 241, 0.14);
    }

    .landing-compare-card.is-bold {
        background: linear-gradient(135deg, #0f172a, #1e293b);
        border: 1px solid rgba(245, 158, 11, 0.18);
        color: #fff;
    }

    .landing-compare-card span {
        display: block;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.14em;
        margin-bottom: 16px;
        text-transform: uppercase;
    }

    .landing-compare-card h3 {
        font-size: 32px;
        line-height: 1.15;
        margin-bottom: 16px;
    }

    .landing-compare-card p {
        margin-bottom: 0;
    }

    .landing-compare-card.is-soft p {
        color: #475569;
    }

    .landing-compare-card.is-bold p,
    .landing-compare-card.is-bold span {
        color: rgba(255, 255, 255, 0.78);
    }

    .landing-cta-panel {
        background: linear-gradient(135deg, #0b1228, #16203f);
        border: 1px solid rgba(245, 158, 11, 0.16);
        border-radius: 28px;
        box-shadow: 0 26px 60px rgba(15, 23, 42, 0.18);
        padding: 38px;
        text-align: center;
    }

    .landing-cta-panel h2 {
        color: #fff;
        font-size: 42px;
        margin-bottom: 12px;
    }

    .landing-cta-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        justify-content: center;
        margin-top: 26px;
    }

    @media (max-width: 1199px) {
        .landing-hero h1 {
            font-size: 54px;
        }
    }

    @media (max-width: 991px) {
        .landing-shell {
            background:
                radial-gradient(circle at top right, rgba(245, 158, 11, 0.18), transparent 32%),
                linear-gradient(180deg, #071022 0%, #0b1430 44%, #ffffff 44%, #ffffff 100%);
        }

        .landing-hero {
            padding: 100px 0 70px;
        }

        .landing-hero-copy {
            margin-bottom: 34px;
        }

        .landing-visual-panel {
            margin: 0;
            max-width: none;
        }

        .landing-proof-row,
        .landing-compact-grid,
        .landing-compare-wrap {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767px) {
        .landing-hero h1,
        .landing-section-head h2,
        .landing-cta-panel h2 {
            font-size: 34px;
        }

        .landing-hero p,
        .landing-section-head p {
            font-size: 16px;
        }

        .landing-proof-row,
        .landing-visual-grid {
            grid-template-columns: 1fr;
        }

        .landing-light-section {
            padding: 68px 0;
        }

        .landing-compare-card,
        .landing-cta-panel,
        .landing-compact-card {
            padding: 24px;
        }
    }
</style>
@endpush

@section('content')
<main class="landing-shell">
    <section class="landing-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7 landing-hero-copy">
                    <span class="landing-kicker">Simple. Premium. Clear.</span>
                    <h1>Grow with <span>clarity</span>, not confusion.</h1>
                    <p>Create your account, choose your package, track every result, and move through the platform with a clean dashboard experience.</p>
                    <div class="landing-hero-actions">
                        <a href="{{ route('register') }}" class="btn">Start Now</a>
                        <a href="{{ route('marketing.how-it-works') }}" class="btn landing-outline-btn">See How It Works</a>
                    </div>
                    <div class="landing-proof-row">
                        <div class="landing-proof-card">
                            <strong>3 steps</strong>
                            <span>Start to tracking</span>
                        </div>
                        <div class="landing-proof-card">
                            <strong>1 wallet</strong>
                            <span>All earnings visible</span>
                        </div>
                        <div class="landing-proof-card">
                            <strong>24/7</strong>
                            <span>Dashboard access</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 landing-hero-visual">
                    <div class="landing-visual-panel">
                        <div class="landing-visual-top">
                            <div>
                                <strong>ZagChain Dashboard</strong>
                                <span>Clean growth overview</span>
                            </div>
                            <div class="landing-status-pill">Live tracking</div>
                        </div>
                        <div class="landing-visual-chart">
                            <strong style="color:#fff;font-size:30px;">$1,000</strong>
                            <span>Starting package example</span>
                            <div class="landing-visual-line">
                                <i></i>
                                <i></i>
                                <i></i>
                                <i></i>
                                <i></i>
                            </div>
                            <span>Clear monthly progress view</span>
                        </div>
                        <div class="landing-visual-grid">
                            <div class="landing-mini-card">
                                <strong>Wallet</strong>
                                <span>Available, locked, paid</span>
                            </div>
                            <div class="landing-mini-card">
                                <strong>Referral</strong>
                                <span>Direct and team growth</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="landing-light-section">
        <div class="container">
            <div class="landing-section-head">
                <span>How it flows</span>
                <h2>Fast to understand.</h2>
                <p>No long explanation needed. The path is simple from the first click.</p>
            </div>
            <div class="landing-compact-grid">
                <div class="landing-compact-card">
                    <strong>1</strong>
                    <h3>Create account</h3>
                    <p>Register, verify your email, and enter the platform in minutes.</p>
                </div>
                <div class="landing-compact-card">
                    <strong>2</strong>
                    <h3>Pick your package</h3>
                    <p>Choose the level that fits your pace and activate your position.</p>
                </div>
                <div class="landing-compact-card">
                    <strong>3</strong>
                    <h3>Track everything</h3>
                    <p>See earnings, wallet movement, and network growth in one place.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="pb-90">
        <div class="container">
            <div class="landing-section-head">
                <span>Why it feels different</span>
                <h2>Less friction. More visibility.</h2>
            </div>
            <div class="landing-compare-wrap">
                <div class="landing-compare-card is-soft">
                    <span>Traditional feel</span>
                    <h3>Slow, scattered, hard to follow.</h3>
                    <p>Many people are used to waiting without seeing a clean day-to-day picture of what is happening.</p>
                </div>
                <div class="landing-compare-card is-bold">
                    <span>ZagChain feel</span>
                    <h3>Simple action. Visible progress.</h3>
                    <p>Your account, package, wallet, earnings, referrals, and payout path stay connected inside one experience.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="pb-100">
        <div class="container">
            <div class="landing-cta-panel">
                <h2>Start clean. Build steadily.</h2>
                <p>Join ZagChain and move with a platform designed to feel direct, premium, and easy to follow.</p>
                <div class="landing-cta-actions">
                    <a href="{{ route('register') }}" class="btn">Create Account</a>
                    <a href="{{ route('login') }}" class="btn landing-outline-btn">Login</a>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection
