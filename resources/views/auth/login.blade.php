<x-guest-layout>
    @push('styles')
        <style>
            .login-shell {
                display: grid;
                gap: 1.4rem;
            }

            .login-hero {
                padding: 1.35rem 1.4rem;
                border-radius: 1.3rem;
                background: linear-gradient(135deg, #081122 0%, #10264a 58%, #2563eb 100%);
                color: #ffffff;
                box-shadow: 0 20px 45px rgba(15, 23, 42, 0.16);
            }

            .login-eyebrow {
                display: inline-block;
                margin-bottom: .85rem;
                padding: .42rem .8rem;
                border: 1px solid rgba(255, 255, 255, 0.2);
                border-radius: 999px;
                color: #dbe8ff;
                font-size: .7rem;
                font-weight: 700;
                letter-spacing: .08em;
                text-transform: uppercase;
            }

            .login-hero h1 {
                margin: 0 0 .7rem;
                font-size: 1.95rem;
                line-height: 1.15;
                color: #ffffff;
            }

            .login-hero p {
                margin: 0;
                color: #d7e6ff;
                font-size: .98rem;
                line-height: 1.75;
            }

            .login-panel {
                border: 1px solid #d9e4f2;
                border-radius: 1.3rem;
                background: #ffffff;
                overflow: hidden;
            }

            .login-panel__body {
                padding: 1.45rem;
            }

            .login-summary {
                margin-bottom: 1.1rem;
                padding: 1rem 1.1rem;
                border: 1px solid #dbe8ff;
                border-radius: 1rem;
                background: linear-gradient(180deg, #f8fbff 0%, #eef5ff 100%);
            }

            .login-summary__title {
                margin-bottom: .45rem;
                color: #31507f;
                font-size: .76rem;
                font-weight: 700;
                letter-spacing: .08em;
                text-transform: uppercase;
            }

            .login-summary p {
                margin: 0;
                color: #334155;
                font-size: .94rem;
                line-height: 1.75;
            }

            .login-success {
                margin-bottom: 1rem;
                padding: .95rem 1.05rem;
                border: 1px solid #b9e6c9;
                border-radius: 1rem;
                background: #edf9f1;
                color: #177245;
                font-size: .92rem;
                line-height: 1.7;
            }

            .login-shell .label {
                display: inline-block;
                margin-bottom: .45rem;
                color: #1f2d44;
                font-size: .88rem;
                font-weight: 700;
            }

            .login-shell .form-control {
                min-height: 50px;
                border-radius: .95rem;
                border: 1px solid #d3deeb;
                padding: .8rem .95rem;
                box-shadow: none;
            }

            .login-shell .form-control:focus {
                border-color: #2563eb;
                box-shadow: 0 0 0 0.18rem rgba(37, 99, 235, 0.14);
            }

            .login-remember {
                display: flex;
                align-items: center;
                gap: .55rem;
                margin-top: .2rem;
                color: #334155;
                font-size: .92rem;
            }

            .login-remember input {
                width: 16px;
                height: 16px;
                accent-color: #2563eb;
            }

            .login-actions {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: .9rem;
                margin-top: 1.25rem;
                flex-wrap: wrap;
            }

            .login-links {
                display: flex;
                flex-direction: column;
                gap: .45rem;
            }

            .login-link {
                color: #23406a;
                font-weight: 600;
                text-decoration: none;
            }

            .login-link:hover {
                text-decoration: underline;
            }

            .login-submit {
                min-width: 180px;
                border: none;
                border-radius: .95rem;
                padding: .9rem 1.25rem;
                background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
                color: #ffffff;
                font-weight: 700;
            }

            .login-submit:hover {
                filter: brightness(1.02);
            }

            @media (max-width: 767px) {
                .login-hero h1 {
                    font-size: 1.7rem;
                }

                .login-panel__body,
                .login-hero {
                    padding: 1.1rem;
                }

                .login-actions {
                    align-items: stretch;
                }

                .login-submit {
                    width: 100%;
                }
            }
        </style>
    @endpush

    <div class="login-shell">
        <div class="login-hero">
            <div class="login-eyebrow">Welcome back</div>
            <h1>Sign in and continue your ZagChain journey</h1>
            <p>
                Access your dashboard, track your progress, and continue from exactly where you left off.
            </p>
        </div>

        <div class="login-panel">
            <div class="login-panel__body">
                <div class="login-summary">
                    <div class="login-summary__title">Secure access</div>
                    <p>
                        Sign in with your registered email and password to return to your account, wallet,
                        invitations, and dashboard activity.
                    </p>
                </div>

                @if (session('status'))
                    <div class="login-success">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="form-group">
                        <label class="label" for="email">Email address</label>
                        <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="Enter your email address">
                        @error('email')<div class="error-text">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="label" for="password">Password</label>
                        <input id="password" class="form-control" type="password" name="password" required autocomplete="current-password" placeholder="Enter your password">
                        @error('password')<div class="error-text">{{ $message }}</div>@enderror
                    </div>

                    <label class="login-remember" for="remember_me">
                        <input id="remember_me" type="checkbox" name="remember">
                        <span>Remember me on this device</span>
                    </label>

                    <div class="login-actions">
                        <div class="login-links">
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="login-link">Forgot your password?</a>
                            @endif
                            <a href="{{ route('register') }}" class="login-link">Need an account? Create one</a>
                        </div>

                        <button class="login-submit" type="submit">Sign in</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
