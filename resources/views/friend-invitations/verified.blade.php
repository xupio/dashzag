<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invitation Confirmed | ZagChain</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
      background:
        radial-gradient(circle at top right, rgba(37, 99, 235, 0.18), transparent 28%),
        linear-gradient(180deg, #081122 0%, #0f1d33 36%, #eef3fb 36%, #eef3fb 100%);
      color: #172033;
      min-height: 100vh;
    }

    .shell {
      max-width: 920px;
      margin: 0 auto;
      padding: 56px 20px 80px;
    }

    .hero {
      padding: 8px 0 32px;
      color: #ffffff;
    }

    .badge {
      display: inline-block;
      margin-bottom: 16px;
      padding: 8px 14px;
      border: 1px solid rgba(255, 255, 255, 0.22);
      border-radius: 999px;
      color: #dbe8ff;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    .logo {
      display: block;
      width: 200px;
      max-width: 100%;
      height: auto;
      margin-bottom: 28px;
    }

    .hero h1 {
      margin: 0 0 16px;
      font-size: 42px;
      line-height: 1.15;
    }

    .hero p {
      max-width: 720px;
      margin: 0;
      color: #d6e4ff;
      font-size: 18px;
      line-height: 1.75;
    }

    .card {
      margin-top: 18px;
      background: #ffffff;
      border: 1px solid #dbe4f0;
      border-radius: 26px;
      box-shadow: 0 24px 60px rgba(15, 23, 42, 0.10);
      overflow: hidden;
    }

    .card-inner {
      padding: 36px;
    }

    .grid {
      display: grid;
      grid-template-columns: 1.3fr 0.9fr;
      gap: 24px;
      align-items: start;
    }

    .success-box {
      background: linear-gradient(180deg, #f8fbff 0%, #eef5ff 100%);
      border: 1px solid #d8e6ff;
      border-radius: 22px;
      padding: 28px;
    }

    .eyebrow {
      margin-bottom: 12px;
      color: #31507f;
      font-size: 13px;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    .success-box h2 {
      margin: 0 0 14px;
      font-size: 30px;
      line-height: 1.2;
      color: #172033;
    }

    .success-box p {
      margin: 0 0 14px;
      font-size: 16px;
      line-height: 1.75;
      color: #334155;
    }

    .status-pill {
      display: inline-block;
      margin-top: 4px;
      padding: 10px 16px;
      border-radius: 999px;
      background: #e7f8ee;
      color: #127a43;
      font-size: 14px;
      font-weight: 700;
    }

    .details {
      background: #ffffff;
      border: 1px solid #e5edf6;
      border-radius: 22px;
      padding: 24px;
    }

    .details h3 {
      margin: 0 0 16px;
      font-size: 20px;
      color: #172033;
    }

    .detail-row {
      padding: 12px 0;
      border-bottom: 1px solid #edf2f7;
    }

    .detail-row:last-child {
      border-bottom: none;
      padding-bottom: 0;
    }

    .detail-label {
      display: block;
      margin-bottom: 6px;
      color: #6b7a90;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 0.07em;
      text-transform: uppercase;
    }

    .detail-value {
      color: #172033;
      font-size: 16px;
      font-weight: 700;
      word-break: break-word;
    }

    .steps {
      margin-top: 28px;
      padding: 24px 26px;
      background: #fffaf0;
      border: 1px solid #f4dec1;
      border-radius: 20px;
    }

    .steps h3 {
      margin: 0 0 14px;
      color: #8a5a14;
      font-size: 18px;
    }

    .steps p {
      margin: 0 0 8px;
      color: #7c5b2a;
      font-size: 15px;
      line-height: 1.7;
    }

    .actions {
      display: flex;
      flex-wrap: wrap;
      gap: 14px;
      margin-top: 28px;
    }

    .button-primary,
    .button-secondary {
      display: inline-block;
      padding: 15px 24px;
      border-radius: 12px;
      text-decoration: none;
      font-size: 15px;
      font-weight: 700;
    }

    .button-primary {
      background: #2563eb;
      color: #ffffff;
    }

    .button-secondary {
      border: 1px solid #cfdced;
      color: #23406a;
      background: #ffffff;
    }

    .footer {
      padding: 0 36px 34px;
      color: #7b8798;
      font-size: 13px;
      line-height: 1.7;
    }

    @media (max-width: 760px) {
      .shell {
        padding: 28px 16px 48px;
      }

      .hero h1 {
        font-size: 34px;
      }

      .grid {
        grid-template-columns: 1fr;
      }

      .card-inner,
      .footer {
        padding-left: 22px;
        padding-right: 22px;
      }
    }
  </style>
</head>
<body>
  <div class="shell">
    <div class="hero">
      <img src="{{ asset('branding/zagchain-logo.png') }}" alt="ZagChain" class="logo">
      <div class="badge">Invitation Verified</div>
      <h1>Your invitation has been confirmed successfully</h1>
      <p>
        {{ $friendInvitation->name }}, you are now marked as a verified invited friend in ZagChain.
        This confirmation helps connect your journey to the person who invited you.
      </p>
    </div>

    <div class="card">
      <div class="card-inner">
        <div class="grid">
          <div>
            <div class="success-box">
              <div class="eyebrow">Confirmation complete</div>
              <h2>Welcome to the next step</h2>
              <p>
                Your inviter will now see your status as verified. You can continue exploring ZagChain,
                understand how it works, and decide when you want to register.
              </p>
              <p>
                This page only confirms the invitation. It does not force any registration or payment step.
              </p>
              <div class="status-pill">Verified invitation status</div>
            </div>

            <div class="steps">
              <h3>What you can do next</h3>
              <p>1. Review how ZagChain works before creating an account.</p>
              <p>2. Register when you are ready to continue.</p>
              <p>3. Return later if you only wanted to confirm the invitation now.</p>
            </div>

            <div class="actions">
              <a href="{{ route('marketing.how-it-works') }}" class="button-primary">See How It Works</a>
              <a href="{{ route('register') }}" class="button-secondary">Create Account</a>
            </div>
          </div>

          <div class="details">
            <h3>Invitation details</h3>

            <div class="detail-row">
              <span class="detail-label">Invited friend</span>
              <span class="detail-value">{{ $friendInvitation->name }}</span>
            </div>

            <div class="detail-row">
              <span class="detail-label">Email</span>
              <span class="detail-value">{{ $friendInvitation->email }}</span>
            </div>

            @if ($friendInvitation->country)
              <div class="detail-row">
                <span class="detail-label">Country</span>
                <span class="detail-value">{{ $friendInvitation->country }}</span>
              </div>
            @endif

            @if ($friendInvitation->verified_at)
              <div class="detail-row">
                <span class="detail-label">Verified at</span>
                <span class="detail-value">{{ $friendInvitation->verified_at->format('M d, Y h:i A') }}</span>
              </div>
            @endif
          </div>
        </div>
      </div>

      <div class="footer">
        Invitation confirmation page for ZagChain.
      </div>
    </div>
  </div>
</body>
</html>
