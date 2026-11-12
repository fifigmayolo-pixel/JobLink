<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileEducation extends Model
{
    // Set explicitly: "education" is uncountable, so don't rely on guessing.
    protected $table = 'profile_education';

    /**
     * Other schooling only. The PSU program and year come from the locked
     * profile fields, so they are never typed in here.
     */
    public const LEVELS = [
        'Senior High School',
        'Vocational / Technical',
        'College (other school)',
        'Graduate Studies',
        'Other',
    ];

    protected $fillable = [
        'school_name',
        'level',
        'degree_program',
        'start_year',
        'end_year',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'start_year' => 'integer',
            'end_year' => 'integer',
        ];
    }

    public function profile()
    {
        return $this->belongsTo(StudentAlumniProfile::class, 'profile_id');
    }
}