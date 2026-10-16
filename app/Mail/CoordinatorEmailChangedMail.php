<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CoordinatorEmailChangedMail extends Mailable
{
    public function __construct(
        public string $fullName,
        public string $oldEmail,
        public string $newEmail,
        public string $audience, // 'old' = alert to the previous address, 'new' = notice to the new one
        public string $loginUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->audience === 'old'
                ? '[JobLink] Your login email was changed'
                : '[JobLink] This is now your login email'
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.coordinator-email-changed');
    }
}