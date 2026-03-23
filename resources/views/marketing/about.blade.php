@extends('marketing.legacy-dark-layout', ['pageTitle' => 'ZagChain | How It Works'])

@section('content')
<main>
    <div class="slider-area">
        <div class="single-slider slider-height2 d-flex align-items-center legacy-page-hero" style="background-image: url('{{ asset('legacy-start/assets/img/hero/about.jpg') }}');">
            <div class="container">
                <div class="row">
                    <div class="col-xl-12">
                        <div class="hero-cap">
                            <h2>How It Works</h2>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('landing') }}">Home</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('marketing.how-it-works') }}">How It Works</a></li>
                                </ol>
                            </nav>
                            <p class="legacy-page-intro">ZagChain is designed as one connected client journey. The user registers, chooses a subscription package, grows profile power, builds a referral structure, follows miner visibility, and understands profits from one dashboard experience instead of separate disconnected systems.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="about-low-area section-padding30">
        <div class="container">
            <div class="row align-items-center mb-80">
                <div class="col-lg-6 col-md-12">
                    <div class="about-caption mb-50">
                        <div class="section-tittle mb-35">
                            <span>ZagChain system</span>
                            <h2>One connected platform that explains the full client journey from registration to tracked profits.</h2>
                        </div>
                        <p>The client journey starts with registration and continues into a complete dashboard experience. After joining, the client can choose a mining package, activate an account position, and begin following rewards through a clear user flow.</p>
                        <p>Instead of using separate tools, ZagChain keeps everything inside one platform: package subscriptions, profile-power progress, team growth, wallet tracking, daily miner reporting, network strength, and milestone visibility.</p>
                        <p>This makes the system easier to understand for both new users and serious investors, because the client can see what to do next, what affects rewards, and where each earning source comes from.</p>
                        <a href="{{ route('register') }}" class="btn">Start the journey</a>
                    </div>
                </div>
                <div class="col-lg-6 col-md-12">
                    <div class="about-img legacy-system-visual">
                        <div class="about-font-img">
                            <img src="{{ asset('legacy-start/assets/img/gallery/about21.png') }}" alt="ZagChain system overview">
                        </div>
                        <div class="about-back-img d-none d-lg-block">
                            <img src="{{ asset('legacy-start/assets/img/about/about_right.png') }}" alt="ZagChain supporting visual">
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-4 mb-80">
                <div class="col-lg-3 col-md-6">
                    <div class="legacy-system-card">
                        <div class="legacy-system-icon">1</div>
                        <h3>Join the platform</h3>
                        <p>The client creates an account, verifies email, and enters the dashboard where all actions begin.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="legacy-system-card">
                        <div class="legacy-system-icon">2</div>
                        <h3>Activate a package</h3>
                        <p>The user subscribes to a package and receives a clear package position tied to rewards and growth potential.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="legacy-system-card">
                        <div class="legacy-system-icon">3</div>
                        <h3>Grow account power</h3>
                        <p>Invites, referrals, direct investors, and stronger commitment increase profile power and unlock stronger caps.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="legacy-system-card">
                        <div class="legacy-system-icon">4</div>
                        <h3>Track every result</h3>
                        <p>The client can see wallet history, network strength, miner reporting, and personal reward progress in one place.</p>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-xl-8 col-lg-8">
                    <div class="section-tittle text-center mb-70">
                        <span>ZagChain flow</span>
                        <h2>A simple client journey from registration to tracked profits</h2>
                    </div>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-6 col-md-6">
                    <div class="legacy-step-card">
                        <div class="legacy-step-number">1</div>
                        <h3>Register and verify</h3>
                        <p>The client creates an account, verifies email, and enters the dashboard. From there, they can access packages, profile tools, wallet tracking, and network growth pages.</p>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6">
                    <div class="legacy-step-card">
                        <div class="legacy-step-number">2</div>
                        <h3>Choose a package</h3>
                        <p>The client selects a mining subscription package such as Basic 100, Growth 500, or Scale 1000+, each with its own share position and return structure.</p>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6">
                    <div class="legacy-step-card">
                        <div class="legacy-step-number">3</div>
                        <h3>Grow profile power</h3>
                        <p>Verified invites, registered referrals, active direct investors, and stronger capital commitment all increase profile power and unlock stronger reward caps.</p>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6">
                    <div class="legacy-step-card">
                        <div class="legacy-step-number">4</div>
                        <h3>Build network rewards</h3>
                        <p>As the team grows, the client can earn direct referral rewards and multi-level team rewards, while also becoming more visible through ranks, milestones, and leaderboard progress.</p>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6">
                    <div class="legacy-step-card">
                        <div class="legacy-step-number">5</div>
                        <h3>Track the miner story</h3>
                        <p>The platform also shows daily miner performance through hashrate, revenue, costs, net profit, and per-share reporting, creating a stronger and more transparent operational picture.</p>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6">
                    <div class="legacy-step-card">
                        <div class="legacy-step-number">6</div>
                        <h3>Understand profits clearly</h3>
                        <p>The wallet and investments pages help the client see exactly where earnings come from, including miner share income, package return, referral rewards, and team bonuses.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="contact-form-area section-bg pt-115 pb-120 fix" style="background-image: url('{{ asset('legacy-start/assets/img/gallery/section_bg04.jpg') }}'); background-size: cover;">
        <div class="container">
            <div class="row justify-content-end mb-80">
                <div class="col-xl-8 col-lg-9">
                    <div class="contact-form-wrapper">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="section-tittle mb-50">
                                    <span>Why clients choose ZagChain</span>
                                    <h2>Clear rewards, visible growth, and a stronger investor story</h2>
                                    <p>Instead of leaving the client with hidden calculations, ZagChain shows package returns, profile-power progression, reward caps, network growth, and miner reporting in one clean flow.</p>
                                </div>
                            </div>
                        </div>
                        <div class="legacy-dark-panel">
                            <ul class="legacy-mini-list list-unstyled mb-0">
                                <li>Basic 100 can grow toward a 4% profile-power cap</li>
                                <li>Growth 500 can grow toward a 6% profile-power cap</li>
                                <li>Scale 1000+ can grow toward a 7% profile-power cap</li>
                                <li>Wallet pages separate miner income, monthly return, referral income, and MLM rewards</li>
                                <li>Daily miner reporting gives clients a more transparent performance picture</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="legacy-highlight-grid mb-80">
                <div class="legacy-highlight-item">
                    <h4>What makes the system clear</h4>
                    <p>The client can see package information, profile power, team activity, and miner data without leaving the platform or guessing how rewards are formed.</p>
                </div>
                <div class="legacy-highlight-item">
                    <h4>What makes the system stronger</h4>
                    <p>Growth is visible. More verified invites, more active investors, and stronger commitment increase both account strength and benefit potential.</p>
                </div>
                <div class="legacy-highlight-item">
                    <h4>What the wallet explains</h4>
                    <p>The wallet separates miner income, package return, direct referral rewards, and team rewards, so the client understands every earning source.</p>
                </div>
                <div class="legacy-highlight-item">
                    <h4>What the miner page proves</h4>
                    <p>Daily miner visibility through hashrate, revenue, costs, net profit, and share reporting helps give the platform a more transparent operational story.</p>
                </div>
            </div>
            <div class="row justify-content-between align-items-center">
                <div class="col-xl-7 col-lg-7">
                    <div class="section-tittle section-tittle2 mb-25">
                        <span>Reward structure</span>
                        <h2>How the monthly benefit becomes stronger</h2>
                    </div>
                    <div class="legacy-dark-panel">
                        <ul class="legacy-mini-list list-unstyled mb-0">
                            <li>Base package return starts the monthly reward structure</li>
                            <li>Investor level bonus adds strength as the account matures</li>
                            <li>Invite and team bonus reward real network activity</li>
                            <li>Profile power adds an extra cap-based reward layer</li>
                            <li>Basic 100 can grow toward 4%</li>
                            <li>Growth 500 can grow toward 6%</li>
                            <li>Scale 1000+ can grow toward 7%</li>
                        </ul>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-5 col-md-8 mt-4 mt-lg-0">
                    <div class="legacy-explain-card">
                        <h3>What the client understands at the end</h3>
                        <p class="mb-3">By the end of this page, the visitor should understand the full ZagChain model:</p>
                        <ul class="legacy-mini-list list-unstyled mb-4">
                            <li>How to join the system</li>
                            <li>How packages create account positions</li>
                            <li>How profile power affects reward potential</li>
                            <li>How network growth adds new reward layers</li>
                            <li>How miner performance supports visibility and reporting</li>
                        </ul>
                        <a href="{{ route('register') }}" class="submit-btn">Create Account</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Core experience section temporarily hidden --}}
</main>
@endsection
