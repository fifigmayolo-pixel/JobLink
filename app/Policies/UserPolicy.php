<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * PESO and Coordinator both create companies (Phase 1 spec).
     */
    public function createCompany(User $user): bool
    {
        return in_array($user->role, ['peso', 'job_coordinator']);
    }

    /**
     * Viewing, editing, and deleting existing company accounts — same roles
     * as createCompany, kept separate so the two abilities can diverge later.
     */
    public function manageCompanies(User $user): bool
    {
        return in_array($user->role, ['peso', 'job_coordinator']);
    }

    /**
     * Everything else — create/view/edit/delete student & alumni records,
     * temp passwords, bulk update, tracer study links — is Coordinator-only.
     * PESO's permission list never includes these.
     */
    public function manageStudentsAndAlumni(User $user): bool
    {
        return $user->role === 'job_coordinator';
    }
}