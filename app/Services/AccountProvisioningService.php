<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

class AccountProvisioningService
{
    /**
     * Pre-registers a student/alumni record from Student ID + Full Name.
     * The account remains inactive until the student self-activates.
     */
    public function quickAddStudentOrAlumni(
        string $studentIdNumber,
        string $fullName,
        string $role
    ): User {
        return User::create([
            'role' => $role,
            'student_id_number' => $studentIdNumber,
            'full_name' => $fullName,
            'password' => Str::password(32),
            'must_change_password' => true,
            'activated_at' => null,
        ]);
    }

    /**
     * Creates a company account with a temporary password.
     */
    public function createCompanyWithTempPassword(
        array $companyData,
        int $createdBy
    ): array {
        $user = User::create([
            'role' => 'company',
            'email' => $companyData['email'],
            'password' => $tempPassword = $this->generateTempPassword(),
            'must_change_password' => true,
            'activated_at' => now(),
        ]);

        $company = $user->company()->create([
            'company_name' => $companyData['company_name'],
            'industry' => $companyData['industry'] ?? null,
            'contact_person' => $companyData['contact_person'] ?? null,
            'contact_number' => $companyData['contact_number'] ?? null,
            'address' => $companyData['address'] ?? null,
            'created_by' => $createdBy,
        ]);

        return [
            'user' => $user,
            'company' => $company,
            'temp_password' => $tempPassword,
        ];
    }

    /**
     * Creates a Job Coordinator account with a temporary password.
     */
    public function createCoordinatorWithTempPassword(
        string $fullName,
        string $email
    ): array {
        $user = User::create([
            'role' => 'job_coordinator',
            'full_name' => $fullName,
            'email' => $email,
            'password' => $tempPassword = $this->generateTempPassword(),
            'must_change_password' => true,
            'activated_at' => now(),
        ]);

        return [
            'user' => $user,
            'temp_password' => $tempPassword,
        ];
    }

    /**
     * Regenerates a user's temporary password.
     */
    public function regenerateTempPassword(User $user): string
    {
        $tempPassword = $this->generateTempPassword();

        $user->update([
            'password' => $tempPassword,
            'must_change_password' => true,
        ]);

        return $tempPassword;
    }

    /**
     * Generates a temporary password.
     */
    private function generateTempPassword(): string
    {
        return Str::password(10, symbols: false);
    }
}
