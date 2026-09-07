<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\StudentAlumniProfile;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ConvertStudentsToAlumni implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(
        public array $profileIds,
        public string $yearGraduated,
        public ?int $initiatedBy = null,
    ) {}

    public function handle(): void
    {
        $converted = 0;

        StudentAlumniProfile::query()
            ->with('user')
            ->whereIn('id', $this->profileIds)
            ->where('status', 'student')
            ->chunkById(100, function ($profiles) use (&$converted) {
                foreach ($profiles as $profile) {
                    $profile->update([
                        'status' => 'alumni',
                        'year_graduated' => $this->yearGraduated,
                        'year_level' => null,
                        'section' => null,
                    ]);

                    $profile->user->update(['role' => 'alumni']);

                    $converted++;
                }
            });

        if ($this->initiatedBy) {
            Notification::create([
                'recipient_id' => $this->initiatedBy,
                'type' => 'bulk_conversion_complete',
                'title' => 'Bulk Conversion Finished',
                'message' => "{$converted} student(s) converted to alumni (graduation year {$this->yearGraduated}).",
            ]);
        }
    }
}