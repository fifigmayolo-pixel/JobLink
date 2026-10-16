<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CoordinatorAccountCreatedMail extends Mailable
{
    public function __construct(
        public string $fullName,
        public string $loginEmail,
        public string $tempPassword,
        public string $loginUrl,
        public string $createdBy,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[JobLink] Your Job Coordinator account');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.coordinator-account-created');
    }
}