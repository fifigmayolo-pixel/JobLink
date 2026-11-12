<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobPosting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class JobApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_student_can_apply_with_their_profile(): void
    {
        $student = User::factory()->withProfile()->create();
        $job = $this->openJob();

        $response = $this->actingAs($student)->post(route('applications.store', $job));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $this->assertDatabaseHas('job_applications', [
            'job_posting_id' => $job->id,
            'applicant_id' => $student->id,
            'status' => 'pending',
        ]);
    }

    public function test_alumni_can_apply_with_their_profile(): void
    {
        $alumni = User::factory()->alumni()->withProfile()->create();
        $job = $this->openJob();

        $this->actingAs($alumni)->post(route('applications.store', $job))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('job_applications', [
            'job_posting_id' => $job->id,
            'applicant_id' => $alumni->id,
            'status' => 'pending',
        ]);
    }

    public function test_student_cannot_apply_to_an_unapproved_job(): void
    {
        $student = User::factory()->withProfile()->create();
        $job = JobPosting::factory()->create(['approval_status' => 'pending']);

        $this->actingAs($student)->post(route('applications.store', $job))
            ->assertNotFound();

        $this->assertSame(0, $job->applications()->count());
    }

    public function test_student_cannot_apply_twice_to_the_same_job(): void
    {
        $student = User::factory()->withProfile()->create();
        $job = $this->openJob();
        $job->applications()->create([
            'applicant_id' => $student->id,
            'date_applied' => now(),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($student)->post(route('applications.store', $job));

        $response->assertSessionHasErrors('application');
        $this->assertSame(1, $job->applications()->count());
    }

    public function test_student_can_apply_again_after_withdrawing(): void
    {
        $student = User::factory()->withProfile()->create();
        $job = $this->openJob();
        $job->applications()->create([
            'applicant_id' => $student->id,
            'date_applied' => now(),
            'status' => 'withdrawn',
        ]);

        $this->actingAs($student)->post(route('applications.store', $job))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $job->applications()->count());
        $this->assertSame(1, $job->applications()->where('status', 'pending')->count());
    }

    public function test_student_cannot_apply_to_a_closed_job(): void
    {
        $student = User::factory()->withProfile()->create();
        $job = $this->closedJob();

        $this->actingAs($student)->post(route('applications.store', $job))
            ->assertForbidden();

        $this->assertSame(0, $job->applications()->count());
    }

    public function test_a_company_cannot_apply_to_a_job(): void
    {
        $company = Company::factory()->create();
        $job = $this->openJob();

        $this->actingAs($company->user)->post(route('applications.store', $job))
            ->assertForbidden();
    }

    public function test_student_can_withdraw_a_pending_application(): void
    {
        $student = User::factory()->withProfile()->create();
        $job = $this->openJob();
        $application = $job->applications()->create([
            'applicant_id' => $student->id,
            'date_applied' => now(),
            'status' => 'pending',
        ]);

        $this->actingAs($student)
            ->patch(route('applications.withdraw', $application))
            ->assertRedirect(route('applications.show', $application));

        $this->assertSame('withdrawn', $application->fresh()->status);
    }

    public function test_student_cannot_withdraw_after_the_company_has_acted(): void
    {
        $student = User::factory()->withProfile()->create();
        $job = $this->openJob();
        $application = $job->applications()->create([
            'applicant_id' => $student->id,
            'date_applied' => now(),
            'status' => 'interview',
        ]);

        $this->actingAs($student)
            ->patch(route('applications.withdraw', $application))
            ->assertForbidden();

        $this->assertSame('interview', $application->fresh()->status);
    }

    public function test_resume_download_routes_no_longer_exist(): void
    {
        $this->assertFalse(Route::has('applications.resume'));
        $this->assertFalse(Route::has('company.applications.resume'));
    }

    private function openJob(): JobPosting
    {
        return JobPosting::factory()->create([
            'approval_status' => 'approved',
            'status' => 'open',
        ]);
    }

    private function closedJob(): JobPosting
    {
        return JobPosting::factory()->closed()->create([
            'approval_status' => 'approved',
        ]);
    }
}