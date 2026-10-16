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
        'resume_path',
        'date_applied',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date_applied' => 'datetime',
        ];
    }

    public function jobPosting()
    {
        return $this->belongsTo(JobPosting::class);
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

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'related_application_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
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