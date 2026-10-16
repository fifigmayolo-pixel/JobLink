<?php

namespace App\Services;

use App\Mail\ApplicantNotificationMail;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function applicationStatusChanged(JobApplication $application): Notification
    {
        $messages = [
            'interview' => 'You have been invited for an interview for '.$application->jobPosting->title.'.',
            'rejected' => 'Your application for '.$application->jobPosting->title.' was not selected this time.',
        ];

        $notification = Notification::create([
            'recipient_id' => $application->applicant_id,
            'type' => $application->status === 'interview' ? 'interview_invitation' : 'rejection_notice',
            'title' => $application->status === 'interview' ? 'Interview Invitation' : 'Application Update',
            'message' => $messages[$application->status] ?? 'Your application status was updated.',
            'related_application_id' => $application->id,
        ]);

        $this->emailApplicant($application->applicant, $notification->title, $notification->message, $application);

        return $notification;
    }

    public function customMessage(User $recipient, string $title, string $message, ?JobApplication $application = null): Notification
    {
        $notification = Notification::create([
            'recipient_id' => $recipient->id,
            'type' => 'custom_company_message',
            'title' => $title,
            'message' => $message,
            'related_application_id' => $application?->id,
        ]);

        $this->emailApplicant($recipient, $title, $message, $application);

        return $notification;
    }

    /**
     * Tells the posting's owner whether the Job Coordinator approved or
     * rejected it. A rejection carries the Coordinator's reason.
     */
    public function jobPostingReviewed(JobPosting $jobPosting, User $recipient, bool $approved, ?string $reason = null): Notification
    {
        return Notification::create([
            'recipient_id' => $recipient->id,
            'type' => $approved ? 'job_posting_approved' : 'job_posting_rejected',
            'title' => $approved ? 'Job Posting Approved' : 'Job Posting Rejected',
            'message' => $approved
                ? "Your job posting \"{$jobPosting->title}\" was approved and is now visible to students and alumni."
                : "Your job posting \"{$jobPosting->title}\" was not approved. Reason: {$reason}",
            'related_job_posting_id' => $jobPosting->id,
        ]);
    }

    /**
     * Bulk-inserts one notification per recipient — used for Coordinator
     * announcements. Raw insert() in chunks rather than looping Eloquent
     * create(), since this can run against hundreds of users at once.
     */
    /**
     * $type defaults to 'announcement' for the existing Coordinator
     * announcement feature. TracerStudyLinkController passes 'tracer_study'
     * so those notifications get a distinct icon in the inbox, without a
     * second bulk-insert method duplicating this one.
     */
    public function announcement(string $title, string $message, array $recipientIds, string $type = 'announcement'): int
    {
        $now = now();

        $rows = collect($recipientIds)->map(fn ($id) => [
            'recipient_id' => $id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'related_application_id' => null,
            'read_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $rows->chunk(500)->each(fn ($chunk) => Notification::insert($chunk->toArray()));

        return $rows->count();
    }

    /**
     * Also emails the applicant at their registered personal email. Skipped
     * quietly for removed (soft-deleted) accounts or accounts with no valid
     * email. A mail problem never breaks the in-app notification above.
     */
    private function emailApplicant(?User $recipient, string $title, string $message, ?JobApplication $application): void
    {
        if (! $recipient || $recipient->trashed()) {
            return;
        }

        $email = $recipient->profile?->personal_email;

        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $company = $application?->jobPosting?->company;

        try {
            Mail::to($email)->queue(new ApplicantNotificationMail(
                mailTitle: $title,
                mailMessage: $message,
                companyName: $company?->company_name,
                jobTitle: $application?->jobPosting?->title,
                replyToEmail: $company?->user?->email,
            ));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}