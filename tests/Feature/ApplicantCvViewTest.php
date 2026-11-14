<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicantCvViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    // ---- the company's view -------------------------------------------------

    public function test_the_company_sees_the_cv_the_student_submitted(): void
    {
        [$student, $company, $application] = $this->applied();

        $this->actingAs($company->user)
            ->get(route('company.applications.show', $application))
            ->assertOk()
            ->assertSee($student->profile()->first()->full_name)
            ->assertSee('Fresh graduate')
            ->assertSee('Communication')
            ->assertSee('jane@example.com')
            ->assertSee('09171234567')
            ->assertSee(route('company.applications.cv', $application))
            // Private details never reach the company.
            ->assertDontSee('MARKER-STREET')
            ->assertDontSee('MARKER-GENDER')
            ->assertDontSee('MARKER-CIVIL');
    }

    public function test_the_company_sees_the_cv_as_it_was_not_as_it_is_now(): void
    {
        [$student, $company, $application] = $this->applied();

        $student->profile()->first()->update(['cv_summary' => 'Rewritten after applying, the company must not see this.']);

        $this->actingAs($company->user)
            ->get(route('company.applications.show', $application))
            ->assertOk()
            ->assertSee('Fresh graduate')
            ->assertDontSee('Rewritten after applying');
    }

    public function test_the_printable_page_opens_for_the_owning_company(): void
    {
        [, $company, $application] = $this->applied();

        $this->actingAs($company->user)
            ->get(route('company.applications.cv', $application))
            ->assertOk()
            ->assertSee('Print')
            ->assertSee('Fresh graduate')
            ->assertSee('Communication')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_another_company_cannot_open_the_cv(): void
    {
        [, , $application] = $this->applied();
        $other = Company::factory()->create();

        $this->actingAs($other->user)
            ->get(route('company.applications.show', $application))
            ->assertForbidden();

        $this->actingAs($other->user)
            ->get(route('company.applications.cv', $application))
            ->assertForbidden();
    }

    public function test_the_coordinator_cannot_open_an_applicants_cv(): void
    {
        [, , $application] = $this->applied();
        $coordinator = User::factory()->jobCoordinator()->create();

        $this->actingAs($coordinator)
            ->get(route('company.applications.cv', $application))
            ->assertForbidden();
    }

    public function test_a_withdrawn_application_has_no_cv_left(): void
    {
        [$student, $company, $application] = $this->applied();

        $this->actingAs($student)->patch(route('applications.withdraw', $application));

        $this->actingAs($company->user)
            ->get(route('company.applications.show', $application))
            ->assertOk()
            ->assertSee('withdrew this application')
            ->assertDontSee('Fresh graduate');

        $this->get(route('company.applications.cv', $application))->assertNotFound();

        $this->actingAs($student)->get(route('applications.cv', $application))->assertNotFound();
    }

    public function test_an_application_from_before_the_cv_system_says_no_cv_is_on_file(): void
    {
        $company = Company::factory()->create();
        $job = $this->openJobFor($company);
        $student = User::factory()->withProfile()->create();
        $application = $job->applications()->create([
            'applicant_id' => $student->id,
            'date_applied' => now(),
            'status' => 'pending',
        ]);

        $this->actingAs($company->user)
            ->get(route('company.applications.show', $application))
            ->assertOk()
            ->assertSee('submitted before the CV system');

        $this->get(route('company.applications.cv', $application))->assertNotFound();
    }

    public function test_everything_in_a_cv_is_escaped_and_unsafe_links_are_not_drawn(): void
    {
        $company = Company::factory()->create();
        $job = $this->openJobFor($company);
        $student = User::factory()->withProfile()->create();

        // A small, hostile snapshot with most keys missing on purpose.
        $application = $job->applications()->create([
            'applicant_id' => $student->id,
            'date_applied' => now(),
            'status' => 'pending',
            'cv_snapshot' => [
                'v' => 1,
                'personal' => ['full_name' => 'Test Person'],
                'summary' => '<b>bold</b>',
                'skills' => ['<script>alert(1)</script>'],
                'links' => ['linkedin' => 'javascript:alert(1)', 'portfolio' => 'https://example.com'],
                'experiences' => [[
                    'position' => '<i>Boss</i>',
                    'organization' => 'Org',
                    'start_date' => '2025-01-01',
                    'end_date' => null,
                    'description' => "line1\nline2",
                ]],
            ],
        ]);

        $this->actingAs($company->user)
            ->get(route('company.applications.show', $application))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<b>bold</b>', false)
            ->assertDontSee('javascript:alert', false)
            ->assertSee('https://example.com', false)
            ->assertSee('Present');
    }

    // ---- the student's own copy ---------------------------------------------

    public function test_the_student_can_see_the_cv_they_sent_and_nobody_else_can(): void
    {
        [$student, , $application] = $this->applied();

        $this->actingAs($student)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->assertSee('View the CV I sent');

        $this->actingAs($student)
            ->get(route('applications.cv', $application))
            ->assertOk()
            ->assertSee('The CV I sent')
            ->assertSee('Fresh graduate');

        $other = User::factory()->cvReady()->create();
        $this->actingAs($other)->get(route('applications.cv', $application))->assertForbidden();
    }

    // ---- helpers ------------------------------------------------------------

    /**
     * A real application made the real way: the student applies through the form
     * route, so the snapshot is built by the same code the app uses.
     *
     * @return array{0: User, 1: Company, 2: JobApplication}
     */
    private function applied(): array
    {
        $company = Company::factory()->create();
        $job = $this->openJobFor($company);

        $student = User::factory()->cvReady()->create();
        $student->profile()->first()->update([
            'house_street' => 'MARKER-STREET 9',
            'gender' => 'MARKER-GENDER',
            'civil_status' => 'MARKER-CIVIL',
            'personal_email' => 'jane@example.com',
            'mobile_number' => '09171234567',
        ]);

        $this->actingAs($student)->post(route('applications.store', $job))->assertSessionHasNoErrors();

        return [$student, $company, $job->applications()->first()];
    }

    private function openJobFor(Company $company): JobPosting
    {
        return JobPosting::factory()->for($company)->create([
            'approval_status' => 'approved',
            'status' => 'open',
        ]);
    }
}
