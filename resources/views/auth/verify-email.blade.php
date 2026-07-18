<x-guest-layout>
    @push('styles')
        <style>
            .verify-shell {
                display: grid;
                gap: 1.4rem;
            }

            .verify-hero {
                padding: 1.35rem 1.4rem;
                border-radius: 1.3rem;
                background: linear-gradient(135deg, #081122 0%, #10264a 58%, #2563eb 100%);
                color: #ffffff;
                box-shadow: 0 20px 45px rgba(15, 23, 42, 0.16);
            }

            .verify-eyebrow {
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

            .verify-hero h1 {
                margin: 0 0 .7rem;
                font-size: 1.95rem;
                line-height: 1.15;
                color: #ffffff;
            }

            .verify-hero p {
                margin: 0;
                color: #d7e6ff;
                font-size: .98rem;
                line-height: 1.75;
            }

            .verify-panel {
                border: 1px solid #d9e4f2;
                border-radius: 1.3rem;
                background: #ffffff;
                overflow: hidden;
            }

            .verify-panel__body {
                padding: 1.45rem;
            }

            .verify-summary {
                margin-bottom: 1.1rem;
                padding: 1rem 1.1rem;
                border: 1px solid #dbe8ff;
                border-radius: 1rem;
                background: linear-gradient(180deg, #f8fbff 0%, #eef5ff 100%);
            }

            .verify-summary__title {
                margin-bottom: .45rem;
                color: #31507f;
                font-size: .76rem;
                font-weight: 700;
                letter-spacing: .08em;
                text-transform: uppercase;
            }

            .verify-summary p {
                margin: 0;
                color: #334155;
                font-size: .94rem;
                line-height: 1.75;
            }

            .verify-note {
                margin-bottom: 1rem;
                padding: 1rem 1.1rem;
                border: 1px solid #d8e6ff;
                border-radius: 1rem;
                background: #f8fbff;
                color: #334155;
                font-size: .93rem;
                line-height: 1.75;
            }

            .verify-note strong {
                display: block;
                margin-bottom: .4rem;
                color: #1f2d44;
            }

            .verify-success {
                margin-bottom: 1rem;
                padding: .95rem 1.05rem;
                border: 1px solid #b9e6c9;
                border-radius: 1rem;
                background: #edf9f1;
                color: #177245;
                font-size: .92rem;
                line-height: 1.7;
            }

            .verify-actions {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: .9rem;
                flex-wrap: wrap;
                margin-top: 1.15rem;
            }

            .verify-resend {
                border: none;
                border-radius: .95rem;
                padding: .9rem 1.25rem;
                background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
                color: #ffffff;
                font-weight: 700;
                min-width: 220px;
            }

            .verify-resend:hover {
                filter: brightness(1.02);
            }

            .verify-logout {
                border: 1px solid #d3deeb;
                border-radius: .95rem;
                padding: .9rem 1.25rem;
                background: #ffffff;
                color: #23406a;
                font-weight: 700;
                min-width: 160px;
            }

            .verify-steps {
                margin-top: 1.2rem;
                padding: 1rem 1.1rem;
                border: 1px solid #f2dfbf;
                border-radius: 1rem;
                background: #fffaf0;
            }

            .verify-steps strong {
                display: block;
                margin-bottom: .45rem;
                color: #8a5a14;
                font-size: .92rem;
            }

            .verify-steps p {
                margin: 0 0 .35rem;
                color: #7c5b2a;
                font-size: .9rem;
                line-height: 1.7;
            }

            .verify-steps p:last-child {
                margin-bottom: 0;
            }

            @media (max-width: 767px) {
                .verify-hero h1 {
                    font-size: 1.7rem;
                }

                .verify-panel__body,
                .verify-hero {
                    padding: 1.1rem;
                }

                .verify-actions {
                    align-items: stretch;
                }

                .verify-resend,
                .verify-logout {
                    width: 100%;
                }
            }
        </style>
    @endpush

    <div class="verify-shell">
        <div class="verify-hero">
            <div class="verify-eyebrow">Email verification</div>
            <h1>Confirm your email to activate your ZagChain account</h1>
            <p>
                You are almost inside. Open the verification email we sent and confirm your address to continue
                into your dashboard and the next ZagChain steps.
            </p>
        </div>

        <div class="verify-panel">
            <div class="verify-panel__body">
                <div class="verify-summary">
                    <div class="verify-summary__title">Verification overview</div>
                    <p>
                        Email verification protects your access and completes the first activation step for your account.
                        After verification, you can continue normally inside the platform.
                    </p>
                </div>

                <div class="verify-note">
                    <strong>What to do now</strong>
                    Check your inbox and click the verification link we sent. If you do not see it, check your spam folder
                    or request a fresh verification email below.
                </div>

                @if (session('status') == 'verification-link-sent')
                    <div class="verify-success">
                        A new verification link has been sent to your email address successfully.
                    </div>
                @endif

                <div class="verify-steps">
                    <strong>Next steps</strong>
                    <p>1. Open the latest ZagChain verification email.</p>
                    <p>2. Click the verification link inside the email.</p>
                    <p>3. Return to your account and continue from the dashboard.</p>
                </div>

                <div class="verify-actions">
                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button class="verify-resend" type="submit">Resend verification email</button>
                    </form>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="verify-logout">Log out</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
