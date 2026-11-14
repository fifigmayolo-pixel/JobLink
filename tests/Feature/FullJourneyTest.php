<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobPosting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The thesis story in one test: a company posts a job, the Coordinator approves it,
 * a student applies with their CV, the company reads the CV and invites them to an
 * interview, and the student is notified.
 */
class FullJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_from_posting_a_job_to_the_student_being_notified(): void
    {
        $coordinator = User::factory()->jobCoordinator()->create();
        $company = Company::factory()->create();
        $student = User::factory()->cvReady()->create();

        // 1. The company posts a job. It waits for approval.
        $this->actingAs($company->user)
            ->post(route('jobs.store'), [
                'title' => 'Journey Developer',
                'description' => 'Build things for the journey test.',
                'qualifications' => 'Any IT course.',
                'requirements' => 'A willingness to learn.',
                'location' => 'Alaminos City',
                'job_type' => 'full_time',
                'application_deadline' => now()->addWeek()->toDateString(),
            ])
            ->assertSessionHasNoErrors();

        $job = JobPosting::where('title', 'Journey Developer')->firstOrFail();
        $this->assertSame('pending', $job->approval_status);
        $this->assertSame($company->id, $job->company_id);

        // 2. Until it is approved, students can neither see it nor apply to it.
        $this->actingAs($student)->get(route('jobs.index'))
            ->assertOk()
            ->assertDontSee('Journey Developer');
        $this->post(route('applications.store', $job))->assertNotFound();

        // 3. The Coordinator approves it.
        $this->actingAs($coordinator)
            ->patch(route('coordinator.job-approvals.approve', $job))
            ->assertSessionHasNoErrors();
        $this->assertSame('approved', $job->fresh()->approval_status);

        // 4. Now the student sees it and applies. Their CV is copied into the application.
        $this->actingAs($student)->get(route('jobs.index'))
            ->assertOk()
            ->assertSee('Journey Developer');

        $this->post(route('applications.store', $job))->assertSessionHasNoErrors();

        $application = $job->applications()->firstOrFail();
        $this->assertSame('pending', $application->status);
        $this->assertTrue($application->hasCvSnapshot());

        // 5. The company opens the application, reads the CV and invites them to interview.
        $this->actingAs($company->user)
            ->get(route('company.applications.show', $application))
            ->assertOk()
            ->assertSee('Fresh graduate'); // the summary from the student's CV

        $this->patch(route('company.applications.status', $application), ['status' => 'interview'])
            ->assertRedirect();
        $this->assertSame('interview', $application->fresh()->status);

        // 6. The student is notified, and can open their notifications.
        $this->assertDatabaseHas('notifications', [
            'recipient_id' => $student->id,
            'related_application_id' => $application->id,
            'type' => 'interview_invitation',
        ]);

        $this->actingAs($student)->get(route('notifications.index'))->assertOk();
        $this->get(route('applications.show', $application))
            ->assertOk()
            ->assertSee('invited to interview');
    }
}
