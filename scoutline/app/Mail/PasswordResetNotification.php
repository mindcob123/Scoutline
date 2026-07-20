<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetNotification extends Mailable
{
    use Queueable, SerializesModels;
    // Public Properties
    public string $userName;
    public string $resetUrl;

    // Constructor & Mail Configuration
    /**
     * Create a new message instance.
     *
     * @param string $userName   // Name of the user who requested password reset
     * @param string $resetUrl   // Secure temporary signed URL for password reset
     */
    public function __construct(string $userName, string $resetUrl)
    {
        $this->userName = $userName;
        $this->resetUrl = $resetUrl;
    }

    // Get the message envelope (subject, from, etc.).
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Scoutline — Reset Your Password',
        );
    }

    // Get the message content definition. Specifies which Blade view will be used for the email body.
    public function content(): Content
    {
        return new Content(
            view: 'password_reset_email', // Points directly to resources/views/password_reset_email.blade.php
        );
    }
}