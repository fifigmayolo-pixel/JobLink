<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileExperience extends Model
{
    public const KINDS = [
        'work' => 'Work experience',
        'ojt' => 'On-the-job training',
        'volunteer' => 'Volunteer work',
    ];

    protected $fillable = [
        'kind',
        'position',
        'organization',
        'start_date',
        'end_date',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function profile()
    {
        return $this->belongsTo(StudentAlumniProfile::class, 'profile_id');
    }

    /**
     * No end date means the person is still there.
     */
    public function isCurrent(): bool
    {
        return $this->end_date === null;
    }
}