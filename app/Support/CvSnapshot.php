<?php

namespace App\Support;

use App\Models\StudentAlumniProfile;

/**
 * The copy of a student's CV that a company receives when they apply.
 *
 * Applications store this (job_applications.cv_snapshot) instead of reading the
 * live profile, so what the company reviewed never changes behind its back, and
 * it survives if the profile is later removed. Withdrawing clears it.
 *
 * Deliberately NOT included: date of birth, age, gender, civil status, street
 * address, profile picture. Companies only get what a hiring decision needs.
 *
 * Shape (v1):
 *  v, taken_at, personal{...}, summary, links{...}, skills[], experiences[],
 *  educations[], certifications[]
 * Bump VERSION if the shape changes, so renderers can tell old snapshots apart.
 */
class CvSnapshot
{
    public const VERSION = 1;

    public static function fromProfile(StudentAlumniProfile $profile): array
    {
        $profile->loadCv();

        return [
            'v' => self::VERSION,
            'taken_at' => now()->toIso8601String(),

            'personal' => [
                'full_name' => $profile->full_name,
                'email' => $profile->personal_email,
                'mobile' => $profile->mobile_number,
                'city' => $profile->municipality_city,
                'province' => $profile->province,
                // The PSU program and year come from the locked school record.
                'status' => $profile->status, // student or alumni
                'course_program' => $profile->course_program,
                'year_level' => $profile->year_level,         // students only
                'year_graduated' => $profile->year_graduated, // alumni only
            ],

            'summary' => $profile->cv_summary,

            'links' => [
                'linkedin' => $profile->linkedin_url,
                'portfolio' => $profile->portfolio_url,
            ],

            'skills' => $profile->skills->pluck('name')->values()->all(),

            'experiences' => $profile->experiences->map(fn ($e) => [
                'kind' => $e->kind,
                'position' => $e->position,
                'organization' => $e->organization,
                'start_date' => $e->start_date?->toDateString(),
                'end_date' => $e->end_date?->toDateString(), // null = still ongoing
                'description' => $e->description,
            ])->values()->all(),

            'educations' => $profile->educations->map(fn ($e) => [
                'level' => $e->level,
                'school_name' => $e->school_name,
                'degree_program' => $e->degree_program,
                'start_year' => $e->start_year,
                'end_year' => $e->end_year,
                'details' => $e->details,
            ])->values()->all(),

            'certifications' => $profile->certifications->map(fn ($c) => [
                'kind' => $c->kind,
                'title' => $c->title,
                'issuer' => $c->issuer,
                'date_received' => $c->date_received?->toDateString(),
            ])->values()->all(),
        ];
    }
}
