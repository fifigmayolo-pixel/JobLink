<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_posting_id',
        'applicant_id',
        'profile_id',
        'cv_snapshot',
        'date_applied',
        'status',
        'hidden_by_applicant_at',
    ];

    protected function casts(): array
    {
        return [
            'date_applied' => 'datetime',
            'hidden_by_applicant_at' => 'datetime',
            'cv_snapshot' => 'array',
        ];
    }

    /**
     * withTrashed(): a posting the company removed is only soft-deleted. Without
     * this, ->jobPosting is null for those applications and every page that
     * reads it (My Applications, the company Applicants page) crashes.
     */
    public function jobPosting()
    {
        return $this->belongsTo(JobPosting::class)->withTrashed();
    }

    /**
     * withTrashed(): a Student/Alumni removed from Records is only soft-deleted,
     * and their applications are kept. Without this, ->applicant is null for
     * them and every view that reads it crashes.
     */
    public function applicant()
    {
        return $this->belongsTo(User::class, 'applicant_id')->withTrashed();
    }

    /**
     * The live profile the application was made from. Null if the profile
     * was removed since; the cv_snapshot is what the company actually received.
     */
    public function profile()
    {
        return $this->belongsTo(StudentAlumniProfile::class, 'profile_id');
    }

    public function hasCvSnapshot(): bool
    {
        return ! empty($this->cv_snapshot);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'related_application_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Applications the applicant has not removed from their own list.
     */
    public function scopeVisibleToApplicant($query)
    {
        return $query->whereNull('hidden_by_applicant_at');
    }

    /**
     * "Active" tab: still being considered. Unknown statuses land here too.
     */
    public function scopeInProgress($query)
    {
        return $query->whereNotIn('status', ['rejected', 'withdrawn']);
    }

    /**
     * "Past" tab: the application has ended.
     */
    public function scopeFinished($query)
    {
        return $query->whereIn('status', ['rejected', 'withdrawn']);
    }

    /**
     * True once the applicant has withdrawn it.
     */
    public function isWithdrawn(): bool
    {
        return $this->status === 'withdrawn';
    }

    /**
     * True once the company has rejected it.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Display name for the applicant, safe even if the account is gone.
     */
    public function applicantName(): string
    {
        return $this->applicant?->profile?->full_name
            ?? $this->applicant?->student_id_number
            ?? 'Unknown applicant';
    }

    /**
     * True if the applicant's account was removed (soft-deleted) or is missing.
     */
    public function applicantRemoved(): bool
    {
        return ! $this->applicant || $this->applicant->trashed();
    }
}