@extends('marketing.legacy-dark-layout', ['pageTitle' => 'ZagChain | Home'])

@section('content')
<main>
    <style>
        .legacy-home-hero-copy {
            margin-top: -120px;
        }

        @media (max-width: 991px) {
            .legacy-home-hero-copy {
                margin-top: -45px;
            }
        }
    </style>

    <div class="slider-area">
        <div class="single-slider slider-height d-flex align-items-center legacy-hero" style="background-image: url('{{ asset('legacy-start/assets/img/hero/h1_hero2.jpg') }}');">
            <div class="container">
                <div class="row">
                    <div class="col-xl-8 col-lg-9 legacy-home-hero-copy">
                        <div class="hero__caption">
                            <h1>Start simple. <span>Track clearly.</span> Grow with ZagChain.</h1>
                        </div>
                        <div class="hero-pera">
                            <p>Create your account, choose a package, follow your earnings in the dashboard, and complete KYC before your first withdrawal. ZagChain keeps the journey clear from day one.</p>
                        </div>
                        <div class="d-flex flex-wrap gap-3 mt-4 align-items-center">
                            <a href="{{ route('register') }}" class="btn">Create Account</a>
                            <a href="{{ route('marketing.how-it-works') }}" class="btn" style="background:#1f2b6c;border-color:#1f2b6c;">How It Works</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="our-info-area pt-70 pb-40">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 col-sm-6">
                    <div class="legacy-stat-card mb-30 text-center">
                        <img src="{{ asset('legacy-start/assets/img/service/service_icon_1.png') }}" alt="" class="mb-3">
                        <h3>Choose a package</h3>
                        <p>Start with the package that fits your budget and follow a clear investment path inside the platform.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 col-sm-6">
                    <div class="legacy-stat-card mb-30 text-center">
                        <img src="{{ asset('legacy-start/assets/img/service/service_icon_2.png') }}" alt="" class="mb-3">
                        <h3>Track your earnings</h3>
                        <p>Use the dashboard and wallet to see your mining share, returns, bonuses, and payout progress clearly.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 col-sm-6">
                    <div class="legacy-stat-card mb-30 text-center">
                        <img src="{{ asset('legacy-start/assets/img/service/service_icon_3.png') }}" alt="" class="mb-3">
                        <h3>Grow with referrals</h3>
                        <p>Invite others, build your network, and unlock extra reward opportunities as your community grows.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="about-low-area pt-40 pb-70">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-8 col-lg-9">
                    <div class="section-tittle text-center mb-50">
                        <span>Simple flow</span>
                        <h2>How ZagChain works in 3 easy steps</h2>
                    </div>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="legacy-system-card text-center h-100">
                        <div class="legacy-system-icon">1</div>
                        <h3>Create account</h3>
                        <p>Join the platform with a simple registration and verify your email to access the dashboard.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="legacy-system-card text-center h-100">
                        <div class="legacy-system-icon">2</div>
                        <h3>Choose your package</h3>
                        <p>Select the investment package that matches your plan and activate your position inside the platform.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="legacy-system-card text-center h-100">
                        <div class="legacy-system-icon">3</div>
                        <h3>Track and grow</h3>
                        <p>Follow your earnings, watch your wallet, and grow with referral and team opportunities over time.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="contact-form-area section-bg pt-80 pb-90 fix" style="background-image: url('{{ asset('legacy-start/assets/img/gallery/section_bg04.jpg') }}'); background-size: cover;">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-8 col-lg-9">
                    <div class="section-tittle text-center mb-50" style="color:#fff;">
                        <span>Simple comparison</span>
                        <h2 style="color:#fff;">What the difference can look like with $1,000</h2>
                        <p style="color: rgba(255,255,255,0.82);">This example helps a new visitor understand why the ZagChain path feels different from traditional yearly bank growth.</p>
                    </div>
                </div>
            </div>
            <div class="row g-4 align-items-stretch">
                <div class="col-lg-6">
                    <div class="legacy-explain-card h-100">
                        <h3 class="mb-3">Traditional bank example</h3>
                        <p class="mb-4">A $1,000 deposit in a bank with a 4% annual return grows slowly across the full year.</p>
                        <ul class="legacy-mini-list list-unstyled mb-0">
                            <li>$1,000 deposit</li>
                            <li>4% yearly return</li>
                            <li>About $40 in 12 months</li>
                            <li>About $3.33 per month</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="legacy-explain-card h-100" style="border-color: rgba(255, 138, 0, 0.35); box-shadow: 0 20px 45px rgba(255, 138, 0, 0.12);">
                        <h3 class="mb-3">ZagChain example</h3>
                        <p class="mb-4">A $1,000 package on a 4% monthly growth example can show a much stronger month-by-month path.</p>
                        <ul class="legacy-mini-list list-unstyled mb-0">
                            <li>$1,000 package</li>
                            <li>4% monthly growth example</li>
                            <li>About $40 in 1 month</li>
                            <li>About $480 across 12 months</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center mt-5">
                <div class="col-xl-8">
                    <div class="legacy-dark-panel text-center">
                        <p class="mb-0" style="color:#fff;">What a bank may give in one year, ZagChain can match in about one month in this example.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

</main>
@endsection
