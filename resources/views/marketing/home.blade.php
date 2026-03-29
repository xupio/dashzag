@extends('marketing.legacy-dark-layout', ['pageTitle' => 'ZagChain | Home'])

@section('content')
<main>
    <div class="slider-area">
        <div class="single-slider slider-height d-flex align-items-center legacy-hero" style="background-image: url('{{ asset('legacy-start/assets/img/hero/h1_hero2.jpg') }}');">
            <div class="container">
                <div class="row">
                    <div class="col-xl-8 col-lg-9">
                        <div class="hero__caption">
                            <h1>We build mining growth <span>with clarity</span> and visible investor power.</h1>
                        </div>
                        <div class="hero-pera">
                            <p>ZagChain combines mining subscriptions, profile-power rewards, daily reporting, and network growth inside one dark dashboard experience.</p>
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
                        <h3>Mining subscriptions</h3>
                        <p>Choose from starter, growth, and scale packages built around real share positions.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 col-sm-6">
                    <div class="legacy-stat-card mb-30 text-center">
                        <img src="{{ asset('legacy-start/assets/img/service/service_icon_2.png') }}" alt="" class="mb-3">
                        <h3>Profile power</h3>
                        <p>Grow your account strength through verified invites, referrals, team investors, and capital commitment.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 col-sm-6">
                    <div class="legacy-stat-card mb-30 text-center">
                        <img src="{{ asset('legacy-start/assets/img/service/service_icon_3.png') }}" alt="" class="mb-3">
                        <h3>Transparent tracking</h3>
                        <p>Follow miner reports, wallet breakdowns, daily share income, and full network performance.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

</main>
@endsection
