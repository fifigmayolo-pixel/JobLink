<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicantNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 60;

    public function __construct(
        public string $mailTitle,
        public string $mailMessage,
        public ?string $companyName = null,
        public ?string $jobTitle = null,
        public ?string $replyToEmail = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[JobLink] ' . $this->mailTitle,
            replyTo: $this->replyToEmail
                ? [new Address($this->replyToEmail, $this->companyName)]
                : [],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.applicant-notification');
    }
}