<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

class AccountProvisioningService
{
    /**
     * Pre-registers a student/alumni record from Student ID + Full Name —
     * used by both the Coordinator's quick-add form and the roster import.
     * The password is random and never revealed to anyone; the account is
     * unusable until the student self-activates and sets their own.
     * activated_at stays null until that happens.
     */
    public function quickAddStudentOrAlumni(string $studentIdNumber, string $fullName, string $role): User
    {
        return User::create([
            'role' => $role,
            'student_id_number' => $studentIdNumber,
            'full_name' => $fullName,
            'password' => Str::password(32),
            'must_change_password' => true,
            'activated_at' => null,
        ]);
    }

    public function createCompanyWithTempPassword(array $companyData, int $createdBy): array
    {
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

        return ['user' => $user, 'company' => $company, 'temp_password' => $tempPassword];
    }

    /**
     * PESO doesn't self-activate (no Student ID/Name to verify against),
     * so it keeps the original hand-over-a-temp-password pattern, same as
     * Company accounts.
     */
    public function createPesoAccount(string $email): array
    {
        $user = User::create([
            'role' => 'peso',
            'email' => $email,
            'password' => $tempPassword = $this->generateTempPassword(),
            'must_change_password' => true,
            'activated_at' => now(),
        ]);

        return ['user' => $user, 'temp_password' => $tempPassword];
    }

    public function regenerateTempPassword(User $user): string
    {
        $user->update([
            'password' => $tempPassword = $this->generateTempPassword(),
            'must_change_password' => true,
        ]);

        return $tempPassword;
    }

    private function generateTempPassword(): string
    {
        return Str::password(10, symbols: false);
    }
}