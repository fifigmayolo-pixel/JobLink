<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\FieldChangeLog;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\ProfileClearance;
use App\Models\User;
use App\Support\CvSnapshot;
use App\Support\ClearanceStore;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MakesClearances;
use Tests\TestCase;

/**
 * Phase 14: who can open a clearance file.
 *
 * Yes: the owner, and a company the owner has an active (not withdrawn) application with.
 * No: the Job Coordinator, any other company, any other student, a company that only found
 * the person in candidate search, a guest, and anyone once the owner's account is removed.
 * Everyone who is refused gets a plain 404.
 */
class ClearancePolicyTest extends TestCase
{
    use MakesClearances;
    use RefreshDatabase;

    private User $owner;
    private ProfileClearance $nbi;
    private ProfileClearance $barangay;
    private string $nbiContent;
    private Company $company;
    private JobPosting $job;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->fakeClearanceDisk();

        $this->owner = User::factory()->cvReady()->create();
        $profile = $this->owner->profile()->first();

        $this->nbi = $profile->clearances()->where('type', 'nbi')->firstOrFail();
        $this->barangay = $profile->clearances()->where('type', 'barangay')->firstOrFail();
        $this->nbiContent = $this->putClearanceFile($this->nbi);
        $this->putClearanceFile($this->barangay);

        $this->company = Company::factory()->create(['company_name' => 'Policy Corp']);
        $this->job = $this->job($this->company);
    }

    // ---- the owner ------------------------------------------------------------

    public function test_the_owner_opens_their_own_file_as_a_streamed_pdf(): void
    {
        $response = $this->actingAs($this->owner)->get(route('clearances.show', $this->nbi))->assertOk();

        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('NBI-Clearance.pdf', $response->headers->get('Content-Disposition'));

        // The real file comes back, byte for byte.
        $this->assertSame($this->nbiContent, $response->streamedContent());
    }

    public function test_the_owner_opening_their_own_file_is_not_logged(): void
    {
        $this->actingAs($this->owner)->get(route('clearances.show', $this->nbi))->assertOk();

        $this->assertDatabaseMissing('field_change_logs', ['action' => 'clearance_opened']);
    }

    // ---- a company the owner applied to ---------------------------------------

    public function test_a_company_the_student_applied_to_can_open_both_files_at_every_stage(): void
    {
        $application = $this->apply($this->job);

        foreach (['pending', 'interview', 'rejected'] as $status) {
            $application->update(['status' => $status]);

            foreach ([$this->nbi, $this->barangay] as $clearance) {
                $this->actingAs($this->company->user)
                    ->get(route('clearances.show', $clearance))
                    ->assertOk()
                    ->assertHeader('Content-Type', 'application/pdf');
            }
        }
    }

    public function test_a_company_opening_a_file_is_logged_once_a_day(): void
    {
        $this->apply($this->job);

        $this->actingAs($this->company->user);
        $this->get(route('clearances.show', $this->nbi))->assertOk();
        $this->get(route('clearances.show', $this->nbi))->assertOk();

        $this->assertSame(1, FieldChangeLog::where('action', 'clearance_opened')->count());
        $this->assertDatabaseHas('field_change_logs', [
            'action' => 'clearance_opened',
            'changed_by' => $this->company->user->id,
            'user_id' => $this->owner->id,
            'field_name' => 'nbi_clearance',
        ]);

        // The other document is a separate entry, and so is the same one on the next day.
        $this->get(route('clearances.show', $this->barangay))->assertOk();
        $this->assertSame(2, FieldChangeLog::where('action', 'clearance_opened')->count());

        $this->travel(1)->day();
        $this->get(route('clearances.show', $this->nbi))->assertOk();
        $this->assertSame(3, FieldChangeLog::where('action', 'clearance_opened')->count());

        // The entry names the company and the document, never the file.
        $entry = FieldChangeLog::where('action', 'clearance_opened')->first();
        $this->assertStringContainsString('Policy Corp', $entry->details);
        $this->assertStringNotContainsString('.pdf', $entry->details);
    }

    public function test_the_company_loses_access_when_the_student_withdraws(): void
    {
        $application = $this->apply($this->job);

        $this->actingAs($this->company->user)->get(route('clearances.show', $this->nbi))->assertOk();

        // The student withdraws through the real route (it clears the link to the profile).
        $this->actingAs($this->owner)->patch(route('applications.withdraw', $application))->assertRedirect();
        $this->assertSame('withdrawn', $application->fresh()->status);

        $this->actingAs($this->company->user)->get(route('clearances.show', $this->nbi))->assertNotFound();
        $this->actingAs($this->company->user)->get(route('clearances.show', $this->barangay))->assertNotFound();
    }

    public function test_a_company_keeps_access_when_it_deleted_the_job_later(): void
    {
        $this->apply($this->job);
        $this->job->delete();

        $this->actingAs($this->company->user)->get(route('clearances.show', $this->nbi))->assertOk();
    }

    public function test_nobody_but_the_owner_can_open_the_file_once_the_account_is_removed(): void
    {
        $this->apply($this->job);
        $this->owner->delete();

        $this->actingAs($this->company->user)->get(route('clearances.show', $this->nbi))->assertNotFound();
    }

    // ---- everyone else --------------------------------------------------------

    public function test_another_company_cannot_open_the_file(): void
    {
        $this->apply($this->job);

        $stranger = Company::factory()->create(['company_name' => 'Other Corp']);
        $this->job($stranger);

        $this->actingAs($stranger->user)->get(route('clearances.show', $this->nbi))->assertNotFound();
        $this->assertDatabaseMissing('field_change_logs', ['action' => 'clearance_opened']);
    }

    public function test_a_company_with_no_application_from_this_student_cannot_open_the_file(): void
    {
        // The student applied to someone else, not to this company.
        $other = Company::factory()->create();
        $this->apply($this->job($other));

        $this->actingAs($this->company->user)->get(route('clearances.show', $this->nbi))->assertNotFound();
    }

    public function test_the_coordinator_cannot_open_the_file(): void
    {
        $this->apply($this->job);
        $coordinator = User::factory()->jobCoordinator()->create();

        $this->actingAs($coordinator)->get(route('clearances.show', $this->nbi))->assertNotFound();
        $this->actingAs($coordinator)->get(route('clearances.show', $this->barangay))->assertNotFound();
    }

    public function test_another_student_cannot_open_the_file(): void
    {
        $other = User::factory()->cvReady()->create();

        $this->actingAs($other)->get(route('clearances.show', $this->nbi))->assertNotFound();
    }

    public function test_a_guest_is_sent_to_login(): void
    {
        $this->get(route('clearances.show', $this->nbi))->assertRedirect(route('login'));
    }

    public function test_a_company_that_only_found_the_student_in_search_cannot_open_the_file(): void
    {
        $candidate = User::factory()->searchable()->create();
        $profile = $candidate->profile()->first();
        $clearance = $profile->clearances()->where('type', 'nbi')->firstOrFail();
        $this->putClearanceFile($clearance);

        $page = $this->actingAs($this->company->user)->get(route('company.candidates.show', $profile))->assertOk();

        // No link to the file anywhere on the candidate page, and the file itself stays closed.
        $page->assertDontSee(route('clearances.show', $clearance), false);
        $this->get(route('clearances.show', $clearance))->assertNotFound();
    }

    public function test_a_missing_file_is_a_plain_404_even_for_the_owner(): void
    {
        ClearanceStore::disk()->delete($this->nbi->file_path);

        $this->actingAs($this->owner)->get(route('clearances.show', $this->nbi))->assertNotFound();
    }

    public function test_an_unknown_clearance_is_a_404_too(): void
    {
        $this->actingAs($this->owner)->get(route('clearances.show', 999999))->assertNotFound();
    }

    public function test_the_gate_gives_the_same_answers(): void
    {
        $this->apply($this->job);

        $coordinator = User::factory()->jobCoordinator()->create();
        $stranger = Company::factory()->create()->user;
        $other = User::factory()->cvReady()->create();

        foreach ([$this->nbi, $this->barangay] as $clearance) {
            $this->assertTrue(Gate::forUser($this->owner)->allows('view', $clearance));
            $this->assertTrue(Gate::forUser($this->company->user)->allows('view', $clearance));
            $this->assertFalse(Gate::forUser($stranger)->allows('view', $clearance));
            $this->assertFalse(Gate::forUser($coordinator)->allows('view', $clearance));
            $this->assertFalse(Gate::forUser($other)->allows('view', $clearance));
        }
    }

    // ---- what the company sees on the application ---------------------------

    public function test_the_company_application_page_lists_the_clearances_with_links(): void
    {
        // Needs the small edit to resources/views/company/applications/show.blade.php from the README.
        $application = $this->apply($this->job);

        $this->actingAs($this->company->user)
            ->get(route('company.applications.show', $application))
            ->assertOk()
            ->assertSee('NBI clearance')
            ->assertSee('Barangay clearance')
            ->assertSee('Valid until')
            ->assertSee(route('clearances.show', $this->nbi), false)
            ->assertSee(route('clearances.show', $this->barangay), false);
    }

    public function test_the_company_page_marks_an_expired_clearance(): void
    {
        $application = $this->apply($this->job);
        $this->nbi->update(['valid_until' => now()->subDays(2)->toDateString()]);

        $this->actingAs($this->company->user)
            ->get(route('company.applications.show', $application))
            ->assertOk()
            ->assertSee('Expired '.now()->subDays(2)->format('M j, Y'));
    }

    public function test_the_company_page_has_no_links_once_the_account_is_removed(): void
    {
        $application = $this->apply($this->job);
        $this->owner->delete();

        $this->actingAs($this->company->user)
            ->get(route('company.applications.show', $application))
            ->assertOk()
            ->assertSee('clearance files can no longer be opened')
            ->assertDontSee(route('clearances.show', $this->nbi), false);
    }

    public function test_another_company_cannot_see_the_application_page_or_its_clearances(): void
    {
        $application = $this->apply($this->job);
        $stranger = Company::factory()->create()->user;

        $status = $this->actingAs($stranger)->get(route('company.applications.show', $application))->getStatusCode();

        $this->assertContains($status, [403, 404]);
    }

    public function test_the_printable_cv_lists_the_dates_but_no_file_links(): void
    {
        // Needs the small edit to resources/views/company/applications/cv-print.blade.php from the README.
        $application = $this->apply($this->job, withCv: true);

        $this->actingAs($this->company->user)
            ->get(route('company.applications.cv', $application))
            ->assertOk()
            ->assertSee('Clearances on file')
            ->assertSee('NBI clearance: valid until '.$this->nbi->valid_until->format('M j, Y'))
            ->assertDontSee(route('clearances.show', $this->nbi), false);
    }

    public function test_a_withdrawn_application_shows_no_clearances_to_the_company(): void
    {
        $application = $this->apply($this->job);
        $this->actingAs($this->owner)->patch(route('applications.withdraw', $application));

        $this->actingAs($this->company->user)
            ->get(route('company.applications.show', $application))
            ->assertOk()
            ->assertDontSee('Open PDF')
            ->assertDontSee(route('clearances.show', $this->nbi), false);
    }

    // ---- helpers ----------------------------------------------------------------

    private function job(Company $company): JobPosting
    {
        return JobPosting::factory()->for($company)->create([
            'posted_by' => $company->user->id,
            'approval_status' => 'approved',
            'status' => 'open',
            'application_deadline' => now()->addWeeks(2),
            'slots' => 3,
        ]);
    }

    private function apply(JobPosting $job, bool $withCv = false): JobApplication
    {
        $profile = $this->owner->profile()->first();

        return JobApplication::create([
            'job_posting_id' => $job->id,
            'applicant_id' => $this->owner->id,
            'profile_id' => $profile->id,
            'cv_snapshot' => $withCv ? CvSnapshot::fromProfile($profile) : null,
            'date_applied' => now(),
            'status' => 'pending',
        ]);
    }
}
