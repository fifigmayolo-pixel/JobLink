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
        'cv_summary',
        'linkedin_url',
        'portfolio_url',
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
     * Minimum a profile needs before its owner can apply for jobs:
     * a summary of at least this many characters, and at least one skill.
     */
    public const CV_SUMMARY_MIN_LENGTH = 50;
    public const CV_MIN_SKILLS = 1;

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

    // CV sections. Kept unordered on purpose: PostgreSQL rejects
    // ->count() on a query that has an ORDER BY. Ordering happens in loadCv().

    public function educations()
    {
        return $this->hasMany(ProfileEducation::class, 'profile_id');
    }

    public function experiences()
    {
        return $this->hasMany(ProfileExperience::class, 'profile_id');
    }

    public function skills()
    {
        return $this->hasMany(ProfileSkill::class, 'profile_id');
    }

    public function certifications()
    {
        return $this->hasMany(ProfileCertification::class, 'profile_id');
    }

    public function applications()
    {
        return $this->hasMany(JobApplication::class, 'profile_id');
    }

    /**
     * Eager-load every CV section in display order. Use this before showing
     * the CV or calling cvCompleteness() so the counts need no extra queries.
     */
    public function loadCv(): static
    {
        return $this->load([
            'educations' => fn ($q) => $q->orderByDesc('start_year'),
            'experiences' => fn ($q) => $q->orderByDesc('start_date'),
            'skills' => fn ($q) => $q->orderBy('name'),
            'certifications' => fn ($q) => $q->orderByDesc('date_received'),
        ]);
    }

    public function hasCvSummary(): bool
    {
        return mb_strlen(trim((string) $this->cv_summary)) >= self::CV_SUMMARY_MIN_LENGTH;
    }

    /**
     * True when the profile meets the minimum to apply (summary + a skill).
     */
    public function cvReady(): bool
    {
        return $this->hasCvSummary() && $this->cvCount('skills') >= self::CV_MIN_SKILLS;
    }

    /**
     * Completeness is computed, never stored, so it can't go stale.
     * Required sections gate applying; optional ones only add to the percent.
     *
     * @return array{percent:int, ready:bool, missing_required:array<int,string>, sections:array}
     */
    public function cvCompleteness(): array
    {
        $sections = [
            'summary' => ['label' => 'Personal summary', 'points' => 30, 'required' => true, 'done' => $this->hasCvSummary()],
            'skills' => ['label' => 'Skills', 'points' => 25, 'required' => true, 'done' => $this->cvCount('skills') >= self::CV_MIN_SKILLS],
            'experience' => ['label' => 'Work experience or OJT', 'points' => 20, 'required' => false, 'done' => $this->cvCount('experiences') > 0],
            'education' => ['label' => 'Other education', 'points' => 10, 'required' => false, 'done' => $this->cvCount('educations') > 0],
            'certifications' => ['label' => 'Certifications and seminars', 'points' => 10, 'required' => false, 'done' => $this->cvCount('certifications') > 0],
            'links' => ['label' => 'Online links', 'points' => 5, 'required' => false, 'done' => filled($this->linkedin_url) || filled($this->portfolio_url)],
        ];

        $percent = (int) collect($sections)->where('done', true)->sum('points');

        $missingRequired = collect($sections)
            ->where('required', true)
            ->where('done', false)
            ->pluck('label')
            ->values()
            ->all();

        return [
            'percent' => $percent,
            'ready' => $missingRequired === [],
            'missing_required' => $missingRequired,
            'sections' => $sections,
        ];
    }

    /**
     * Uses the already-loaded relation when there is one, otherwise a COUNT.
     */
    private function cvCount(string $relation): int
    {
        return $this->relationLoaded($relation)
            ? $this->getRelation($relation)->count()
            : $this->{$relation}()->count();
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