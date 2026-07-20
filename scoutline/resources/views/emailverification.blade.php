<x-layout>
    <x-slot:styles>
        <link rel="stylesheet" href="{{ asset('css/global.css') }}">
        <link rel="stylesheet" href="{{ asset('css/email.css') }}">
    </x-slot:styles>
    <!-- EMAIL VERIFICATION -->
    <section class="email-body">

        <!-- Greeting -->
        <h2 class="email-heading">Hello, {{ $userName }}!</h2>
        
        <p>We're excited to have you on board! Please confirm your email address by clicking the link below:</p>

        <!-- Primary Verification Button -->
        <div style="margin: 30px 0; text-align: center;">
            <a href="{{ $verificationUrl }}" 
               style="background-color: #4F46E5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">
                Verify Email Address
            </a>
        </div>

        <!-- Fallback Link -->
        <p>If the button above doesn't work, copy and paste this absolute URL link into your web browser:</p>
        <p style="word-break: break-all; color: #666;">{{ $verificationUrl }}</p>
        
        <br>
        <p>Best regards,<br>The Scoutline Team</p>

    </section>
</x-layout>