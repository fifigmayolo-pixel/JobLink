<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Creating company accounts — Job Coordinator only.
     */
    public function createCompany(User $user): bool
    {
        return $user->role === 'job_coordinator';
    }

    /**
     * Viewing, editing, and deleting existing company accounts — same role
     * as createCompany, kept separate so the two abilities can diverge later.
     */
    public function manageCompanies(User $user): bool
    {
        return $user->role === 'job_coordinator';
    }

    /**
     * Everything else — create/view/edit/delete student & alumni records,
     * temp passwords, bulk update, tracer study links — is Coordinator-only.
     */
    public function manageStudentsAndAlumni(User $user): bool
    {
        return $user->role === 'job_coordinator';
    }

    /**
     * The Coordinator sees the full audit log.
     */
    public function viewAuditLog(User $user): bool
    {
        return $user->role === 'job_coordinator';
    }

    /**
     * Exporting is Coordinator-only.
     */
    public function exportAuditLog(User $user): bool
    {
        return $user->role === 'job_coordinator';
    }



        /**
     * Creating other Job Coordinator accounts — Job Coordinator only.
     */
    public function createCoordinator(User $user): bool
    {
        return $user->role === 'job_coordinator';
    }
}