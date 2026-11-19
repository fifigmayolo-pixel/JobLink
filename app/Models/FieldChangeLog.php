<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One table, many action types, distinguished by `action`. user_id,
 * field_name, and job_posting_id are nullable because not every action
 * has an affected user, a field, or a posting.
 */
class FieldChangeLog extends Model
{
    use HasFactory;

    public const ACTION_LABELS = [
        'field_correction' => 'Field Correction',
        'record_deletion' => 'Record Deletion',
        'student_alumni_added' => 'Student/Alumni Added',
        'roster_import' => 'Roster Import',
        'company_account_created' => 'Company Account Created',
        // Legacy: the PESO role was removed, but old log rows still carry this action.
        'peso_account_created' => 'PESO Account Created',
        'job_posting_created' => 'Job Posting Created',
        'job_posting_update' => 'Job Posting Update',
        'job_posting_deleted' => 'Job Posting Deleted',
        'job_posting_approved' => 'Job Posting Approved',
        'job_posting_rejected' => 'Job Posting Rejected',
        'job_posting_resubmitted' => 'Job Posting Resubmitted',
        // Vacancy slots: JobLink closes a posting when every interview slot is taken and reopens it when one frees up.
        'job_posting_closed_full' => 'Job Posting Closed (Slots Full)',
        'job_posting_reopened_slots' => 'Job Posting Reopened (Slot Freed)',
        'tracer_study_update' => 'Tracer Study Link Updated',
        // Candidate search: who looked at whose profile, and the student's consent trail.
        'candidate_profile_viewed' => 'Candidate Profile Viewed',
        'searchable_enabled' => 'Searchable Profile Turned On',
        'searchable_disabled' => 'Searchable Profile Turned Off',
        // Required clearances: who uploaded, replaced or removed one, and each company opening one.
        // The entries hold the document type and validity date only, never the file.
        'clearance_uploaded' => 'Clearance Uploaded',
        'clearance_replaced' => 'Clearance Replaced',
        'clearance_removed' => 'Clearance Removed',
        'clearance_opened' => 'Clearance Opened by Company',
    ];

    public const JOB_POSTING_ACTIONS = [
        'job_posting_created',
        'job_posting_update',
        'job_posting_deleted',
        'job_posting_approved',
        'job_posting_rejected',
        'job_posting_resubmitted',
        'job_posting_closed_full',
        'job_posting_reopened_slots',
    ];

    protected $fillable = [
        'action',
        'user_id',
        'changed_by',
        'job_posting_id',
        'field_name',
        'old_value',
        'new_value',
        'reason',
        'details',
    ];

    public function actionLabel(): string
    {
        return self::ACTION_LABELS[$this->action] ?? $this->action;
    }

    public function concernsJobPosting(): bool
    {
        return in_array($this->action, self::JOB_POSTING_ACTIONS, true);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function jobPosting()
    {
        return $this->belongsTo(JobPosting::class);
    }
}
