<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AnnouncementMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 60;

    public function __construct(
        public string $announcementTitle,
        public string $announcementMessage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[JobLink] ' . $this->announcementTitle);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.announcement');
    }
}