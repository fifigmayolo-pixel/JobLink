<?php

namespace App\Support;

use App\Models\StudentAlumniProfile;

/**
 * What a company sees of a searchable student or alumni BEFORE they apply or agree
 * to share contact details.
 *
 * Built from CvSnapshot (which already leaves out date of birth, age, gender, civil
 * status, street address and profile picture) and then also removes the contact
 * details. The candidate views only ever receive this array, never the profile
 * model, so a hidden field can't leak by accident from a Blade file.
 *
 * Shown: name, course, year, city and province, summary, skills, experience, other
 * education, certifications, links.
 * Never shown here: email, mobile number.
 */
class CandidateCv
{
    public static function forCompany(StudentAlumniProfile $profile): array
    {
        $cv = CvSnapshot::fromProfile($profile);

        unset(
            $cv['personal']['email'],
            $cv['personal']['mobile'],
            $cv['taken_at'], // "CV as submitted on..." only makes sense for an application
        );

        return $cv;
    }
}
