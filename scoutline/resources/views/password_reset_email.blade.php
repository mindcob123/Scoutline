<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        {!! file_get_contents(public_path('css/email.css')) !!}
    </style>
</head>
<body class="email-body">
    <h2>Hello, {{ $userName }}</h2>
    <p>We received a request to reset the password for your Scoutline operator account.</p>
    
    <div style="margin: 30px 0; text-align: center;">
        <a href="{{ $resetUrl }}" class="btn-primary" style="background-color: #ef4444;">Reset Password</a>
    </div>

    <p>This secure link is strictly valid for the next 15 minutes. If you did not request this modification, you can safely ignore this communication.</p>
    
    <hr style="border: none; border-top: 1px solid rgba(255,255,255,0.1); margin: 20px 0;">
    <p style="word-break: break-all; color: #666; font-size: 12px;">{{ $resetUrl }}</p>
    <br>
    <p>Best regards,<br>The Scoutline Team</p>
</body>
</html>