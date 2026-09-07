<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobPosting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyApplicationIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_a_company_cannot_review_another_companys_applications(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $job = JobPosting::factory()->for($companyA)->create();
        $applicant = User::factory()->create(['role' => 'student']);
        $application = $job->applications()->create([
            'applicant_id' => $applicant->id,
            'resume_path' => 'resumes/test.pdf',
            'date_applied' => now(),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($companyB->user)
            ->patch(route('company.applications.status', $application), ['status' => 'interview']);

        $response->assertForbidden();
        $this->assertSame('pending', $application->fresh()->status);
    }

    public function test_a_company_can_update_status_on_its_own_application_and_it_notifies_the_applicant(): void
    {
        $company = Company::factory()->create();
        $job = JobPosting::factory()->for($company)->create();
        $applicant = User::factory()->create(['role' => 'student']);
        $application = $job->applications()->create([
            'applicant_id' => $applicant->id,
            'resume_path' => 'resumes/test.pdf',
            'date_applied' => now(),
            'status' => 'pending',
        ]);

        $this->actingAs($company->user)
            ->patch(route('company.applications.status', $application), ['status' => 'interview'])
            ->assertRedirect();

        $this->assertSame('interview', $application->fresh()->status);
        $this->assertDatabaseHas('notifications', [
            'recipient_id' => $applicant->id,
            'related_application_id' => $application->id,
            'type' => 'interview_invitation',
        ]);
    }
}