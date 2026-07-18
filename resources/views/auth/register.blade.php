<x-guest-layout>
    @push('styles')
        <style>
            .register-shell {
                display: grid;
                gap: 1.4rem;
            }

            .register-hero {
                padding: 1.35rem 1.4rem;
                border-radius: 1.3rem;
                background: linear-gradient(135deg, #081122 0%, #10264a 58%, #2563eb 100%);
                color: #ffffff;
                box-shadow: 0 20px 45px rgba(15, 23, 42, 0.16);
            }

            .register-eyebrow {
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

            .register-hero h1 {
                margin: 0 0 .7rem;
                font-size: 1.95rem;
                line-height: 1.15;
                color: #ffffff;
            }

            .register-hero p {
                margin: 0;
                color: #d7e6ff;
                font-size: .98rem;
                line-height: 1.75;
            }

            .register-panel {
                border: 1px solid #d9e4f2;
                border-radius: 1.3rem;
                background: #ffffff;
                overflow: hidden;
            }

            .register-panel__body {
                padding: 1.45rem;
            }

            .register-summary {
                margin-bottom: 1.1rem;
                padding: 1rem 1.1rem;
                border: 1px solid #dbe8ff;
                border-radius: 1rem;
                background: linear-gradient(180deg, #f8fbff 0%, #eef5ff 100%);
            }

            .register-summary__title {
                margin-bottom: .45rem;
                color: #31507f;
                font-size: .76rem;
                font-weight: 700;
                letter-spacing: .08em;
                text-transform: uppercase;
            }

            .register-summary p {
                margin: 0;
                color: #334155;
                font-size: .94rem;
                line-height: 1.75;
            }

            .register-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: .95rem;
            }

            .register-grid .form-group--full {
                grid-column: 1 / -1;
            }

            .register-shell .label {
                display: inline-block;
                margin-bottom: .45rem;
                color: #1f2d44;
                font-size: .88rem;
                font-weight: 700;
            }

            .register-shell .form-control {
                min-height: 50px;
                border-radius: .95rem;
                border: 1px solid #d3deeb;
                padding: .8rem .95rem;
                box-shadow: none;
            }

            .register-shell .form-control:focus {
                border-color: #2563eb;
                box-shadow: 0 0 0 0.18rem rgba(37, 99, 235, 0.14);
            }

            .register-notes {
                margin-top: 1.2rem;
                padding: 1rem 1.1rem;
                border: 1px solid #f2dfbf;
                border-radius: 1rem;
                background: #fffaf0;
            }

            .register-notes strong {
                display: block;
                margin-bottom: .4rem;
                color: #8a5a14;
                font-size: .92rem;
            }

            .register-notes p {
                margin: 0;
                color: #7c5b2a;
                font-size: .9rem;
                line-height: 1.7;
            }

            .register-actions {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: .9rem;
                margin-top: 1.25rem;
                flex-wrap: wrap;
            }

            .register-login-link {
                color: #23406a;
                font-weight: 600;
                text-decoration: none;
            }

            .register-login-link:hover {
                text-decoration: underline;
            }

            .register-submit {
                min-width: 180px;
                border: none;
                border-radius: .95rem;
                padding: .9rem 1.25rem;
                background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
                color: #ffffff;
                font-weight: 700;
            }

            .register-submit:hover {
                filter: brightness(1.02);
            }

            @media (max-width: 767px) {
                .register-grid {
                    grid-template-columns: 1fr;
                }

                .register-hero h1 {
                    font-size: 1.7rem;
                }

                .register-panel__body,
                .register-hero {
                    padding: 1.1rem;
                }

                .register-actions {
                    align-items: stretch;
                }

                .register-submit {
                    width: 100%;
                }
            }
        </style>
    @endpush

    <div class="register-shell">
        <div class="register-hero">
            <div class="register-eyebrow">Create account</div>
            <h1>Start your ZagChain account with a clear first step</h1>
            <p>
                Set up your account, verify your email, and continue into the platform at your own pace.
                You can review how ZagChain works before making any commitment.
            </p>
        </div>

        <div class="register-panel">
            <div class="register-panel__body">
                <div class="register-summary">
                    <div class="register-summary__title">Registration overview</div>
                    <p>
                        This account gives you access to the main dashboard, invitation tracking, package selection,
                        and the next steps after email verification.
                    </p>
                </div>

                <form method="POST" action="{{ route('register') }}">
                    @csrf

                    <div class="register-grid">
                        <div class="form-group form-group--full">
                            <label class="label" for="name">Full name</label>
                            <input id="name" class="form-control" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Enter your full name">
                            @error('name')<div class="error-text">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group form-group--full">
                            <label class="label" for="email">Email address</label>
                            <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" placeholder="Enter your email address">
                            @error('email')<div class="error-text">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="label" for="password">Password</label>
                            <input id="password" class="form-control" type="password" name="password" required autocomplete="new-password" placeholder="Create a password">
                            @error('password')<div class="error-text">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="label" for="password_confirmation">Confirm password</label>
                            <input id="password_confirmation" class="form-control" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Repeat your password">
                            @error('password_confirmation')<div class="error-text">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="register-notes">
                        <strong>What happens after registration</strong>
                        <p>
                            After creating your account, you will verify your email and then continue to the next ZagChain steps
                            from inside your dashboard.
                        </p>
                    </div>

                    <div class="register-actions">
                        <a href="{{ route('login') }}" class="register-login-link">Already registered? Sign in</a>
                        <button class="register-submit" type="submit">Create account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
