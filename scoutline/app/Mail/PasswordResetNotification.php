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

    public string $userName;
    public string $resetUrl;

    public function __construct(string $userName, string $resetUrl)
    {
        $this->userName = $userName;
        $this->resetUrl = $resetUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Scoutline — Reset Your Password',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'password_reset_email', // Points directly to resources/views/password_reset_email.blade.php
        );
    }
}