<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetCodeMail extends Mailable
{
    use SerializesModels;

    public function __construct(public string $code) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your JobLink password reset code',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.password-reset-code',
            with: ['code' => $this->code],
        );
    }
}