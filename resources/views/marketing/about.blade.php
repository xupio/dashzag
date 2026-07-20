@extends('marketing.legacy-dark-layout', ['pageTitle' => 'ZagChain | About'])

@push('style')
<style>
    .about-shell {
        background:
            radial-gradient(circle at top right, rgba(245, 158, 11, 0.16), transparent 26%),
            linear-gradient(180deg, #071022 0%, #0b1430 45%, #ffffff 45%, #ffffff 100%);
    }

    .about-hero {
        overflow: hidden;
        padding: 118px 0 88px;
        position: relative;
    }

    .about-hero::before {
        background:
            radial-gradient(circle at 18% 24%, rgba(255, 255, 255, 0.08), transparent 26%),
            radial-gradient(circle at 82% 16%, rgba(245, 158, 11, 0.22), transparent 22%);
        content: "";
        inset: 0;
        pointer-events: none;
        position: absolute;
    }

    .about-hero-copy,
    .about-hero-visual {
        position: relative;
        z-index: 1;
    }

    .about-kicker {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 999px;
        color: #f8fafc;
        display: inline-flex;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.14em;
        margin-bottom: 22px;
        padding: 10px 18px;
        text-transform: uppercase;
    }

    .about-hero h1 {
        color: #fff;
        font-size: 58px;
        font-weight: 800;
        letter-spacing: -0.03em;
        line-height: 1.02;
        margin-bottom: 18px;
        max-width: 650px;
    }

    .about-hero h1 span {
        color: #f59e0b;
    }

    .about-hero p {
        color: rgba(255, 255, 255, 0.76);
        font-size: 18px;
        line-height: 1.8;
        margin-bottom: 0;
        max-width: 580px;
    }

    .about-hero-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        margin-top: 30px;
    }

    .about-outline-btn {
        background: transparent !important;
        border: 1px solid rgba(255, 255, 255, 0.18) !important;
        color: #fff !important;
    }

    .about-outline-btn:hover {
        background: rgba(255, 255, 255, 0.08) !important;
        border-color: rgba(255, 255, 255, 0.3) !important;
    }

    .about-proof-grid {
        display: grid;
        gap: 16px;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        margin-top: 34px;
        max-width: 640px;
    }

    .about-proof-card {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 18px;
        padding: 18px;
    }

    .about-proof-card strong {
        color: #fff;
        display: block;
        font-size: 28px;
        line-height: 1;
        margin-bottom: 8px;
    }

    .about-proof-card span {
        color: rgba(255, 255, 255, 0.66);
        display: block;
        font-size: 13px;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .about-visual-panel {
        background: linear-gradient(180deg, rgba(15, 23, 42, 0.95), rgba(8, 14, 29, 0.98));
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 28px;
        box-shadow: 0 28px 80px rgba(2, 6, 23, 0.45);
        margin-left: auto;
        max-width: 455px;
        padding: 28px;
    }

    .about-visual-panel h3 {
        color: #fff;
        font-size: 28px;
        margin-bottom: 18px;
    }

    .about-visual-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .about-visual-list li {
        align-items: flex-start;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        color: rgba(255, 255, 255, 0.76);
        display: flex;
        gap: 14px;
        padding: 16px 0;
    }

    .about-visual-list li:first-child {
        border-top: 0;
        padding-top: 0;
    }

    .about-visual-list b {
        align-items: center;
        background: rgba(245, 158, 11, 0.16);
        border-radius: 12px;
        color: #f59e0b;
        display: inline-flex;
        flex: 0 0 38px;
        font-size: 15px;
        height: 38px;
        justify-content: center;
        margin-top: 2px;
    }

    .about-visual-list strong {
        color: #fff;
        display: block;
        font-size: 18px;
        margin-bottom: 4px;
    }

    .about-section {
        padding: 84px 0;
    }

    .about-head {
        margin-bottom: 34px;
        text-align: center;
    }

    .about-head span {
        color: #f59e0b;
        display: block;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.16em;
        margin-bottom: 14px;
        text-transform: uppercase;
    }

    .about-head h2 {
        color: #0f172a;
        font-size: 42px;
        line-height: 1.08;
        margin-bottom: 0;
    }

    .about-head p {
        color: #64748b;
        font-size: 17px;
        margin: 18px auto 0;
        max-width: 690px;
    }

    .about-stage-grid {
        display: grid;
        gap: 22px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .about-stage-card,
    .about-detail-card,
    .about-summary-card,
    .about-cta-card {
        background: #fff;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 24px;
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.07);
        padding: 28px;
    }

    .about-stage-number,
    .about-detail-number {
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

    .about-stage-card h3,
    .about-detail-card h3,
    .about-summary-card h3,
    .about-cta-card h3 {
        color: #0f172a;
        font-size: 24px;
        margin-bottom: 12px;
    }

    .about-stage-card p,
    .about-detail-card p,
    .about-summary-card p,
    .about-cta-card p {
        color: #64748b;
        margin-bottom: 0;
    }

    .about-detail-grid {
        display: grid;
        gap: 22px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .about-summary-panel {
        background: linear-gradient(135deg, #0b1228, #16203f);
        border: 1px solid rgba(245, 158, 11, 0.16);
        border-radius: 28px;
        box-shadow: 0 26px 60px rgba(15, 23, 42, 0.18);
        color: #fff;
        padding: 34px;
    }

    .about-summary-panel h3 {
        color: #fff;
        font-size: 34px;
        margin-bottom: 14px;
    }

    .about-summary-panel p {
        color: rgba(255, 255, 255, 0.74);
        margin-bottom: 0;
    }

    .about-summary-grid {
        display: grid;
        gap: 16px;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        list-style: none;
        margin: 28px 0 0;
        padding: 0;
    }

    .about-summary-grid li {
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 18px;
        color: rgba(255, 255, 255, 0.78);
        padding: 18px;
    }

    .about-summary-grid strong {
        color: #fff;
        display: block;
        font-size: 18px;
        margin-bottom: 6px;
    }

    .about-cta-card {
        text-align: center;
    }

    .about-cta-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        justify-content: center;
        margin-top: 24px;
    }

    @media (max-width: 1199px) {
        .about-hero h1 {
            font-size: 50px;
        }

        .about-stage-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 991px) {
        .about-shell {
            background:
                radial-gradient(circle at top right, rgba(245, 158, 11, 0.18), transparent 32%),
                linear-gradient(180deg, #071022 0%, #0b1430 38%, #ffffff 38%, #ffffff 100%);
        }

        .about-hero {
            padding: 100px 0 68px;
        }

        .about-hero-copy {
            margin-bottom: 34px;
        }

        .about-visual-panel {
            margin: 0;
            max-width: none;
        }

        .about-proof-grid,
        .about-detail-grid,
        .about-summary-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767px) {
        .about-hero h1,
        .about-head h2,
        .about-summary-panel h3 {
            font-size: 34px;
        }

        .about-hero p,
        .about-head p {
            font-size: 16px;
        }

        .about-proof-grid,
        .about-stage-grid {
            grid-template-columns: 1fr;
        }

        .about-section {
            padding: 68px 0;
        }

        .about-stage-card,
        .about-detail-card,
        .about-summary-panel,
        .about-cta-card {
            padding: 24px;
        }
    }
</style>
@endpush

@section('content')
<main class="about-shell">
    <section class="about-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7 about-hero-copy">
                    <span class="about-kicker">About ZagChain</span>
                    <h1>One platform. <span>One journey.</span> Clear from start to growth.</h1>
                    <p>ZagChain is designed to keep the client journey simple: join the platform, activate a package, track every result, and grow through visible progress instead of guesswork.</p>
                    <div class="about-hero-actions">
                        <a href="{{ route('register') }}" class="btn">Start the Journey</a>
                        <a href="{{ route('marketing.how-it-works') }}" class="btn about-outline-btn">See the Flow</a>
                    </div>
                    <div class="about-proof-grid">
                        <div class="about-proof-card">
                            <strong>1 platform</strong>
                            <span>Everything connected</span>
                        </div>
                        <div class="about-proof-card">
                            <strong>4 layers</strong>
                            <span>Account, package, wallet, growth</span>
                        </div>
                        <div class="about-proof-card">
                            <strong>Clear view</strong>
                            <span>From entry to rewards</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 about-hero-visual">
                    <div class="about-visual-panel">
                        <h3>What ZagChain connects</h3>
                        <ul class="about-visual-list">
                            <li>
                                <b>1</b>
                                <div>
                                    <strong>Registration</strong>
                                    <span>Simple sign-up and email verification.</span>
                                </div>
                            </li>
                            <li>
                                <b>2</b>
                                <div>
                                    <strong>Packages</strong>
                                    <span>Clear subscription levels and positions.</span>
                                </div>
                            </li>
                            <li>
                                <b>3</b>
                                <div>
                                    <strong>Wallet tracking</strong>
                                    <span>Visible earning sources and payout flow.</span>
                                </div>
                            </li>
                            <li>
                                <b>4</b>
                                <div>
                                    <strong>Network growth</strong>
                                    <span>Referrals, milestones, and stronger account power.</span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="about-section">
        <div class="container">
            <div class="about-head">
                <span>Main journey</span>
                <h2>How the platform feels to a new client.</h2>
                <p>The experience is meant to move in a clean order, with each next step feeling obvious.</p>
            </div>
            <div class="about-stage-grid">
                <div class="about-stage-card">
                    <div class="about-stage-number">1</div>
                    <h3>Join</h3>
                    <p>Create an account, verify email, and enter the dashboard with a clean starting point.</p>
                </div>
                <div class="about-stage-card">
                    <div class="about-stage-number">2</div>
                    <h3>Activate</h3>
                    <p>Choose a package that fits the plan and turn it into a clear position inside the system.</p>
                </div>
                <div class="about-stage-card">
                    <div class="about-stage-number">3</div>
                    <h3>Track</h3>
                    <p>See wallet activity, earnings sources, miner visibility, and personal progress in one place.</p>
                </div>
                <div class="about-stage-card">
                    <div class="about-stage-number">4</div>
                    <h3>Grow</h3>
                    <p>Build stronger profile power through commitment, referrals, and active direct investors.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="pb-90">
        <div class="container">
            <div class="about-head">
                <span>Why it is clearer</span>
                <h2>Visibility replaces confusion.</h2>
            </div>
            <div class="about-detail-grid">
                <div class="about-detail-card">
                    <div class="about-detail-number">A</div>
                    <h3>Package clarity</h3>
                    <p>Each package creates a visible account position, not a hidden or disconnected investment step.</p>
                </div>
                <div class="about-detail-card">
                    <div class="about-detail-number">B</div>
                    <h3>Reward separation</h3>
                    <p>The wallet helps clients see miner share, monthly return, referral rewards, and team rewards separately.</p>
                </div>
                <div class="about-detail-card">
                    <div class="about-detail-number">C</div>
                    <h3>Growth logic</h3>
                    <p>Profile power becomes easier to understand because stronger activity visibly improves the account path.</p>
                </div>
                <div class="about-detail-card">
                    <div class="about-detail-number">D</div>
                    <h3>Miner visibility</h3>
                    <p>Daily miner reporting gives the platform a stronger operational story and more transparent performance view.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="pb-90">
        <div class="container">
            <div class="about-summary-panel">
                <h3>Why clients choose ZagChain</h3>
                <p>Instead of scattering the experience across different tools, ZagChain keeps the story together so the user can understand what to do, what changes rewards, and where progress comes from.</p>
                <ul class="about-summary-grid">
                    <li>
                        <strong>Simple entry</strong>
                        Fast registration and a clear first action.
                    </li>
                    <li>
                        <strong>Visible wallet</strong>
                        Separate earning sources and payout progress.
                    </li>
                    <li>
                        <strong>Stronger story</strong>
                        Growth, network activity, and miner visibility in one flow.
                    </li>
                </ul>
            </div>
        </div>
    </section>

    <section class="pb-100">
        <div class="container">
            <div class="about-cta-card">
                <h3>Ready to see it from inside?</h3>
                <p>Create your account and move from explanation into the actual platform journey.</p>
                <div class="about-cta-actions">
                    <a href="{{ route('register') }}" class="btn">Create Account</a>
                    <a href="{{ route('marketing.how-it-works') }}" class="btn about-outline-btn" style="color:#0f172a !important;border-color:rgba(15,23,42,0.12) !important;background:#fff !important;">How It Works</a>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection
