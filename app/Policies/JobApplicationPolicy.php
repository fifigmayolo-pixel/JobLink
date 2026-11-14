<?php

namespace App\Policies;

use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\User;

class JobApplicationPolicy
{
    public function view(User $user, JobApplication $application): bool
    {
        return $application->applicant_id === $user->id || $this->review($user, $application);
    }

    public function review(User $user, JobApplication $application): bool
    {
        return $user->role === 'company'
            && $user->company
            && $application->jobPosting->company_id === $user->company->id;
    }

    /**
     * Students and alumni may apply to a posting that is open: status "open" AND
     * the deadline has not passed (same meaning as JobPosting::scopeOpen).
     *
     * Approval and archived-company checks happen in ApplicationController@store,
     * and "CV ready" is checked there too, so the student gets a clear message
     * instead of a bare 403.
     */
    public function create(User $user, JobPosting $jobPosting): bool
    {
        return in_array($user->role, ['student', 'alumni'])
            && $jobPosting->status === 'open'
            && ($jobPosting->application_deadline === null
                || $jobPosting->application_deadline->gte(now()->startOfDay()));
    }

    /**
     * Only the applicant, and only while the company hasn't acted on it yet.
     */
    public function withdraw(User $user, JobApplication $application): bool
    {
        return $application->applicant_id === $user->id && $application->status === 'pending';
    }

    /**
     * Remove from "My Applications". Only the applicant, and only once the
     * application has ended (rejected or withdrawn). Pending and interview
     * applications are still active, so they can't be removed.
     */
    public function hide(User $user, JobApplication $application): bool
    {
        return $application->applicant_id === $user->id
            && in_array($application->status, ['rejected', 'withdrawn'], true);
    }
}
