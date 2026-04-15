<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Invitation</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; color: #111827; margin: 0; padding: 24px; }
        .card { max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 12px; padding: 32px; box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08); }
        .btn { display: inline-block; background: #2563eb; color: #ffffff; padding: 12px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; }
        .footer { color: #6b7280; font-size: 14px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>You're Invited!</h2>

        <p>{{ $inviterName }} has invited you to join <strong>{{ $organizationName }}</strong> as a <strong>{{ $role }}</strong>.</p>

        <p>
            <a href="{{ $acceptUrl }}" class="btn">Accept Invitation</a>
        </p>

        <p class="footer">
            This invitation expires on {{ $expiresAt }}.<br>
            If you did not expect this invitation, you can safely ignore this email.
        </p>
    </div>
</body>
</html>