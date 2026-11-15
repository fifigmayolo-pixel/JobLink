<?php

namespace App\Policies;

use App\Models\StudentAlumniProfile;
use App\Models\User;

/**
 * Who may open a student's or alumni's profile as a "candidate" (company search).
 * Bound to StudentAlumniProfile with #[UsePolicy] on the model.
 *
 * Two things must both be true:
 *  1. the user is a company account, and
 *  2. the profile is searchable (the student's own opt-in, CV ready, account active).
 *
 * The Job Coordinator is deliberately NOT allowed: coordinators manage records,
 * they do not browse applicants' CVs.
 *
 * The controller turns a "no" into a 404, not a 403, so nobody can probe which
 * profile ids exist or who has not opted in.
 */
class CandidateProfilePolicy
{
    public function viewAsCandidate(User $user, StudentAlumniProfile $profile): bool
    {
        return $user->role === 'company'
            && $user->company !== null
            && $profile->isSearchable();
    }
}
