@extends('marketing.legacy-dark-layout', ['pageTitle' => 'ZagChain | How It Works'])

@push('style')
<style>
    .how-shell {
        background:
            radial-gradient(circle at top right, rgba(245, 158, 11, 0.16), transparent 26%),
            linear-gradient(180deg, #071022 0%, #0b1430 46%, #f8fafc 46%, #f8fafc 100%);
    }

    .how-hero {
        overflow: hidden;
        padding: 118px 0 88px;
        position: relative;
    }

    .how-hero::before {
        background:
            radial-gradient(circle at 15% 25%, rgba(255, 255, 255, 0.08), transparent 26%),
            radial-gradient(circle at 82% 16%, rgba(245, 158, 11, 0.22), transparent 22%);
        content: "";
        inset: 0;
        pointer-events: none;
        position: absolute;
    }

    .how-hero-copy,
    .how-hero-visual {
        position: relative;
        z-index: 1;
    }

    .how-kicker {
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

    .how-hero h1 {
        color: #fff;
        font-size: 58px;
        font-weight: 800;
        letter-spacing: -0.03em;
        line-height: 1.02;
        margin-bottom: 18px;
        max-width: 640px;
    }

    .how-hero h1 span {
        color: #f59e0b;
    }

    .how-hero p {
        color: rgba(255, 255, 255, 0.76);
        font-size: 18px;
        line-height: 1.8;
        margin-bottom: 0;
        max-width: 570px;
    }

    .how-hero-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        margin-top: 30px;
    }

    .how-outline-btn {
        background: transparent !important;
        border: 1px solid rgba(255, 255, 255, 0.18) !important;
        color: #fff !important;
    }

    .how-outline-btn:hover {
        background: rgba(255, 255, 255, 0.08) !important;
        border-color: rgba(255, 255, 255, 0.3) !important;
    }

    .how-proof-grid {
        display: grid;
        gap: 16px;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        margin-top: 34px;
        max-width: 640px;
    }

    .how-proof-card {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 18px;
        padding: 18px;
    }

    .how-proof-card strong {
        color: #fff;
        display: block;
        font-size: 28px;
        line-height: 1;
        margin-bottom: 8px;
    }

    .how-proof-card span {
        color: rgba(255, 255, 255, 0.66);
        display: block;
        font-size: 13px;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .how-hero-panel {
        background: linear-gradient(180deg, rgba(15, 23, 42, 0.95), rgba(8, 14, 29, 0.98));
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 28px;
        box-shadow: 0 28px 80px rgba(2, 6, 23, 0.45);
        margin-left: auto;
        max-width: 450px;
        padding: 28px;
    }

    .how-hero-panel h3 {
        color: #fff;
        font-size: 28px;
        margin-bottom: 18px;
    }

    .how-hero-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .how-hero-list li {
        align-items: flex-start;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        color: rgba(255, 255, 255, 0.76);
        display: flex;
        gap: 14px;
        padding: 16px 0;
    }

    .how-hero-list li:first-child {
        border-top: 0;
        padding-top: 0;
    }

    .how-hero-list b {
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

    .how-hero-list strong {
        color: #fff;
        display: block;
        font-size: 18px;
        margin-bottom: 4px;
    }

    .how-section {
        padding: 84px 0;
    }

    .how-head {
        margin-bottom: 34px;
        text-align: center;
    }

    .how-head span {
        color: #f59e0b;
        display: block;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.16em;
        margin-bottom: 14px;
        text-transform: uppercase;
    }

    .how-head h2 {
        color: #0f172a;
        font-size: 42px;
        line-height: 1.08;
        margin-bottom: 0;
    }

    .how-head p {
        color: #64748b;
        font-size: 17px;
        margin: 18px auto 0;
        max-width: 690px;
    }

    .how-stage-grid {
        display: grid;
        gap: 22px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .how-stage-card,
    .how-detail-card,
    .how-answer-card,
    .how-cta-card {
        background: #fff;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 24px;
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.07);
        padding: 28px;
    }

    .how-stage-number,
    .how-detail-number {
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

    .how-stage-card h3,
    .how-detail-card h3,
    .how-answer-card h3,
    .how-cta-card h3 {
        color: #0f172a;
        font-size: 24px;
        margin-bottom: 12px;
    }

    .how-stage-card p,
    .how-detail-card p,
    .how-answer-card p,
    .how-cta-card p {
        color: #64748b;
        margin-bottom: 0;
    }

    .how-detail-grid {
        display: grid;
        gap: 22px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .how-answer-wrap {
        display: grid;
        gap: 22px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .how-answer-card {
        background: linear-gradient(180deg, #ffffff, #f8fafc);
    }

    .how-clarity-panel {
        background: linear-gradient(135deg, #0b1228, #16203f);
        border: 1px solid rgba(245, 158, 11, 0.16);
        border-radius: 28px;
        box-shadow: 0 26px 60px rgba(15, 23, 42, 0.18);
        color: #fff;
        padding: 34px;
    }

    .how-clarity-panel h3 {
        color: #fff;
        font-size: 34px;
        margin-bottom: 14px;
    }

    .how-clarity-panel p {
        color: rgba(255, 255, 255, 0.74);
        margin-bottom: 0;
    }

    .how-clarity-list {
        display: grid;
        gap: 16px;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        list-style: none;
        margin: 28px 0 0;
        padding: 0;
    }

    .how-clarity-list li {
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 18px;
        color: rgba(255, 255, 255, 0.78);
        padding: 18px;
    }

    .how-clarity-list strong {
        color: #fff;
        display: block;
        font-size: 18px;
        margin-bottom: 6px;
    }

    .how-cta-card {
        text-align: center;
    }

    .how-cta-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        justify-content: center;
        margin-top: 24px;
    }

    @media (max-width: 1199px) {
        .how-hero h1 {
            font-size: 50px;
        }

        .how-stage-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 991px) {
        .how-shell {
            background:
                radial-gradient(circle at top right, rgba(245, 158, 11, 0.18), transparent 32%),
                linear-gradient(180deg, #071022 0%, #0b1430 38%, #f8fafc 38%, #f8fafc 100%);
        }

        .how-hero {
            padding: 100px 0 68px;
        }

        .how-hero-copy {
            margin-bottom: 34px;
        }

        .how-hero-panel {
            margin: 0;
            max-width: none;
        }

        .how-proof-grid,
        .how-detail-grid,
        .how-answer-wrap,
        .how-clarity-list {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767px) {
        .how-hero h1,
        .how-head h2,
        .how-clarity-panel h3 {
            font-size: 34px;
        }

        .how-hero p,
        .how-head p {
            font-size: 16px;
        }

        .how-proof-grid,
        .how-stage-grid {
            grid-template-columns: 1fr;
        }

        .how-section {
            padding: 68px 0;
        }

        .how-stage-card,
        .how-detail-card,
        .how-answer-card,
        .how-clarity-panel,
        .how-cta-card {
            padding: 24px;
        }
    }
</style>
@endpush

@section('content')
<main class="how-shell">
    <section class="how-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7 how-hero-copy">
                    <span class="how-kicker">Simple user journey</span>
                    <h1>See exactly <span>how it works</span> before you start.</h1>
                    <p>ZagChain is built to feel direct. You join, choose your package, track your results, and complete KYC when you are preparing for your first payout.</p>
                    <div class="how-hero-actions">
                        <a href="{{ route('register') }}" class="btn">Create Account</a>
                        <a href="{{ route('landing') }}" class="btn how-outline-btn">Back Home</a>
                    </div>
                    <div class="how-proof-grid">
                        <div class="how-proof-card">
                            <strong>4 stages</strong>
                            <span>Start to payout</span>
                        </div>
                        <div class="how-proof-card">
                            <strong>1 dashboard</strong>
                            <span>Track everything</span>
                        </div>
                        <div class="how-proof-card">
                            <strong>KYC later</strong>
                            <span>Before first withdrawal</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 how-hero-visual">
                    <div class="how-hero-panel">
                        <h3>Quick path</h3>
                        <ul class="how-hero-list">
                            <li>
                                <b>1</b>
                                <div>
                                    <strong>Create account</strong>
                                    <span>Register and verify your email.</span>
                                </div>
                            </li>
                            <li>
                                <b>2</b>
                                <div>
                                    <strong>Choose package</strong>
                                    <span>Select the level that fits your plan.</span>
                                </div>
                            </li>
                            <li>
                                <b>3</b>
                                <div>
                                    <strong>Track results</strong>
                                    <span>Watch wallet activity, earnings, and growth.</span>
                                </div>
                            </li>
                            <li>
                                <b>4</b>
                                <div>
                                    <strong>Complete KYC</strong>
                                    <span>Required before the first payout request.</span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="how-section">
        <div class="container">
            <div class="how-head">
                <span>Main flow</span>
                <h2>The full path in four clean steps.</h2>
                <p>The goal is simple: help a new visitor understand what happens next without making them read too much.</p>
            </div>
            <div class="how-stage-grid">
                <div class="how-stage-card">
                    <div class="how-stage-number">1</div>
                    <h3>Register</h3>
                    <p>Create your account and verify your email so you can access the platform safely.</p>
                </div>
                <div class="how-stage-card">
                    <div class="how-stage-number">2</div>
                    <h3>Activate package</h3>
                    <p>Choose the package that fits your pace, then complete payment to open your position.</p>
                </div>
                <div class="how-stage-card">
                    <div class="how-stage-number">3</div>
                    <h3>Monitor growth</h3>
                    <p>Use the wallet and dashboard to follow earnings, locked balance, and referral activity.</p>
                </div>
                <div class="how-stage-card">
                    <div class="how-stage-number">4</div>
                    <h3>Prepare payout</h3>
                    <p>Upload KYC before your first withdrawal, then request payout when eligible funds are available.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="pb-90">
        <div class="container">
            <div class="how-head">
                <span>What you will notice</span>
                <h2>Clear details at each stage.</h2>
            </div>
            <div class="how-detail-grid">
                <div class="how-detail-card">
                    <div class="how-detail-number">A</div>
                    <h3>Wallet clarity</h3>
                    <p>Your wallet separates available, locked, paid, and projected amounts so the balance is easier to understand.</p>
                </div>
                <div class="how-detail-card">
                    <div class="how-detail-number">B</div>
                    <h3>Daily movement</h3>
                    <p>Mining-share amounts can move up or down based on miner conditions and market performance.</p>
                </div>
                <div class="how-detail-card">
                    <div class="how-detail-number">C</div>
                    <h3>Referral growth</h3>
                    <p>If you invite others, direct rewards and team rewards can appear alongside your main package activity.</p>
                </div>
                <div class="how-detail-card">
                    <div class="how-detail-number">D</div>
                    <h3>Payout control</h3>
                    <p>KYC and admin review help keep the first withdrawal process controlled, legal, and visible.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="pb-90">
        <div class="container">
            <div class="how-clarity-panel">
                <h3>What new users usually want to know</h3>
                <p>The platform is easier to follow when you know where each amount appears and when it becomes available.</p>
                <ul class="how-clarity-list">
                    <li>
                        <strong>Locked earnings</strong>
                        New mining-share earnings stay locked in the first cycle before becoming available.
                    </li>
                    <li>
                        <strong>KYC timing</strong>
                        KYC is not needed during sign-up. It is needed before the first payout is approved.
                    </li>
                    <li>
                        <strong>Visible tracking</strong>
                        The wallet and dashboard help you follow earnings history, balances, and payout status.
                    </li>
                </ul>
            </div>
        </div>
    </section>

    <section class="pb-100">
        <div class="container">
            <div class="how-head">
                <span>Quick answers</span>
                <h2>Short answers for first-time visitors.</h2>
            </div>
            <div class="how-answer-wrap">
                <div class="how-answer-card">
                    <h3>Why are some earnings locked?</h3>
                    <p>New mining-share earnings stay locked in the first cycle so the platform can separate pending amounts from withdrawable funds.</p>
                </div>
                <div class="how-answer-card">
                    <h3>When do I need KYC?</h3>
                    <p>You only need to complete KYC before your first withdrawal request is approved.</p>
                </div>
                <div class="how-answer-card">
                    <h3>Where do I track everything?</h3>
                    <p>Your dashboard and wallet show package progress, earnings sources, payout status, and referral-related activity.</p>
                </div>
                <div class="how-answer-card">
                    <h3>What should I do first?</h3>
                    <p>Register, verify your email, review the available packages, and then choose the plan that fits you best.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="pb-100">
        <div class="container">
            <div class="how-cta-card">
                <h3>Ready to start?</h3>
                <p>Create your account and move into the platform with a much clearer first experience.</p>
                <div class="how-cta-actions">
                    <a href="{{ route('register') }}" class="btn">Create Account</a>
                    <a href="{{ route('login') }}" class="btn how-outline-btn" style="color:#0f172a !important;border-color:rgba(15,23,42,0.12) !important;background:#fff !important;">Login</a>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection
