<!doctype html>
<html class="no-js" lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>{{ $pageTitle ?? 'ZagChain' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('layout.partials.google-analytics')
    <link rel="icon" type="image/png" href="{{ asset('branding/ZagChain3.png') }}">
    <link rel="stylesheet" href="{{ asset('legacy-start/assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('legacy-start/assets/css/owl.carousel.min.css') }}">
    <link rel="stylesheet" href="{{ asset('legacy-start/assets/css/slicknav.css') }}">
    <link rel="stylesheet" href="{{ asset('legacy-start/assets/css/flaticon.css') }}">
    <link rel="stylesheet" href="{{ asset('legacy-start/assets/css/animate.min.css') }}">
    <link rel="stylesheet" href="{{ asset('legacy-start/assets/css/magnific-popup.css') }}">
    <link rel="stylesheet" href="{{ asset('legacy-start/assets/css/fontawesome-all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('legacy-start/assets/css/themify-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('legacy-start/assets/css/slick.css') }}">
    <link rel="stylesheet" href="{{ asset('legacy-start/assets/css/nice-select.css') }}">
    <link rel="stylesheet" href="{{ asset('legacy-start/assets/css/style.css') }}">
    <style>
        .header-area .main-header .header-top {
            background: #090c2f;
        }
        .hero__caption h1 span,
        .section-tittle span,
        .hero-pera p,
        .footer-copy-right a {
            color: #f59e0b !important;
        }
        .header-right-btn .btn,
        .submit-btn,
        .btn.header-btn,
        .btn {
            background: #f59e0b;
            border-color: #f59e0b;
        }
        .header-right-btn .btn:hover,
        .submit-btn:hover,
        .btn.header-btn:hover,
        .btn:hover {
            background: #d97706;
            border-color: #d97706;
        }
        .legacy-hero {
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .legacy-hero::before {
            background: rgba(5, 9, 35, 0.72);
            content: "";
            inset: 0;
            position: absolute;
        }
        .legacy-hero .container,
        .legacy-hero .row,
        .legacy-hero .hero__caption,
        .legacy-page-hero .container,
        .legacy-page-hero .row,
        .legacy-page-hero .hero-cap {
            position: relative;
            z-index: 2;
        }
        .legacy-page-hero {
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .legacy-page-hero::before {
            background: rgba(5, 9, 35, 0.75);
            content: "";
            inset: 0;
            position: absolute;
        }
        .legacy-stat-card,
        .legacy-step-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
            height: 100%;
            padding: 35px 28px;
        }
        .legacy-stat-card h3,
        .legacy-step-card h3 {
            font-size: 22px;
            margin-bottom: 15px;
        }
        .legacy-step-number {
            align-items: center;
            background: #f59e0b;
            border-radius: 50%;
            color: #fff;
            display: inline-flex;
            font-size: 18px;
            font-weight: 700;
            height: 48px;
            justify-content: center;
            margin-bottom: 18px;
            width: 48px;
        }
        .legacy-mini-list li {
            color: #57667e;
            margin-bottom: 8px;
        }
        .legacy-dark-panel {
            background: #090c2f;
            border-radius: 10px;
            color: #fff;
            padding: 32px;
        }
        .legacy-dark-panel p,
        .legacy-dark-panel li {
            color: rgba(255, 255, 255, 0.78);
        }
        .legacy-page-intro {
            color: rgba(255, 255, 255, 0.82);
            font-size: 18px;
            line-height: 1.8;
            margin-top: 18px;
            max-width: 760px;
        }
        .legacy-system-card,
        .legacy-explain-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 16px 35px rgba(15, 23, 42, 0.08);
            height: 100%;
            padding: 30px 26px;
        }
        .legacy-system-card h3,
        .legacy-explain-card h3 {
            font-size: 22px;
            margin-bottom: 14px;
        }
        .legacy-system-card p,
        .legacy-explain-card p {
            color: #57667e;
            margin-bottom: 0;
        }
        .legacy-system-icon {
            align-items: center;
            background: rgba(245, 158, 11, 0.12);
            border-radius: 50%;
            color: #f59e0b;
            display: inline-flex;
            font-size: 20px;
            font-weight: 700;
            height: 52px;
            justify-content: center;
            margin-bottom: 18px;
            width: 52px;
        }
        .legacy-highlight-grid {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-top: 30px;
        }
        .legacy-highlight-item {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 10px;
            padding: 20px;
        }
        .legacy-highlight-item h4 {
            color: #fff;
            font-size: 18px;
            margin-bottom: 10px;
        }
        .legacy-highlight-item p {
            color: rgba(255, 255, 255, 0.76);
            margin-bottom: 0;
        }
        @media (max-width: 767px) {
            .legacy-highlight-grid {
                grid-template-columns: 1fr;
            }
        }
        .legacy-system-visual {
            margin-top: -140px;
            position: relative;
            z-index: 1;
        }
        .legacy-system-visual .about-font-img img {
            border-radius: 12px;
            display: block;
            max-width: 100%;
            height: auto;
        }
        .legacy-system-visual .about-back-img {
            bottom: -24px;
            max-width: 160px;
            right: -18px;
            z-index: -1;
        }
        .legacy-system-visual .about-back-img img {
            max-width: 100%;
            height: auto;
        }
        @media (max-width: 991px) {
            .legacy-system-visual {
                margin-top: 30px;
            }
            .legacy-system-visual .about-back-img {
                display: none !important;
            }
        }
    </style>
    @stack('style')
</head>
<body>
    <div id="preloader-active">
        <div class="preloader d-flex align-items-center justify-content-center">
            <div class="preloader-inner position-relative">
                <div class="preloader-circle"></div>
                <div class="preloader-img pere-text">
                    <img src="{{ asset('branding/ZagChain3.png') }}" alt="ZagChain loader" style="max-width: 180px; width: 100%; height: auto;">
                </div>
            </div>
        </div>
    </div>

    <header>
        <div class="header-area">
            <div class="main-header">
                <div class="header-top d-none d-lg-block">
                    <div class="container">
                        <div class="row d-flex justify-content-between align-items-center">
                            <div class="header-info-left">
                                <ul>
                                    <li>Email: info@zagchain.net</li>
                                    <li>Digital mining subscriptions and network growth</li>
                                </ul>
                            </div>
                            <div class="header-info-right">
                                <ul class="header-social">
                                    <li><a href="#"><i class="fab fa-twitter"></i></a></li>
                                    <li><a href="#"><i class="fab fa-facebook-f"></i></a></li>
                                    <li><a href="#"><i class="fab fa-linkedin-in"></i></a></li>
                                    <li><a href="#"><i class="fab fa-instagram"></i></a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="header-bottom header-sticky">
                    <div class="container">
                        <div class="row align-items-center">
                            <div class="col-xl-2 col-lg-2">
                                <div class="logo">
                                    <a href="{{ route('landing') }}"><img src="{{ asset('branding/zagchain-logo.png') }}" alt="ZagChain" style="max-width: 220px; width: 100%; height: auto;"></a>
                                </div>
                            </div>
                            <div class="col-xl-10 col-lg-10">
                                <div class="menu-wrapper d-flex align-items-center justify-content-end">
                                    <div class="main-menu d-none d-lg-block">
                                        <nav>
                                            <ul id="navigation">
                                                <li><a href="{{ route('landing') }}">Home</a></li>
                                                <li><a href="{{ route('marketing.how-it-works') }}">How It Works</a></li>
                                            </ul>
                                        </nav>
                                    </div>
                                    <div class="header-right-btn d-none d-lg-block ml-20">
                                        <a href="{{ route('register') }}" class="btn header-btn">Get Started</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mobile_menu d-block d-lg-none"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    @yield('content')

    <footer>
        <div class="footer-area footer-bg">
            <div class="container">
                <div class="footer-top footer-padding">
                    <div class="footer-heading">
                        <div class="row justify-content-between">
                            <div class="col-xl-8 col-lg-8 col-md-8">
                                <div class="wantToWork-caption wantToWork-caption2">
                                    <h2><a href="{{ route('marketing.how-it-works') }}">Understand the model before you join</a></h2>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-4">
                                <span class="contact-number f-right">info@zagchain.net</span>
                            </div>
                        </div>
                    </div>
                    <div class="row d-flex justify-content-between">
                        <div class="col-xl-2 col-lg-2 col-md-4 col-sm-6">
                            <div class="single-footer-caption mb-50">
                                <div class="footer-tittle">
                                    <h4>Pages</h4>
                                    <ul>
                                        <li><a href="{{ route('landing') }}">Home</a></li>
                                        <li><a href="{{ route('marketing.how-it-works') }}">How It Works</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-3 col-md-4 col-sm-6">
                            <div class="single-footer-caption mb-50">
                                <div class="footer-tittle">
                                    <h4>Start Here</h4>
                                    <ul>
                                        <li><a href="{{ route('register') }}">Create Account</a></li>
                                        <li><a href="{{ route('login') }}">Client Login</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-5 col-sm-6">
                            <div class="single-footer-caption mb-50">
                                <div class="footer-logo">
                                    <a href="{{ route('landing') }}"><img src="{{ asset('branding/zagchain-logo.png') }}" alt="ZagChain footer logo" style="max-width: 220px; width: 100%; height: auto;"></a>
                                </div>
                                <div class="footer-tittle">
                                    <div class="footer-pera">
                                        <p class="info1">ZagChain presents mining subscriptions, profile-power growth, and transparent reward tracking through one connected platform.</p>
                                    </div>
                                </div>
                                <div class="footer-social">
                                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                                    <a href="#"><i class="fab fa-twitter"></i></a>
                                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                                    <a href="#"><i class="fab fa-instagram"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="footer-bottom">
                    <div class="row d-flex align-items-center">
                        <div class="col-lg-12">
                            <div class="footer-copy-right text-center">
                                <p>Copyright all rights reserved for <a href="{{ route('landing') }}">ZagChain</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <div id="back-top">
        <a title="Go to Top" href="#"><i class="fas fa-level-up-alt"></i></a>
    </div>

    <script src="{{ asset('legacy-start/assets/js/vendor/modernizr-3.5.0.min.js') }}"></script>
    <script src="{{ asset('legacy-start/assets/js/vendor/jquery-1.12.4.min.js') }}"></script>
    <script src="{{ asset('legacy-start/assets/js/popper.min.js') }}"></script>
    <script src="{{ asset('legacy-start/assets/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('legacy-start/assets/js/jquery.slicknav.min.js') }}"></script>
    <script src="{{ asset('legacy-start/assets/js/owl.carousel.min.js') }}"></script>
    <script src="{{ asset('legacy-start/assets/js/slick.min.js') }}"></script>
    <script src="{{ asset('legacy-start/assets/js/wow.min.js') }}"></script>
    <script src="{{ asset('legacy-start/assets/js/animated.headline.js') }}"></script>
    <script src="{{ asset('legacy-start/assets/js/jquery.magnific-popup.js') }}"></script>
    <script src="{{ asset('legacy-start/assets/js/jquery.nice-select.min.js') }}"></script>
    <script src="{{ asset('legacy-start/assets/js/jquery.sticky.js') }}"></script>
    <script src="{{ asset('legacy-start/assets/js/plugins.js') }}"></script>
    <script src="{{ asset('legacy-start/assets/js/main.js') }}"></script>
    @stack('custom-scripts')
</body>
</html>
