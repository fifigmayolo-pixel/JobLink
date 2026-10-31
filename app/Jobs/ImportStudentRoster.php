<?php

namespace App\Jobs;

use App\Imports\StudentRosterImport;
use App\Models\FieldChangeLog;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ImportStudentRoster implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(
        public string $filePath,
        public string $role,
        public int $initiatedBy,
    ) {}

    public function handle(): void
    {
        $import = new StudentRosterImport($this->role);
        Excel::import($import, $this->filePath, config('filesystems.default'));

        $failures = $import->failures();

        FieldChangeLog::create([
            'action' => 'roster_import',
            'changed_by' => $this->initiatedBy,
            'details' => 'Roster imported as '.ucfirst($this->role).' — '.(
                $failures->isEmpty() ? 'no rows skipped.' : $failures->count().' row(s) skipped.'
            ),
        ]);

        Notification::create([
            'recipient_id' => $this->initiatedBy,
            'type' => 'roster_import_complete',
            'title' => 'Roster Import Finished',
            'message' => $failures->isEmpty()
                ? 'Roster import finished — every row was added successfully.'
                : $failures->count().' row(s) skipped: '.$failures->map(
                    fn ($f) => "row {$f->row()}: ".implode(', ', $f->errors())
                )->implode('; '),
        ]);

        Storage::delete($this->filePath);
    }
}