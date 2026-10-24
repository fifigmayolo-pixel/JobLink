<?php

namespace App\Mail;

use App\Models\JobPosting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to a company's registered Gmail when a Job Coordinator posts a job
 * tagged to them (see App\Jobs\NotifyTaggedCompany). Mirrors AnnouncementMail's
 * conventions (ShouldQueue, tries/backoff, Envelope + Content) so it goes out
 * through the same Gmail SMTP mailer already configured for announcements.
 */
class JobTaggedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 60;

    public function __construct(public JobPosting $jobPosting) {}

    public function envelope(): Envelope
    {
        $companyName = $this->jobPosting->company?->company_name ?? 'your company';

        return new Envelope(subject: "[JobLink] A job was posted for {$companyName}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.job-tagged', with: [
            'jobPosting' => $this->jobPosting,
        ]);
    }
}