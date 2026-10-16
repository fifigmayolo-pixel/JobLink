<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationApprovedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 60;

    public function __construct(
        public string $fullName,
        public string $studentId,
        public string $applicantType,
        public string $activateUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[JobLink] Your registration was approved');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.registration-approved');
    }
}