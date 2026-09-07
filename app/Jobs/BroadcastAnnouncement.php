<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
            'all' => User::pluck('id')->all(),
            default => [],
        };

        $sent = $notifications->announcement($this->title, $this->message, $recipientIds);

        Notification::create([
            'recipient_id' => $this->postedBy,
            'type' => 'announcement_sent',
            'title' => 'Announcement Sent',
            'message' => "\"{$this->title}\" reached {$sent} recipient(s).",
        ]);
    }
}