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
        'tracer_study_update' => 'Tracer Study Link Updated',
    ];

    public const JOB_POSTING_ACTIONS = [
        'job_posting_created',
        'job_posting_update',
        'job_posting_deleted',
        'job_posting_approved',
        'job_posting_rejected',
        'job_posting_resubmitted',
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