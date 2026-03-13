<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invitation Confirmed</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f5f7fb; color: #1f2937; margin: 0; padding: 24px; }
    .card { max-width: 640px; margin: 48px auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 32px; text-align: center; }
    .button { display: inline-block; margin-top: 16px; padding: 12px 18px; border-radius: 8px; background: #2563eb; color: #fff; text-decoration: none; }
  </style>
</head>
<body>
  <div class="card">
    <h1>Invitation confirmed</h1>
    <p>{{ $friendInvitation->name }}, your invitation has been confirmed successfully.</p>
    <p>Your friend will now see your status as <strong>Verified</strong>.</p>
    <a href="{{ url('/') }}" class="button">Go to website</a>
  </div>
</body>
</html>

