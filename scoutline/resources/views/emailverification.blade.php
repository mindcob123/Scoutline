<!DOCTYPE html>
<html>
<head>
    <title>Confirm Your Email Address</title>
    <style>
        {!! file_get_contents(public_path('css/email.css')) !!}   /* Dynamically pull your clean CSS file during rendering */
    </style>
</head>
<body class="email-body">
    <h2 class="email-heading">Hello, {{ $userName }}!</h2>
    <p>We're excited to have you on board! Please confirm your email address by clicking the link below:</p>
    <a href="{{ $verificationUrl }}" class="btn btn-primary">Confirm Email Address</a>
    <div style="margin: 30px 0; text-align: center;">
        <a href="{{ $verificationUrl }}" style="background-color: #4F46E5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">
            Verify Email Address
        </a>
    </div>

    <p>If the button above doesn't work, copy and paste this absolute URL link into your web browser grid:</p>
    <p style="word-break: break-all; color: #666;">{{ $verificationUrl }}</p>
    
    <br>
    <p>Best regards,<br>The Scoutline Team</p>
</body>
</html>

