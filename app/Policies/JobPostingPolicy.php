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
     * Who may open a posting:
     *  - the Job Coordinator: every posting;
     *  - a company: only its own (tagged to its company, or posted by its login),
     *    whatever the approval status. It can never open another company's posting,
     *    even an approved one;
     *  - students and alumni: approved postings only, so an unapproved posting can
     *    never be reached by URL.
     *
     * The controller turns a "no" into a 404, so ids can't be probed.
     */
    public function view(User $user, JobPosting $jobPosting): bool
    {
        if ($user->role === 'job_coordinator') {
            return true;
        }

        if ($user->role === 'company') {
            return $this->ownedByCompany($user, $jobPosting);
        }

        return $jobPosting->approval_status === 'approved';
    }

    /**
     * Approving / rejecting postings. Works with a class (the review queue
     * page) or a single posting (approve / reject actions).
     */
    public function review(User $user, ?JobPosting $jobPosting = null): bool
    {
        return $user->role === 'job_coordinator';
    }

    /**
     * Edit or close a posting: the Coordinator, or the company login that posted it.
     * A posting the Coordinator posted on a company's behalf can be opened by that
     * company but not changed by it.
     */
    public function update(User $user, JobPosting $jobPosting): bool
    {
        return $user->role === 'job_coordinator'
            || ($user->role === 'company' && $jobPosting->posted_by === $user->id);
    }

    public function delete(User $user, JobPosting $jobPosting): bool
    {
        return $this->update($user, $jobPosting);
    }

    /**
     * "Its own": posted by this login, or tagged to this login's company. A company
     * with no company record owns nothing (never compare against a null id).
     */
    private function ownedByCompany(User $user, JobPosting $jobPosting): bool
    {
        if ($jobPosting->posted_by === $user->id) {
            return true;
        }

        $companyId = $user->company?->id;

        return $companyId !== null && $jobPosting->company_id === $companyId;
    }
}
