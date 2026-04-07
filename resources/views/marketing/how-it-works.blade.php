@extends('marketing.legacy-dark-layout', ['pageTitle' => 'ZagChain | How It Works'])

@section('content')
<main>
    <style>
        .legacy-how-it-works-visual {
            margin-top: -220px;
        }

        .legacy-how-it-works-faq-title span {
            color: #ff8a00;
        }

        .legacy-how-it-works-faq-title h2 {
            color: #ffffff;
        }

        @media (max-width: 991px) {
            .legacy-how-it-works-visual {
                margin-top: 10px;
            }
        }
    </style>

    <div class="slider-area">
        <div class="single-slider slider-height2 legacy-page-hero">
            <video class="legacy-page-video" autoplay muted loop playsinline>
                <source src="{{ asset('branding/ZagChainvid.mp4') }}" type="video/mp4">
            </video>
        </div>
    </div>

    <section class="about-low-area section-padding30 pt-0">
        <div class="container">
            <div class="row align-items-center mb-80">
                <div class="col-lg-6 col-md-12">
                    <div class="about-caption mb-50">
                        <div class="section-tittle mb-35">
                            <span>Easy guide</span>
                            <h2>How ZagChain works in simple steps for a new user.</h2>
                        </div>
                        <p>ZagChain is designed to be easy to follow. You create an account, choose an investment package, track your earnings inside the dashboard, and complete verification before your first withdrawal.</p>
                        <p>Instead of leaving you guessing, the platform separates your mining share, monthly return, referral rewards, team bonuses, and wallet history so you can understand where each amount comes from.</p>
                        <p>This page is here to answer one simple question: what happens after I join?</p>
                        <a href="{{ route('register') }}" class="btn">Create account</a>
                    </div>
                </div>
                <div class="col-lg-6 col-md-12">
                    <div class="about-img legacy-system-visual legacy-how-it-works-visual">
                        <div class="about-font-img">
                            <img src="{{ asset('legacy-start/assets/img/gallery/about21.png') }}" alt="How ZagChain works">
                        </div>
                        <div class="about-back-img d-none d-lg-block">
                            <img src="{{ asset('legacy-start/assets/img/about/about_right.png') }}" alt="ZagChain platform visual">
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-80">
                <div class="col-lg-3 col-md-6">
                    <div class="legacy-system-card">
                        <div class="legacy-system-icon">1</div>
                        <h3>Register</h3>
                        <p>Create your account and verify your email to enter the dashboard.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="legacy-system-card">
                        <div class="legacy-system-icon">2</div>
                        <h3>Choose a package</h3>
                        <p>Select the package that fits your plan, such as Basic 100, Growth 500, or Scale 1000.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="legacy-system-card">
                        <div class="legacy-system-icon">3</div>
                        <h3>Track results</h3>
                        <p>Follow your mining share, package return, bonuses, and wallet history in one place.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="legacy-system-card">
                        <div class="legacy-system-icon">4</div>
                        <h3>Withdraw later</h3>
                        <p>Complete KYC before your first withdrawal, then request payout from your wallet.</p>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-xl-8 col-lg-8">
                    <div class="section-tittle text-center mb-70">
                        <span>Step by step</span>
                        <h2>What happens after you join</h2>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-80">
                <div class="col-lg-6 col-md-6">
                    <div class="legacy-step-card">
                        <div class="legacy-step-number">1</div>
                        <h3>Create your account</h3>
                        <p>Sign up with your basic details. Registration stays simple so you can enter the platform quickly.</p>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6">
                    <div class="legacy-step-card">
                        <div class="legacy-step-number">2</div>
                        <h3>Verify your email</h3>
                        <p>Email verification protects your account and unlocks access to the main dashboard features.</p>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6">
                    <div class="legacy-step-card">
                        <div class="legacy-step-number">3</div>
                        <h3>Buy an investment package</h3>
                        <p>Your package creates your active position inside the platform. Different packages have different daily and monthly cap paths.</p>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6">
                    <div class="legacy-step-card">
                        <div class="legacy-step-number">4</div>
                        <h3>See daily mining share</h3>
                        <p>Mining Daily Share appears in your wallet based on miner performance, BTC price movement, and your package cap rules.</p>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6">
                    <div class="legacy-step-card">
                        <div class="legacy-step-number">5</div>
                        <h3>Wait through the first lock period</h3>
                        <p>New mining-share earnings stay locked during the first 30-day cycle. This helps the wallet show what is pending and what is available.</p>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6">
                    <div class="legacy-step-card">
                        <div class="legacy-step-number">6</div>
                        <h3>Grow with referrals and team bonuses</h3>
                        <p>If you invite others, you can also earn referral and team rewards in addition to your package activity.</p>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6">
                    <div class="legacy-step-card">
                        <div class="legacy-step-number">7</div>
                        <h3>Complete KYC before first withdrawal</h3>
                        <p>KYC is not required during registration, but it becomes required before your first payout request can be approved.</p>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6">
                    <div class="legacy-step-card">
                        <div class="legacy-step-number">8</div>
                        <h3>Request payout from the wallet</h3>
                        <p>Once your earnings are available and your KYC is approved, you can submit a withdrawal request from the wallet page.</p>
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
                                    <span>Simple answers</span>
                                    <h2>What new users usually want to know</h2>
                                    <p>The goal is to make the platform easier to understand from the first visit.</p>
                                </div>
                            </div>
                        </div>
                        <div class="legacy-dark-panel">
                            <ul class="legacy-mini-list list-unstyled mb-0">
                                <li>Your wallet separates available, locked, paid, and projected amounts</li>
                                <li>Mining Daily Share can change from day to day because miner conditions change</li>
                                <li>Your package cap limits the daily and monthly credited amount</li>
                                <li>KYC is completed after registration, before the first withdrawal</li>
                                <li>Admin review helps keep payouts and legal verification controlled</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="legacy-highlight-grid mb-80">
                <div class="legacy-highlight-item">
                    <h4>What you see in the wallet</h4>
                    <p>You can clearly follow daily mining share, monthly return, referral rewards, team bonuses, and payout history.</p>
                </div>
                <div class="legacy-highlight-item">
                    <h4>Why some earnings are locked</h4>
                    <p>New mining-share earnings stay locked during the first 30 days, then they move into the available balance when the cycle completes.</p>
                </div>
                <div class="legacy-highlight-item">
                    <h4>Why amounts change</h4>
                    <p>Daily mining-share amounts respond to miner performance and market conditions, but stay inside the package cap path.</p>
                </div>
                <div class="legacy-highlight-item">
                    <h4>What happens before payout</h4>
                    <p>Before the first withdrawal, the user uploads KYC proof, waits for approval, and then can request payout from the wallet page.</p>
                </div>
            </div>

            <div class="row justify-content-between align-items-center">
                <div class="col-xl-7 col-lg-7">
                    <div class="section-tittle section-tittle2 mb-25">
                        <span>Quick example</span>
                        <h2>How a simple user journey looks</h2>
                    </div>
                    <div class="legacy-dark-panel">
                        <ul class="legacy-mini-list list-unstyled mb-0">
                            <li>Create account and verify email</li>
                            <li>Buy a package such as Growth 500 or Scale 1000</li>
                            <li>See mining share begin to appear in the wallet</li>
                            <li>Watch locked earnings move toward availability after the first cycle</li>
                            <li>Invite others to unlock extra referral and team rewards</li>
                            <li>Upload KYC when preparing for the first withdrawal</li>
                            <li>Request payout after approval and available balance confirmation</li>
                        </ul>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-5 col-md-8 mt-4 mt-lg-0">
                    <div class="legacy-explain-card">
                        <h3>Best place to start</h3>
                        <p class="mb-3">If you are new, the easiest start is:</p>
                        <ul class="legacy-mini-list list-unstyled mb-4">
                            <li>Register</li>
                            <li>Verify your email</li>
                            <li>Review packages</li>
                            <li>Choose your plan</li>
                            <li>Track your wallet and dashboard</li>
                        </ul>
                        <a href="{{ route('register') }}" class="submit-btn">Start now</a>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center mt-5">
                <div class="col-xl-8 col-lg-10">
                    <div class="section-tittle text-center mb-50 legacy-how-it-works-faq-title">
                        <span>FAQ</span>
                        <h2>Simple answers for new users</h2>
                    </div>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="legacy-highlight-item h-100">
                        <h4>Why are some earnings locked?</h4>
                        <p>New mining-share earnings stay locked during the first 30-day cycle. After that cycle finishes, eligible earnings move into the available balance.</p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="legacy-highlight-item h-100">
                        <h4>When do I need KYC?</h4>
                        <p>KYC is not required during registration. It becomes required before your first withdrawal can be approved.</p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="legacy-highlight-item h-100">
                        <h4>Why does daily mining share change?</h4>
                        <p>The daily amount can change because miner conditions, BTC price, and revenue strength change. Your package cap still limits the final credited amount.</p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="legacy-highlight-item h-100">
                        <h4>Where can I see my earnings clearly?</h4>
                        <p>The wallet page shows available, locked, paid, and projected amounts, along with detailed earnings history and payout requests.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection
