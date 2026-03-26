<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ZagChain Invitation</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f5f7fb; color:#1f2937; margin:0; padding:24px;">
  <div style="max-width:640px; margin:0 auto; background:#ffffff; border-radius:12px; padding:32px; border:1px solid #e5e7eb;">
    <div style="text-align:center; margin-bottom:24px;">
      <img src="{{ asset('branding/zagchain-logo.png') }}" alt="ZagChain" style="max-width:180px; width:100%; height:auto;">
    </div>
    <h1 style="margin-top:0; font-size:24px;">You have been invited</h1>
    <p style="font-size:16px; line-height:1.6;">
      <strong>{{ $inviter->name }}</strong> invited you to join us with ZagChain.
    </p>
    <p style="font-size:16px; line-height:1.6;">
      Click the button below to confirm your invitation. Once confirmed, your status will be updated and your ZagChain journey can begin.
    </p>
    <p style="margin:32px 0;">
      <a href="{{ $verificationUrl }}" style="display:inline-block; background:#2563eb; color:#ffffff; text-decoration:none; padding:14px 22px; border-radius:8px; font-weight:600;">Confirm Invitation</a>
    </p>
    <p style="font-size:14px; color:#6b7280; line-height:1.6;">
      Invited friend: {{ $friendInvitation->name }}<br>
      Email: {{ $friendInvitation->email }}
    </p>
    <p style="font-size:14px; color:#6b7280; line-height:1.6;">
      If the button does not work, copy and paste this link into your browser:<br>
      <a href="{{ $verificationUrl }}">{{ $verificationUrl }}</a>
    </p>
    <p style="font-size:13px; color:#94a3b8; line-height:1.6; margin-top:24px;">
      ZagChain
    </p>
  </div>
</body>
</html>

