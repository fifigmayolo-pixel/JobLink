<?php

namespace App\Support;

use App\Models\StudentAlumniProfile;

/**
 * What a company sees of a searchable student or alumni BEFORE they apply.
 *
 * Built from CvSnapshot (which already leaves out date of birth, age, gender, civil
 * status, street address and profile picture) and then also removes:
 *  - email and mobile number,
 *  - the LinkedIn and portfolio links (a link is a way to message the student),
 *  - the "taken_at" date, which only makes sense for an application.
 *
 * Free text the student typed (summary, experience, education details) is passed
 * through ContactMask, so an email, phone number or web address written inside it
 * is hidden too.
 *
 * The candidate views only ever receive this array, never the profile model, so a
 * hidden field can't leak by accident from a Blade file. The student's "what
 * companies see" preview uses this same class, so the preview always matches.
 *
 * Shown: name, course, year, city and province, summary, skills, experience, other
 * education, certifications.
 * Never shown here: email, mobile number, links.
 */
class CandidateCv
{
    public static function forCompany(StudentAlumniProfile $profile): array
    {
        $cv = CvSnapshot::fromProfile($profile);

        unset(
            $cv['personal']['email'],
            $cv['personal']['mobile'],
            $cv['links'],
            $cv['taken_at'],
        );

        // Short fields keep names like "Amazon.com" but still lose emails and numbers.
        $short = fn ($value) => ContactMask::text($value, links: false);
        $long = fn ($value) => ContactMask::text($value);

        $cv['summary'] = $long($cv['summary'] ?? null);

        $cv['skills'] = array_map($short, $cv['skills'] ?? []);

        $cv['experiences'] = array_map(fn (array $e) => [
            ...$e,
            'position' => $short($e['position'] ?? null),
            'organization' => $short($e['organization'] ?? null),
            'description' => $long($e['description'] ?? null),
        ], $cv['experiences'] ?? []);

        $cv['educations'] = array_map(fn (array $e) => [
            ...$e,
            'school_name' => $short($e['school_name'] ?? null),
            'degree_program' => $short($e['degree_program'] ?? null),
            'details' => $long($e['details'] ?? null),
        ], $cv['educations'] ?? []);

        $cv['certifications'] = array_map(fn (array $c) => [
            ...$c,
            'title' => $short($c['title'] ?? null),
            'issuer' => $short($c['issuer'] ?? null),
        ], $cv['certifications'] ?? []);

        return $cv;
    }
}
