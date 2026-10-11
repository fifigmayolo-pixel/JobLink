<?php

namespace App\Jobs;

use App\Models\JobPosting;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyNewJobPosting implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    // Takes the id, not the model: if the posting is deleted before the worker
    // gets to it, find() just returns null and we skip quietly.
    public function __construct(public int $jobPostingId) {}

    public function handle(): void
    {
        $jobPosting = JobPosting::with('company')->find($this->jobPostingId);

        if (! $jobPosting) {
            return;
        }

        $companyName = $jobPosting->company?->company_name ?? 'A company';
        $message = "{$companyName} posted a new job: {$jobPosting->title}.";
        $now = now();

        // Students and alumni only, and only accounts that have activated —
        // pre-registered rosters shouldn't open to a pile of old job alerts.
        // Soft-deleted users are excluded automatically.
        User::query()
            ->whereIn('role', ['student', 'alumni'])
            ->whereNotNull('activated_at')
            ->chunkById(500, function ($users) use ($jobPosting, $message, $now) {
                Notification::insert(
                    $users->map(fn ($user) => [
                        'recipient_id' => $user->id,
                        'type' => 'new_job_posting',
                        'title' => 'New Job Posting',
                        'message' => $message,
                        'related_job_posting_id' => $jobPosting->id,
                        'read_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );
            });
    }
}