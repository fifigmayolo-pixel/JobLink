<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\ProfileClearance;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesClearances;
use Tests\TestCase;

/**
 * Phase 14: applying needs a ready CV and BOTH clearances, uploaded and not expired.
 * A clearance is valid through its date and Expired from the next day.
 */
class ClearanceApplyBlockTest extends TestCase
{
    use MakesClearances;
    use RefreshDatabase;

    private JobPosting $job;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->fakeClearanceDisk();

        $this->job = $this->job('Apply Block Job');
    }

    // ---- nothing in the way ---------------------------------------------------

    public function test_with_both_clearances_valid_the_student_can_apply(): void
    {
        $student = User::factory()->cvReady()->create();

        $this->actingAs($student)->post(route('applications.store', $this->job))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('job_applications', ['job_posting_id' => $this->job->id, 'applicant_id' => $student->id]);
    }

    // ---- missing --------------------------------------------------------------

    public function test_without_any_clearance_the_student_cannot_apply_and_is_told_what_is_missing(): void
    {
        $student = $this->studentWithoutClearances();

        $this->actingAs($student)->post(route('applications.store', $this->job))
            ->assertSessionHasErrors(['application' => 'Upload your NBI clearance and barangay clearance to apply.']);

        $this->assertDatabaseMissing('job_applications', ['applicant_id' => $student->id]);

        $profile = $student->profile()->first();
        $this->assertTrue($profile->cvReady(), 'the CV itself is fine');
        $this->assertFalse($profile->clearancesReady());
        $this->assertFalse($profile->canApply());
    }

    public function test_the_job_page_says_so_and_offers_no_apply_button(): void
    {
        $student = $this->studentWithoutClearances();

        $this->actingAs($student)->get(route('jobs.show', $this->job))
            ->assertOk()
            ->assertSee('Upload your clearances to apply')
            ->assertSee('Upload your NBI clearance and barangay clearance to apply.')
            ->assertSee('Go to My Clearances')
            ->assertSee(route('clearances.edit'), false)
            ->assertDontSee('Apply now');
    }

    public function test_the_job_page_offers_apply_when_both_clearances_are_valid(): void
    {
        $student = User::factory()->cvReady()->create();

        $this->actingAs($student)->get(route('jobs.show', $this->job))
            ->assertOk()
            ->assertSee('Apply now')
            ->assertSee('NBI clearance and barangay clearance')   // the confirmation says what the company can open
            ->assertDontSee('Upload your clearances to apply');
    }

    public function test_one_missing_clearance_is_named(): void
    {
        $student = $this->studentWithoutClearances();
        ProfileClearance::factory()->type('nbi')->create(['profile_id' => $student->profile()->first()->id]);

        $this->actingAs($student)->post(route('applications.store', $this->job))
            ->assertSessionHasErrors(['application' => 'Upload your barangay clearance to apply.']);

        $this->assertDatabaseMissing('job_applications', ['applicant_id' => $student->id]);
    }

    // ---- expired --------------------------------------------------------------

    public function test_an_expired_clearance_blocks_applying_until_it_is_replaced(): void
    {
        $student = $this->studentWithoutClearances();
        $profileId = $student->profile()->first()->id;

        ProfileClearance::factory()->type('nbi')->expired()->create(['profile_id' => $profileId]);
        ProfileClearance::factory()->type('barangay')->create(['profile_id' => $profileId]);

        $this->actingAs($student)->post(route('applications.store', $this->job))
            ->assertSessionHasErrors(['application' => 'Your NBI clearance has expired. Replace it to apply.']);
        $this->assertDatabaseMissing('job_applications', ['applicant_id' => $student->id]);

        // The job page shows the same thing.
        $this->get(route('jobs.show', $this->job))
            ->assertSee('Your NBI clearance has expired. Replace it to apply.')
            ->assertDontSee('Apply now');

        // Replacing it through the page unblocks applying.
        $this->uploadClearance($student, 'nbi', ['valid_until' => now()->addMonths(6)->toDateString()])
            ->assertSessionHasNoErrors();

        $this->actingAs($student)->post(route('applications.store', $this->job))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('job_applications', ['job_posting_id' => $this->job->id, 'applicant_id' => $student->id]);
    }

    public function test_both_expired_and_one_missing_and_one_expired_read_clearly(): void
    {
        $bothExpired = $this->studentWithoutClearances();
        foreach (['nbi', 'barangay'] as $type) {
            ProfileClearance::factory()->type($type)->expired()->create(['profile_id' => $bothExpired->profile()->first()->id]);
        }

        $this->actingAs($bothExpired)->post(route('applications.store', $this->job))
            ->assertSessionHasErrors(['application' => 'Your NBI clearance and barangay clearance have expired. Replace them to apply.']);

        $mixed = $this->studentWithoutClearances();
        ProfileClearance::factory()->type('barangay')->expired()->create(['profile_id' => $mixed->profile()->first()->id]);

        $this->actingAs($mixed)->post(route('applications.store', $this->job))
            ->assertSessionHasErrors(['application' => 'Upload your NBI clearance to apply. Your barangay clearance has expired. Replace it to apply.']);
    }

    public function test_a_clearance_is_valid_through_its_date_and_expired_the_day_after(): void
    {
        $today = User::factory()->cvReadyWithoutClearances()->create();
        foreach (['nbi', 'barangay'] as $type) {
            ProfileClearance::factory()->type($type)->validUntil(today())->create(['profile_id' => $today->profile()->first()->id]);
        }

        $yesterday = User::factory()->cvReadyWithoutClearances()->create();
        foreach (['nbi', 'barangay'] as $type) {
            ProfileClearance::factory()->type($type)->validUntil(today()->subDay())->create(['profile_id' => $yesterday->profile()->first()->id]);
        }

        $this->actingAs($today)->post(route('applications.store', $this->job))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('job_applications', ['applicant_id' => $today->id]);

        $this->actingAs($yesterday)->post(route('applications.store', $this->job))->assertSessionHasErrors('application');
        $this->assertDatabaseMissing('job_applications', ['applicant_id' => $yesterday->id]);
    }

    public function test_the_check_uses_the_day_of_applying_not_the_day_of_uploading(): void
    {
        $student = User::factory()->cvReady()->create();
        $student->profile()->first()->clearances()->update(['valid_until' => now()->addDays(5)->toDateString()]);

        $second = $this->job('Second Apply Job');

        $this->actingAs($student)->post(route('applications.store', $this->job))->assertSessionHasNoErrors();

        // Six days later both clearances have expired.
        $this->travel(6)->days();

        $this->actingAs($student)->post(route('applications.store', $second))->assertSessionHasErrors('application');
        $this->assertDatabaseMissing('job_applications', ['job_posting_id' => $second->id, 'applicant_id' => $student->id]);
    }

    public function test_an_application_already_sent_stays_when_a_clearance_expires_later(): void
    {
        $student = User::factory()->cvReady()->create();

        $this->actingAs($student)->post(route('applications.store', $this->job))->assertSessionHasNoErrors();
        $application = JobApplication::where('applicant_id', $student->id)->firstOrFail();

        $this->clearanceOf($student, 'nbi')->update(['valid_until' => now()->subDay()->toDateString()]);

        $this->assertSame('pending', $application->fresh()->status);
        $this->assertTrue($application->fresh()->hasCvSnapshot());

        // But they cannot apply to anything new until it is replaced.
        $this->actingAs($student)->post(route('applications.store', $this->job('Another Job')))->assertSessionHasErrors('application');
    }

    // ---- uploading unblocks ---------------------------------------------------

    public function test_uploading_both_clearances_through_the_page_unblocks_applying(): void
    {
        $student = $this->studentWithoutClearances();

        $this->actingAs($student)->post(route('applications.store', $this->job))->assertSessionHasErrors('application');

        $this->uploadClearance($student, 'nbi')->assertSessionHasNoErrors();

        // One is not enough.
        $this->actingAs($student)->post(route('applications.store', $this->job))
            ->assertSessionHasErrors(['application' => 'Upload your barangay clearance to apply.']);

        $this->uploadClearance($student, 'barangay', ['consent' => null])->assertSessionHasNoErrors();

        $this->actingAs($student)->post(route('applications.store', $this->job))->assertSessionHasNoErrors();
        $application = JobApplication::where('applicant_id', $student->id)->firstOrFail();
        $this->assertSame('pending', $application->status);
        $this->assertTrue($application->hasCvSnapshot());
    }

    // ---- what the clearances do NOT change ---------------------------------------

    public function test_the_cv_problems_are_still_reported_first(): void
    {
        $noCv = User::factory()->withProfile()->create();

        $this->actingAs($noCv)->post(route('applications.store', $this->job))
            ->assertSessionHasErrors('application');

        $message = session('errors')->first('application');
        $this->assertStringStartsWith('Finish your CV before applying.', $message);
        $this->assertStringNotContainsString('clearance', $message);
    }

    public function test_the_cv_strength_percent_ignores_the_clearances(): void
    {
        $with = User::factory()->cvReady()->create()->profile()->first()->cvCompleteness();
        $without = $this->studentWithoutClearances()->profile()->first()->cvCompleteness();

        $this->assertSame($without['percent'], $with['percent']);
        $this->assertSame($without['ready'], $with['ready']);
    }

    public function test_the_card_on_my_cv_lists_the_required_documents(): void
    {
        $student = $this->studentWithoutClearances();

        $this->actingAs($student)->get(route('cv.edit'))
            ->assertOk()
            ->assertSee('Required documents')
            ->assertSee('Your CV is ready. To start applying, add:')
            ->assertSee('NBI clearance and Barangay clearance')
            ->assertDontSee('Ready to apply');

        $ready = User::factory()->cvReady()->create();

        $this->actingAs($ready)->get(route('cv.edit'))
            ->assertOk()
            ->assertSee('Required documents')
            ->assertSee('Ready to apply');
    }

    public function test_being_findable_by_companies_does_not_depend_on_the_clearances(): void
    {
        $student = $this->studentWithoutClearances();
        $profile = $student->profile()->first();

        $profile->update(['searchable_by_companies' => true, 'searchable_consented_at' => now()]);

        $this->assertTrue($profile->fresh()->isSearchable());
    }

    // ---- helpers ----------------------------------------------------------------

    private function job(string $title): JobPosting
    {
        $company = Company::factory()->create();

        return JobPosting::factory()->for($company)->create([
            'title' => $title,
            'posted_by' => $company->user->id,
            'approval_status' => 'approved',
            'status' => 'open',
            'application_deadline' => now()->addWeeks(3),
            'slots' => 5,
        ]);
    }
}
