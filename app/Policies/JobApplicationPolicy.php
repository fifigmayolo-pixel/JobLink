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

    public function create(User $user, JobPosting $jobPosting): bool
    {
        return in_array($user->role, ['student', 'alumni']) && $jobPosting->status === 'open';
    }
}