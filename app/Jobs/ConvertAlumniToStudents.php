<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\StudentAlumniProfile;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ConvertAlumniToStudents implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(
        public array $profileIds,
        public string $yearLevel,
        public ?int $initiatedBy = null,
    ) {}

    public function handle(): void
    {
        $converted = 0;

        StudentAlumniProfile::query()
            ->whereIn('id', $this->profileIds)
            ->where('status', 'alumni')
            ->with('user')
            ->chunkById(100, function ($profiles) use (&$converted) {
                foreach ($profiles as $profile) {
                    $profile->update([
                        'status' => 'student',
                        'year_level' => $this->yearLevel,
                        'year_graduated' => null,
                    ]);

                    $profile->user->update(['role' => 'student']);

                    $converted++;
                }
            });

        if ($this->initiatedBy) {
            Notification::create([
                'recipient_id' => $this->initiatedBy,
                'type' => 'bulk_conversion_complete',
                'title' => 'Bulk Conversion Finished',
                'message' => "{$converted} alumni converted back to student (year level {$this->yearLevel}).",
            ]);
        }
    }
}