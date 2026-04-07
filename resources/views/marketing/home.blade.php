@extends('marketing.legacy-dark-layout', ['pageTitle' => 'ZagChain | Home'])

@section('content')
<main>
    <div class="slider-area">
        <div class="single-slider slider-height d-flex align-items-center legacy-hero" style="background-image: url('{{ asset('legacy-start/assets/img/hero/h1_hero2.jpg') }}');">
            <div class="container">
                <div class="row">
                    <div class="col-xl-8 col-lg-9">
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

</main>
@endsection
