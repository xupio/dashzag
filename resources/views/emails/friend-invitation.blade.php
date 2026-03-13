<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Friend Invitation</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f5f7fb; color:#1f2937; margin:0; padding:24px;">
  <div style="max-width:640px; margin:0 auto; background:#ffffff; border-radius:12px; padding:32px; border:1px solid #e5e7eb;">
    <h1 style="margin-top:0; font-size:24px;">You have been invited</h1>
    <p style="font-size:16px; line-height:1.6;">
      <strong>{{ $inviter->name }}</strong> invited you to join us on this website.
    </p>
    <p style="font-size:16px; line-height:1.6;">
      Click the button below to confirm your invitation. Once confirmed, your status will be updated for your friend.
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
  </div>
</body>
</html>

