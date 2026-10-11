<?php

namespace App\Policies;

use App\Models\JobPosting;
use App\Models\User;

class JobPostingPolicy
{
    public function create(User $user): bool
    {
        return in_array($user->role, ['job_coordinator', 'company']);
    }

    public function update(User $user, JobPosting $jobPosting): bool
    {
        return $user->role === 'job_coordinator'
            || $jobPosting->posted_by === $user->id;
    }

    public function delete(User $user, JobPosting $jobPosting): bool
    {
        return $this->update($user, $jobPosting);
    }
}