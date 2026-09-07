<?php

namespace App\Models;

use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

#[ObservedBy(UserObserver::class)]
class User extends Authenticatable
{
    use HasFactory, HasRoles, SoftDeletes;

    protected $fillable = [
        'role',
        'student_id_number',
        'full_name',
        'email',
        'password',
        'must_change_password',
        'activated_at',
        'terms_accepted_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'must_change_password' => 'boolean',
            'activated_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function profile()
    {
        return $this->hasOne(StudentAlumniProfile::class);
    }

    public function company()
    {
        return $this->hasOne(Company::class);
    }

    public function companiesCreated()
    {
        return $this->hasMany(Company::class, 'created_by');
    }

    public function jobPostings()
    {
        return $this->hasMany(JobPosting::class, 'posted_by');
    }

    public function applications()
    {
        return $this->hasMany(JobApplication::class, 'applicant_id');
    }

    public function savedJobs()
    {
        return $this->hasMany(SavedJob::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'recipient_id');
    }

    public function tracerStudyLinksCreated()
    {
        return $this->hasMany(TracerStudyLink::class, 'created_by');
    }

    public function fieldChangesReceived()
    {
        return $this->hasMany(FieldChangeLog::class, 'user_id');
    }

    public function fieldChangesMade()
    {
        return $this->hasMany(FieldChangeLog::class, 'changed_by');
    }

    public function scopeStudents($query)
    {
        return $query->where('role', 'student');
    }

    public function scopeAlumni($query)
    {
        return $query->where('role', 'alumni');
    }
}