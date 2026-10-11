<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Read-only formatter — takes an already-fetched collection of
 * FieldChangeLog rows and turns them into spreadsheet rows. Never queries,
 * updates, or deletes anything itself, so exporting cannot alter the
 * underlying audit trail.
 */
class AuditLogExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private Collection $logs) {}

    public function collection()
    {
        return $this->logs;
    }

    public function headings(): array
    {
        return [
            'Date/Time', 'Action', 'Record / Job Posting',
            'Field', 'Old Value', 'New Value', 'Reason', 'Details', 'Performed By',
        ];
    }

    public function map($log): array
    {
        return [
            $log->created_at->format('Y-m-d H:i:s'),
            $log->actionLabel(),
            match (true) {
                $log->concernsJobPosting() => $log->jobPosting?->title ?? 'Deleted posting',
                $log->action === 'roster_import' => '—',
                default => $log->user?->full_name ?? $log->user?->email ?? 'Deleted user',
            },
            $log->field_name ?? '—',
            $log->old_value ?? '—',
            $log->new_value ?? '—',
            $log->reason ?? '—',
            $log->details ?? '—',
            $log->editor?->full_name ?? $log->editor?->email ?? 'Unknown',
        ];
    }
}