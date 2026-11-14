<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class JobApplicationPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_only_the_applicant_and_the_owning_company_can_view_an_application(): void
    {
        [$application, $applicant, $company] = $this->application();

        $otherStudent = User::factory()->cvReady()->create();
        $otherCompany = Company::factory()->create()->user;
        $coordinator = User::factory()->jobCoordinator()->create();

        $this->assertTrue(Gate::forUser($applicant)->allows('view', $application));
        $this->assertTrue(Gate::forUser($company->user)->allows('view', $application));

        $this->assertFalse(Gate::forUser($otherStudent)->allows('view', $application));
        $this->assertFalse(Gate::forUser($otherCompany)->allows('view', $application));
        $this->assertFalse(Gate::forUser($coordinator)->allows('view', $application));
    }

    public function test_only_the_owning_company_can_review_an_application(): void
    {
        [$application, $applicant, $company] = $this->application();

        $otherCompany = Company::factory()->create()->user;
        $coordinator = User::factory()->jobCoordinator()->create();

        $this->assertTrue(Gate::forUser($company->user)->allows('review', $application));

        $this->assertFalse(Gate::forUser($applicant)->allows('review', $application));
        $this->assertFalse(Gate::forUser($otherCompany)->allows('review', $application));
        $this->assertFalse(Gate::forUser($coordinator)->allows('review', $application));
    }

    public function test_only_students_and_alumni_can_apply_and_only_to_an_open_job(): void
    {
        $student = User::factory()->cvReady()->create();
        $alumni = User::factory()->alumni()->cvReady()->create();
        $company = Company::factory()->create();
        $coordinator = User::factory()->jobCoordinator()->create();

        $open = $this->job($company);
        $closed = $this->job($company, ['status' => 'closed']);
        $expired = $this->job($company, ['application_deadline' => now()->subDay()]);
        $closesToday = $this->job($company, ['application_deadline' => now()->toDateString()]);

        $can = fn (User $user, JobPosting $job) => Gate::forUser($user)->allows('create', [JobApplication::class, $job]);

        $this->assertTrue($can($student, $open));
        $this->assertTrue($can($alumni, $open));
        $this->assertTrue($can($student, $closesToday));

        $this->assertFalse($can($company->user, $open));
        $this->assertFalse($can($coordinator, $open));
        $this->assertFalse($can($student, $closed));
        $this->assertFalse($can($student, $expired));
    }

    public function test_withdrawing_is_only_for_the_applicant_and_only_while_pending(): void
    {
        [$application, $applicant] = $this->application('pending');
        $otherStudent = User::factory()->cvReady()->create();

        $this->assertTrue(Gate::forUser($applicant)->allows('withdraw', $application));
        $this->assertFalse(Gate::forUser($otherStudent)->allows('withdraw', $application));

        foreach (['interview', 'rejected', 'withdrawn'] as $status) {
            $application->update(['status' => $status]);
            $this->assertFalse(
                Gate::forUser($applicant)->allows('withdraw', $application->fresh()),
                "A $status application must not be withdrawable."
            );
        }
    }

    public function test_hiding_is_only_for_the_applicant_and_only_once_it_has_ended(): void
    {
        [$application, $applicant] = $this->application('pending');
        $otherStudent = User::factory()->cvReady()->create();

        foreach (['pending', 'interview'] as $status) {
            $application->update(['status' => $status]);
            $this->assertFalse(Gate::forUser($applicant)->allows('hide', $application->fresh()));
        }

        foreach (['rejected', 'withdrawn'] as $status) {
            $application->update(['status' => $status]);
            $this->assertTrue(Gate::forUser($applicant)->allows('hide', $application->fresh()));
            $this->assertFalse(Gate::forUser($otherStudent)->allows('hide', $application->fresh()));
        }
    }

    // ---- the routes behind those rules ---------------------------------------

    public function test_another_student_cannot_withdraw_someone_elses_application(): void
    {
        [$application] = $this->application('pending');
        $otherStudent = User::factory()->cvReady()->create();

        $this->actingAs($otherStudent)
            ->patch(route('applications.withdraw', $application))
            ->assertForbidden();

        $this->assertSame('pending', $application->fresh()->status);
    }

    public function test_a_student_can_hide_a_finished_application_but_the_company_still_has_it(): void
    {
        [$application, $applicant, $company] = $this->application('rejected');

        $this->actingAs($applicant)
            ->patch(route('applications.hide', $application))
            ->assertRedirect(route('applications.index', ['tab' => 'past']));

        $this->assertNotNull($application->fresh()->hidden_by_applicant_at);

        // Gone from the student's list...
        $this->get(route('applications.index', ['tab' => 'past']))
            ->assertOk()
            ->assertDontSee($application->jobPosting->title);

        // ...but nothing was deleted: the company still sees it.
        $this->actingAs($company->user)
            ->get(route('company.applications.index', ['tab' => 'past']))
            ->assertOk()
            ->assertSee($application->applicantName());
    }

    public function test_an_application_that_is_still_active_cannot_be_hidden(): void
    {
        [$application, $applicant] = $this->application('pending');

        $this->actingAs($applicant)
            ->patch(route('applications.hide', $application))
            ->assertForbidden();

        $this->assertNull($application->fresh()->hidden_by_applicant_at);
    }

    // ---- helpers -------------------------------------------------------------

    /**
     * @return array{0: JobApplication, 1: User, 2: Company}
     */
    private function application(string $status = 'pending'): array
    {
        $company = Company::factory()->create();
        $job = $this->job($company);
        $applicant = User::factory()->cvReady()->create();

        $application = $job->applications()->create([
            'applicant_id' => $applicant->id,
            'date_applied' => now(),
            'status' => $status,
        ]);

        return [$application, $applicant, $company];
    }

    private function job(Company $company, array $overrides = []): JobPosting
    {
        return JobPosting::factory()->for($company)->create(array_merge([
            'approval_status' => 'approved',
            'status' => 'open',
        ], $overrides));
    }
}
