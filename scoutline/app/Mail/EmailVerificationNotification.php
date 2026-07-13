<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationNotification extends Mailable
{
    use Queueable, SerializesModels;

    public string $verificationUrl;
    public string $userName;

    public function __construct(string $userName, string $verificationUrl)    #takes the user's name and the verification URL as parameters and assigns them to the class properties
    {
        $this->userName = $userName;
        $this->verificationUrl = $verificationUrl;
    }

    public function envelope(): Envelope                                     #returns an instance of the Envelope class, which defines the subject of the email
    {
        return new Envelope(
            subject: 'Welcome to Scoutline! Confirm Your Email Address',
        );
    }

    public function content(): Content                                        #returns an instance of the Content class, which defines the view that will be used to render the email
    {
        return new Content(
            view: 'emailverification',
        );
    }
}