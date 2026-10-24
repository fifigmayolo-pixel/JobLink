<?php

namespace App\Jobs;

use App\Mail\JobTaggedMail;
use App\Models\JobPosting;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Fired when a Job Coordinator posts a job tagged to a specific company
 * (JobPostingController@store, the $isCoordinator branch, only when the
 * posting has a company_id). Notifies that company's own account — both
 * an in-app Notification and an email to their registered login address —
 * that the Coordinator posted on their behalf.
 *
 * Mirrors NotifyNewJobPosting: takes the id (not the model) so a posting
 * deleted before the worker runs is skipped quietly instead of failing.
 */
class NotifyTaggedCompany implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(public int $jobPostingId) {}

    public function handle(): void
    {
        $jobPosting = JobPosting::with('company.user')->find($this->jobPostingId);

        // Nothing to notify if the posting, its company, or that company's
        // login account no longer exist.
        if (! $jobPosting || ! $jobPosting->company?->user) {
            return;
        }

        $companyUser = $jobPosting->company->user;
        $now = now();

        Notification::create([
            'recipient_id' => $companyUser->id,
            'type' => 'job_posting_tagged',
            'title' => 'A Job Was Posted For You',
            'message' => "The Job Coordinator posted \"{$jobPosting->title}\" on your company's behalf.",
            'related_job_posting_id' => $jobPosting->id,
            'read_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Mail::to($companyUser->email)->send(new JobTaggedMail($jobPosting));
    }
}