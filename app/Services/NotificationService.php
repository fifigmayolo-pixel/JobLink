<?php

namespace App\Services;

use App\Models\JobApplication;
use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    public function applicationStatusChanged(JobApplication $application): Notification
    {
        $messages = [
            'interview' => 'You have been invited for an interview for '.$application->jobPosting->title.'.',
            'rejected' => 'Your application for '.$application->jobPosting->title.' was not selected this time.',
        ];

        return Notification::create([
            'recipient_id' => $application->applicant_id,
            'type' => $application->status === 'interview' ? 'interview_invitation' : 'rejection_notice',
            'title' => $application->status === 'interview' ? 'Interview Invitation' : 'Application Update',
            'message' => $messages[$application->status] ?? 'Your application status was updated.',
            'related_application_id' => $application->id,
        ]);
    }

    public function customMessage(User $recipient, string $title, string $message, ?JobApplication $application = null): Notification
    {
        return Notification::create([
            'recipient_id' => $recipient->id,
            'type' => 'custom_company_message',
            'title' => $title,
            'message' => $message,
            'related_application_id' => $application?->id,
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
}