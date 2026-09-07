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

    public function applicant()
    {
        return $this->belongsTo(User::class, 'applicant_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'related_application_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}