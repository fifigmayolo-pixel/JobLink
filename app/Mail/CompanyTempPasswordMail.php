<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CompanyTempPasswordMail extends Mailable
{
    public function __construct(
        public string $companyName,
        public string $loginEmail,
        public string $tempPassword,
        public string $loginUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[JobLink] Your temporary password');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.company-temp-password');
    }
}