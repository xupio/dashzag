<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify Your ZagChain Email</title>
</head>
<body style="margin:0; padding:0; background-color:#eef3fb; font-family:Arial, sans-serif; color:#172033;">
  <div style="margin:0; padding:32px 16px; background-color:#eef3fb;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:680px; margin:0 auto; border-collapse:collapse;">
      <tr>
        <td style="padding:0;">
          <div style="background:linear-gradient(135deg, #081122 0%, #10264a 58%, #2563eb 100%); border-radius:24px 24px 0 0; padding:34px 40px 26px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;">
              <tr>
                <td align="left" style="vertical-align:middle;">
                  <img src="{{ asset('branding/zagchain-logo.png') }}" alt="ZagChain" style="display:block; width:180px; max-width:100%; height:auto;">
                </td>
                <td align="right" style="vertical-align:middle;">
                  <span style="display:inline-block; padding:8px 14px; border:1px solid rgba(255,255,255,0.22); border-radius:999px; color:#dbe8ff; font-size:12px; letter-spacing:0.08em; text-transform:uppercase;">
                    Account Activation
                  </span>
                </td>
              </tr>
            </table>

            <div style="padding-top:28px;">
              <div style="color:#8ec5ff; font-size:13px; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; margin-bottom:12px;">
                Verify your email
              </div>
              <h1 style="margin:0; color:#ffffff; font-size:34px; line-height:1.2; font-weight:700;">
                Confirm your email and activate your ZagChain account
              </h1>
              <p style="margin:16px 0 0; color:#d6e4ff; font-size:17px; line-height:1.7;">
                Hello {{ $notifiable->name ?? 'there' }}, you are one step away from entering your dashboard and continuing your ZagChain journey.
              </p>
            </div>
          </div>
        </td>
      </tr>

      <tr>
        <td style="padding:0;">
          <div style="background:#ffffff; border:1px solid #dbe4f0; border-top:none; border-radius:0 0 24px 24px; padding:38px 40px 40px; box-shadow:0 24px 60px rgba(15, 23, 42, 0.08);">
            <div style="background:#f8fbff; border:1px solid #d6e5ff; border-radius:18px; padding:22px 24px; margin-bottom:26px;">
              <div style="color:#31507f; font-size:13px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; margin-bottom:10px;">
                Why this matters
              </div>
              <p style="margin:0; color:#26344d; font-size:16px; line-height:1.75;">
                Email verification protects your access and completes the first activation step for your ZagChain account.
              </p>
            </div>

            @if ($inviterSummary)
              <div style="background:#fffaf0; border:1px solid #f4dec1; border-radius:16px; padding:18px 20px; margin-bottom:24px;">
                <div style="color:#8a5a14; font-size:14px; font-weight:700; margin-bottom:8px;">
                  Invitation status
                </div>
                <div style="color:#7c5b2a; font-size:14px; line-height:1.7;">
                  {{ $inviterSummary }}
                </div>
              </div>
            @endif

            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse; margin-bottom:28px;">
              <tr>
                <td style="padding:0 0 14px; color:#172033; font-size:16px; line-height:1.7;">
                  Click the button below to verify your email address and unlock access to the next ZagChain steps.
                </td>
              </tr>
              <tr>
                <td style="padding:0;">
                  <a href="{{ $verificationUrl }}" style="display:inline-block; background:#2563eb; color:#ffffff; text-decoration:none; padding:16px 28px; border-radius:12px; font-size:16px; font-weight:700;">
                    Verify ZagChain Email
                  </a>
                </td>
              </tr>
            </table>

            <div style="margin-bottom:24px;">
              <div style="color:#172033; font-size:20px; font-weight:700; margin-bottom:12px;">
                What happens next
              </div>
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;">
                <tr>
                  <td style="padding:0 0 10px; color:#334155; font-size:15px; line-height:1.7;">1. Verify your email address.</td>
                </tr>
                <tr>
                  <td style="padding:0 0 10px; color:#334155; font-size:15px; line-height:1.7;">2. Return to ZagChain and continue to your dashboard.</td>
                </tr>
                <tr>
                  <td style="padding:0; color:#334155; font-size:15px; line-height:1.7;">3. Review the platform and continue at your own pace.</td>
                </tr>
              </table>
            </div>

            <div style="background:#fffaf0; border:1px solid #f4dec1; border-radius:16px; padding:18px 20px; margin-bottom:22px;">
              <div style="color:#8a5a14; font-size:14px; font-weight:700; margin-bottom:8px;">
                Button not working?
              </div>
              <div style="color:#7c5b2a; font-size:14px; line-height:1.7; word-break:break-word;">
                Copy and paste this link into your browser:<br>
                <a href="{{ $verificationUrl }}" style="color:#2563eb; text-decoration:none;">{{ $verificationUrl }}</a>
              </div>
            </div>

            <div style="padding-top:8px; border-top:1px solid #e8eef6; color:#7b8798; font-size:13px; line-height:1.7;">
              If you did not create an account, no further action is required.<br>
              ZagChain
            </div>
          </div>
        </td>
      </tr>
    </table>
  </div>
</body>
</html>
