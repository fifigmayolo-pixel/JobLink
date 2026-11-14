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

    // ---- applying ---------------------------------------------------------

    public function test_student_can_apply_with_their_profile(): void
    {
        $student = User::factory()->cvReady()->create();
        $job = $this->openJob();

        $response = $this->actingAs($student)->post(route('applications.store', $job));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $this->assertDatabaseHas('job_applications', [
            'job_posting_id' => $job->id,
            'applicant_id' => $student->id,
            'profile_id' => $student->profile()->first()->id,
            'status' => 'pending',
        ]);
        $this->assertTrue($job->applications()->first()->hasCvSnapshot());
    }

    public function test_alumni_can_apply_with_their_profile(): void
    {
        $alumni = User::factory()->alumni()->cvReady()->create();
        $job = $this->openJob();

        $this->actingAs($alumni)->post(route('applications.store', $job))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('job_applications', [
            'job_posting_id' => $job->id,
            'applicant_id' => $alumni->id,
            'status' => 'pending',
        ]);
    }

    public function test_a_student_whose_cv_is_not_ready_cannot_apply(): void
    {
        $job = $this->openJob();

        // No CV at all.
        $empty = User::factory()->withProfile()->create();
        $this->actingAs($empty)->post(route('applications.store', $job))
            ->assertSessionHasErrors('application');

        // A summary but no skill is still not enough.
        $half = User::factory()->withProfile()->create();
        $half->profile()->first()->update([
            'cv_summary' => 'Fresh graduate looking for an entry-level role where I can learn and contribute.',
        ]);
        $this->actingAs($half)->post(route('applications.store', $job))
            ->assertSessionHasErrors('application');

        $this->assertSame(0, $job->applications()->count());
    }

    public function test_the_snapshot_holds_the_cv_and_leaves_out_private_details(): void
    {
        $student = User::factory()->cvReady()->create();
        $profile = $student->profile()->first();
        $profile->update([
            'gender' => 'MARKER-GENDER',
            'civil_status' => 'MARKER-CIVIL',
            'house_street' => 'MARKER-STREET 12',
            'personal_email' => 'jane@example.com',
            'mobile_number' => '09171234567',
            'municipality_city' => 'Alaminos City',
            'province' => 'Pangasinan',
            'linkedin_url' => 'https://www.linkedin.com/in/jane',
        ]);
        $profile->experiences()->create([
            'kind' => 'ojt',
            'position' => 'IT Intern',
            'organization' => 'City Hall',
            'start_date' => '2025-06-01',
        ]);
        $job = $this->openJob();

        $this->actingAs($student)->post(route('applications.store', $job))->assertSessionHasNoErrors();

        $snapshot = $job->applications()->first()->cv_snapshot;

        $this->assertSame(1, $snapshot['v']);
        $this->assertSame($profile->full_name, $snapshot['personal']['full_name']);
        $this->assertSame('jane@example.com', $snapshot['personal']['email']);
        $this->assertSame('09171234567', $snapshot['personal']['mobile']);
        $this->assertSame('Alaminos City', $snapshot['personal']['city']);
        $this->assertSame('Pangasinan', $snapshot['personal']['province']);
        $this->assertSame($profile->course_program, $snapshot['personal']['course_program']);
        $this->assertSame('https://www.linkedin.com/in/jane', $snapshot['links']['linkedin']);
        $this->assertSame(['Communication'], $snapshot['skills']);
        $this->assertSame('IT Intern', $snapshot['experiences'][0]['position']);
        $this->assertSame('2025-06-01', $snapshot['experiences'][0]['start_date']);
        $this->assertNull($snapshot['experiences'][0]['end_date']);

        // Private details are not in the copy the company gets.
        foreach (['date_of_birth', 'age', 'gender', 'civil_status', 'house_street'] as $key) {
            $this->assertArrayNotHasKey($key, $snapshot['personal']);
        }
        $json = json_encode($snapshot);
        foreach (['MARKER-GENDER', 'MARKER-CIVIL', 'MARKER-STREET'] as $private) {
            $this->assertStringNotContainsString($private, $json);
        }
    }

    public function test_changing_the_cv_later_does_not_change_an_application_already_sent(): void
    {
        $student = User::factory()->cvReady()->create();
        $profile = $student->profile()->first();
        $job = $this->openJob();

        $this->actingAs($student)->post(route('applications.store', $job))->assertSessionHasNoErrors();

        $profile->update(['cv_summary' => 'A completely different summary written after I applied.']);
        $profile->skills()->delete();

        $snapshot = $job->applications()->first()->cv_snapshot;

        $this->assertStringStartsWith('Fresh graduate', $snapshot['summary']);
        $this->assertSame(['Communication'], $snapshot['skills']);
    }

    // ---- the existing rules -----------------------------------------------

    public function test_student_cannot_apply_to_an_unapproved_job(): void
    {
        $student = User::factory()->cvReady()->create();
        $job = JobPosting::factory()->create(['approval_status' => 'pending']);

        $this->actingAs($student)->post(route('applications.store', $job))
            ->assertNotFound();

        $this->assertSame(0, $job->applications()->count());
    }

    public function test_student_cannot_apply_twice_to_the_same_job(): void
    {
        $student = User::factory()->cvReady()->create();
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
        $student = User::factory()->cvReady()->create();
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
        $student = User::factory()->cvReady()->create();
        $job = $this->closedJob();

        $this->actingAs($student)->post(route('applications.store', $job))
            ->assertForbidden();

        $this->assertSame(0, $job->applications()->count());
    }

    public function test_student_cannot_apply_after_the_deadline_has_passed(): void
    {
        $student = User::factory()->cvReady()->create();
        $job = JobPosting::factory()->expired()->create(['approval_status' => 'approved']);

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

    // ---- withdrawing ------------------------------------------------------

    public function test_student_can_withdraw_a_pending_application(): void
    {
        $student = User::factory()->cvReady()->create();
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

    public function test_withdrawing_removes_the_cv_the_company_was_holding(): void
    {
        $student = User::factory()->cvReady()->create();
        $job = $this->openJob();

        $this->actingAs($student)->post(route('applications.store', $job))->assertSessionHasNoErrors();
        $application = $job->applications()->first();
        $this->assertTrue($application->hasCvSnapshot());

        $this->actingAs($student)->patch(route('applications.withdraw', $application));

        $application = $application->fresh();
        $this->assertSame('withdrawn', $application->status);
        $this->assertNull($application->cv_snapshot);
        $this->assertNull($application->profile_id);
    }

    public function test_student_cannot_withdraw_after_the_company_has_acted(): void
    {
        $student = User::factory()->cvReady()->create();
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

    // ---- the job page -----------------------------------------------------

    public function test_the_job_page_offers_apply_when_the_cv_is_ready(): void
    {
        $student = User::factory()->cvReady()->create();
        $job = $this->openJob();

        $this->actingAs($student)->get(route('jobs.show', $job))
            ->assertOk()
            ->assertSee('Apply now')
            ->assertDontSee('Finish your CV to apply');
    }

    public function test_the_job_page_sends_a_student_without_a_cv_to_finish_it(): void
    {
        $student = User::factory()->withProfile()->create();
        $job = $this->openJob();

        $this->actingAs($student)->get(route('jobs.show', $job))
            ->assertOk()
            ->assertSee('Finish your CV to apply')
            ->assertSee(route('cv.edit'))
            ->assertDontSee('Apply now');
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
