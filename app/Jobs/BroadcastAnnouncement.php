<?php

namespace App\Jobs;

use App\Mail\AnnouncementMail;
use App\Models\Notification;
use App\Models\StudentAlumniProfile;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class BroadcastAnnouncement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(
        public string $title,
        public string $message,
        public string $audience,
        public int $postedBy,
    ) {}

    public function handle(NotificationService $notifications): void
    {
        $recipientIds = match ($this->audience) {
            'students_alumni' => User::whereIn('role', ['student', 'alumni'])->pluck('id')->all(),
            'companies' => User::where('role', 'company')->pluck('id')->all(),
            'all' => User::pluck('id')->all(),
            default => [],
        };

        $sent = $notifications->announcement($this->title, $this->message, $recipientIds);

        $emailsQueued = $this->queueEmails();

        Notification::create([
            'recipient_id' => $this->postedBy,
            'type' => 'announcement_sent',
            'title' => 'Announcement Sent',
            'message' => "\"{$this->title}\" reached {$sent} recipient(s) in-app; {$emailsQueued} email(s) queued.",
        ]);
    }

    /**
     * Queue one email per recipient (staggered 1s apart to stay under Gmail's
     * send-rate limits). A failure here never blocks the in-app notifications.
     */
    private function queueEmails(): int
    {
        $count = 0;

        try {
            foreach ($this->emailAddresses() as $i => $email) {
                Mail::to($email)->later(
                    now()->addSeconds($i),
                    new AnnouncementMail($this->title, $this->message)
                );
                $count++;
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $count;
    }

    private function emailAddresses(): array
    {
        $emails = collect();

        // Students/alumni: their registered personal email (same one password recovery uses).
        if (in_array($this->audience, ['students_alumni', 'all'], true)) {
            $emails = $emails->merge(
                StudentAlumniProfile::whereHas('user', fn ($q) => $q->whereIn('role', ['student', 'alumni']))
                    ->whereNotNull('personal_email')
                    ->pluck('personal_email')
            );
        }

        // Companies/staff: their login email. Soft-deleted users are excluded automatically.
        $staffRoles = match ($this->audience) {
            'companies' => ['company'],
            'all' => ['company', 'peso', 'job_coordinator'],
            default => [],
        };

        if ($staffRoles) {
            $emails = $emails->merge(
                User::whereIn('role', $staffRoles)->whereNotNull('email')->pluck('email')
            );
        }

        return $emails
            ->map(fn ($e) => strtolower(trim($e)))
            ->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }
}