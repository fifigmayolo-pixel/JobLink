<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAlumniProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'profile_picture',
        'full_name',
        'date_of_birth',
        'age',
        'gender',
        'civil_status',
        'nationality',
        'course_program',
        'year_level',
        'section',
        'year_graduated',
        'personal_email',
        'mobile_number',
        'house_street',
        'barangay',
        'municipality_city',
        'province',
        'postal_code',
        'profile_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'profile_completed_at' => 'datetime',
        ];
    }

    /**
     * Single source of truth for "cannot edit after first submission" (Phase 1).
     * Referenced by the ProfileUpdateRequest and the Coordinator override Policy
     * so the two enforcement points can't drift apart.
     */
    public const LOCKED_FIELDS = [
        'full_name', 'course_program', 'year_level', 'section', 'year_graduated',
    ];

    /**
     * The only programs this system offers — used for the Complete Profile
     * dropdown and its server-side validation.
     */
        public const PROGRAMS = [
        'BSIT',
        'BSHM',
        'BSTM',
        'BSBA - Operations Management',
        'BSBA - Financial Management',
        'BEED',
        'BSED - English',
        'BSED - Filipino',
        'BSED - Mathematics',
    ];

    /**
     * Matches what's already used by the Records filter — confirmed by the
     * user as the actual stored format, not an abbreviated "1st/2nd" style.
     */
    public const YEAR_LEVELS = ['First Year', 'Second Year', 'Third Year', 'Fourth Year'];

    public const SECTIONS = ['A', 'B', 'C', 'D', 'E'];

    /**
     * Curated nationality list rather than the full ISO country list, since
     * the student body is overwhelmingly Filipino. "Other" is the catch-all
     * for anything not listed.
     */
    public const NATIONALITIES = [
        'Filipino', 'Filipino-American', 'Filipino-Chinese', 'American', 'Australian',
        'British', 'Canadian', 'Chinese', 'Indian', 'Indonesian', 'Japanese', 'Korean',
        'Malaysian', 'Singaporean', 'Taiwanese', 'Thai', 'Vietnamese', 'Other',
    ];

    /**
     * Academic-year ranges for the Year Graduated dropdown, e.g. "2025-2026".
     * Fixed at 11 choices starting 2025-2026 per the approved spec — not tied
     * to the current date, so it won't silently shift year to year.
     */
    public static function academicYearRanges(): array
    {
        $ranges = [];

        for ($start = 2025; $start <= 2035; $start++) {
            $ranges[] = $start.'-'.($start + 1);
        }

        return $ranges;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeStudents($query)
    {
        return $query->where('status', 'student');
    }

    public function scopeAlumni($query)
    {
        return $query->where('status', 'alumni');
    }
}