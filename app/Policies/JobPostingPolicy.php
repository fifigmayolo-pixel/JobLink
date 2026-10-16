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

    /**
     * Approved postings are visible to any signed-in user. Until then only the
     * Job Coordinator and the company that owns the posting can see it, so
     * students and alumni can never reach an unapproved posting by URL.
     */
    public function view(User $user, JobPosting $jobPosting): bool
    {
        if ($jobPosting->approval_status === 'approved') {
            return true;
        }

        if ($user->role === 'job_coordinator') {
            return true;
        }

        return $user->role === 'company'
            && ($jobPosting->posted_by === $user->id
                || ($user->company?->id !== null && $jobPosting->company_id === $user->company->id));
    }

    /**
     * Approving / rejecting postings. Works with a class (the review queue
     * page) or a single posting (approve / reject actions).
     */
    public function review(User $user, ?JobPosting $jobPosting = null): bool
    {
        return $user->role === 'job_coordinator';
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