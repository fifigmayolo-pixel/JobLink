<?php

namespace App\Policies;

use App\Models\JobApplication;
use App\Models\ProfileClearance;
use App\Models\User;

/**
 * Who may open a clearance file.
 *
 *   - The owner, always.
 *   - A company, only while it has an application from the owner that is not withdrawn.
 *     (Withdrawing clears the application's link to the profile, so access ends with it.)
 *     A company whose job was later deleted still counts: the applicant applied to it.
 *   - Nobody else: not the Job Coordinator, not another company, not another student,
 *     and not a company that only found the person in candidate search.
 *   - Nobody, once the owner's account has been removed.
 *
 * ClearanceFileController answers 404, not 403, when this says no, so a stranger cannot
 * tell whether a clearance exists.
 */
class ProfileClearancePolicy
{
    public function view(User $user, ProfileClearance $clearance): bool
    {
        $profile = $clearance->profile;

        if (! $profile) {
            return false;
        }

        if ($user->id === $profile->user_id) {
            return true;
        }

        if ($user->role !== 'company') {
            return false;
        }

        $company = $user->company;

        if (! $company) {
            return false;
        }

        // A removed account (soft-deleted) no longer shares anything.
        if (! $profile->user()->exists()) {
            return false;
        }

        return JobApplication::query()
            ->where('profile_id', $profile->id)
            ->where('status', '!=', 'withdrawn')
            ->whereHas('jobPosting', fn ($posting) => $posting->withTrashed()->where('company_id', $company->id))
            ->exists();
    }
}
