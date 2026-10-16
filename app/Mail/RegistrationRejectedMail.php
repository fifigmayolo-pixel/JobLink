<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationRejectedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 60;

    public function __construct(
        public string $fullName,
        public ?string $reason,
        public string $registerUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[JobLink] Update on your registration request');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.registration-rejected');
    }
}