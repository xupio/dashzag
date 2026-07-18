<x-guest-layout>
    @push('styles')
        <style>
            .forgot-shell {
                display: grid;
                gap: 1.4rem;
            }

            .forgot-hero {
                padding: 1.35rem 1.4rem;
                border-radius: 1.3rem;
                background: linear-gradient(135deg, #081122 0%, #10264a 58%, #2563eb 100%);
                color: #ffffff;
                box-shadow: 0 20px 45px rgba(15, 23, 42, 0.16);
            }

            .forgot-eyebrow {
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

            .forgot-hero h1 {
                margin: 0 0 .7rem;
                font-size: 1.95rem;
                line-height: 1.15;
                color: #ffffff;
            }

            .forgot-hero p {
                margin: 0;
                color: #d7e6ff;
                font-size: .98rem;
                line-height: 1.75;
            }

            .forgot-panel {
                border: 1px solid #d9e4f2;
                border-radius: 1.3rem;
                background: #ffffff;
                overflow: hidden;
            }

            .forgot-panel__body {
                padding: 1.45rem;
            }

            .forgot-summary {
                margin-bottom: 1.1rem;
                padding: 1rem 1.1rem;
                border: 1px solid #dbe8ff;
                border-radius: 1rem;
                background: linear-gradient(180deg, #f8fbff 0%, #eef5ff 100%);
            }

            .forgot-summary__title {
                margin-bottom: .45rem;
                color: #31507f;
                font-size: .76rem;
                font-weight: 700;
                letter-spacing: .08em;
                text-transform: uppercase;
            }

            .forgot-summary p {
                margin: 0;
                color: #334155;
                font-size: .94rem;
                line-height: 1.75;
            }

            .forgot-success {
                margin-bottom: 1rem;
                padding: .95rem 1.05rem;
                border: 1px solid #b9e6c9;
                border-radius: 1rem;
                background: #edf9f1;
                color: #177245;
                font-size: .92rem;
                line-height: 1.7;
            }

            .forgot-shell .label {
                display: inline-block;
                margin-bottom: .45rem;
                color: #1f2d44;
                font-size: .88rem;
                font-weight: 700;
            }

            .forgot-shell .form-control {
                min-height: 50px;
                border-radius: .95rem;
                border: 1px solid #d3deeb;
                padding: .8rem .95rem;
                box-shadow: none;
            }

            .forgot-shell .form-control:focus {
                border-color: #2563eb;
                box-shadow: 0 0 0 0.18rem rgba(37, 99, 235, 0.14);
            }

            .forgot-note {
                margin-top: 1.2rem;
                padding: 1rem 1.1rem;
                border: 1px solid #f2dfbf;
                border-radius: 1rem;
                background: #fffaf0;
            }

            .forgot-note strong {
                display: block;
                margin-bottom: .4rem;
                color: #8a5a14;
                font-size: .92rem;
            }

            .forgot-note p {
                margin: 0;
                color: #7c5b2a;
                font-size: .9rem;
                line-height: 1.7;
            }

            .forgot-actions {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: .9rem;
                margin-top: 1.25rem;
                flex-wrap: wrap;
            }

            .forgot-link {
                color: #23406a;
                font-weight: 600;
                text-decoration: none;
            }

            .forgot-link:hover {
                text-decoration: underline;
            }

            .forgot-submit {
                min-width: 230px;
                border: none;
                border-radius: .95rem;
                padding: .9rem 1.25rem;
                background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
                color: #ffffff;
                font-weight: 700;
            }

            .forgot-submit:hover {
                filter: brightness(1.02);
            }

            @media (max-width: 767px) {
                .forgot-hero h1 {
                    font-size: 1.7rem;
                }

                .forgot-panel__body,
                .forgot-hero {
                    padding: 1.1rem;
                }

                .forgot-actions {
                    align-items: stretch;
                }

                .forgot-submit {
                    width: 100%;
                }
            }
        </style>
    @endpush

    <div class="forgot-shell">
        <div class="forgot-hero">
            <div class="forgot-eyebrow">Password reset</div>
            <h1>Reset your password and get back into ZagChain</h1>
            <p>
                Enter your account email and we will send you a secure password reset link so you can continue smoothly.
            </p>
        </div>

        <div class="forgot-panel">
            <div class="forgot-panel__body">
                <div class="forgot-summary">
                    <div class="forgot-summary__title">Reset overview</div>
                    <p>
                        Use this step when you cannot access your account password. We will send a reset link to the email address
                        connected to your ZagChain account.
                    </p>
                </div>

                @if (session('status'))
                    <div class="forgot-success">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf

                    <div class="form-group">
                        <label class="label" for="email">Email address</label>
                        <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="Enter your email address">
                        @error('email')<div class="error-text">{{ $message }}</div>@enderror
                    </div>

                    <div class="forgot-note">
                        <strong>What happens next</strong>
                        <p>
                            After you submit your email, check your inbox for the reset link and follow the instructions to set a new password.
                        </p>
                    </div>

                    <div class="forgot-actions">
                        <a href="{{ route('login') }}" class="forgot-link">Back to sign in</a>
                        <button class="forgot-submit" type="submit">Email password reset link</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
