<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 11: the partner company dashboard. Jobs and applicants are the company's
 * own only; the registered-accounts figure is one number with nothing behind it.
 */
class CompanyDashboardTest extends TestCase
{
    use RefreshDatabase;

    private Company $alpha;
    private Company $beta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->alpha = Company::factory()->create(['company_name' => 'Alpha Corp']);
        $this->beta = Company::factory()->create(['company_name' => 'Beta Corp']);
    }

    // ---- the page ----------------------------------------------------------

    public function test_a_company_gets_its_own_dashboard_with_every_part(): void
    {
        $this->actingAs($this->alpha->user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Welcome back, Alpha Corp')
            ->assertSee('Registered students and alumni')
            ->assertSee('Applicants on your jobs')
            ->assertSee('Your jobs')
            ->assertSee('Pending approval')
            ->assertSee('Not approved')
            ->assertSee('Your latest postings')
            ->assertSee('Recent applicants')
            ->assertSee('Post a new job')
            ->assertSee(route('jobs.create'))
            ->assertDontSee('Suggested jobs')
            ->assertDontSee('Profile completeness');
    }

    public function test_the_company_sidebar_has_the_dashboard_link(): void
    {
        $this->actingAs($this->alpha->user)->get(route('jobs.index'))
            ->assertOk()
            ->assertSee('bi-speedometer2', false);
    }

    public function test_a_company_with_nothing_yet_sees_empty_states_not_errors(): void
    {
        $response = $this->actingAs($this->alpha->user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('You have not posted a job yet')
            ->assertSee('No applicants yet')
            ->assertSee('Nobody is waiting for your review');

        $this->assertEquals(['pending' => 0, 'rejected' => 0, 'open' => 0, 'closed' => 0], $response->viewData('jobCounts'));
        $this->assertSame(0, $response->viewData('applicantsTotal'));
    }

    // ---- registered students and alumni -----------------------------------

    public function test_the_registered_figure_counts_active_students_and_alumni_only(): void
    {
        User::factory()->count(2)->create(['role' => 'student', 'activated_at' => now()]);
        User::factory()->alumni()->create(['activated_at' => now()]);
        User::factory()->create(['role' => 'student', 'activated_at' => null]);                     // roster row, not claimed yet
        User::factory()->alumni()->create(['activated_at' => now()])->delete(); // removed account
        User::factory()->jobCoordinator()->create(['activated_at' => now()]);  // not a student or alumni

        $response = $this->actingAs($this->alpha->user)->get(route('dashboard'))->assertOk();

        $this->assertSame(3, $response->viewData('registeredCount'));

        // The Coordinator dashboard's own definition of an active account.
        $this->assertSame(
            User::students()->whereNotNull('activated_at')->count() + User::alumni()->whereNotNull('activated_at')->count(),
            $response->viewData('registeredCount')
        );
    }

    public function test_the_registered_figure_shows_no_personal_details(): void
    {
        User::factory()->create(['role' => 'student', 'activated_at' => now(), 'full_name' => 'Zed Privatename', 'email' => 'zed.private@example.test']);
        $cv = User::factory()->cvReady()->create(['activated_at' => now()]);
        $cv->profile()->first()->update(['full_name' => 'Zed Profilename', 'personal_email' => 'zed.profile@example.test']);

        $this->actingAs($this->alpha->user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Zed Privatename')
            ->assertDontSee('zed.private@example.test')
            ->assertDontSee('Zed Profilename')
            ->assertDontSee('zed.profile@example.test');
    }

    // ---- jobs by status ----------------------------------------------------

    public function test_jobs_are_counted_by_status_for_this_company_only(): void
    {
        $this->job($this->alpha, ['title' => 'Alpha Open One']);
        $this->job($this->alpha, ['title' => 'Alpha Open Two']);
        $this->job($this->alpha, ['title' => 'Alpha Pending', 'approval_status' => 'pending']);
        $this->job($this->alpha, ['title' => 'Alpha Closed By Hand', 'status' => 'closed']);
        $this->job($this->alpha, ['title' => 'Alpha Expired', 'application_deadline' => now()->subDay()]);
        $this->job($this->alpha, ['title' => 'Alpha Rejected', 'approval_status' => 'rejected']);

        foreach (range(1, 3) as $i) {
            $this->job($this->beta, ['title' => "Beta Pending {$i}", 'approval_status' => 'pending']);
        }
        $this->job($this->beta, ['title' => 'Beta Open']);

        $response = $this->actingAs($this->alpha->user)->get(route('dashboard'))->assertOk();

        $this->assertEquals(['pending' => 1, 'rejected' => 1, 'open' => 2, 'closed' => 2], $response->viewData('jobCounts'));
        $this->assertSame(6, $response->viewData('totalJobs'));
        $response->assertSee('6 postings');

        // Each card opens Your Posted Jobs already filtered.
        foreach (['pending', 'open', 'closed', 'rejected'] as $state) {
            $response->assertSee(route('jobs.index', ['status' => $state]));
        }
    }

    public function test_nothing_in_the_request_can_show_another_companys_numbers(): void
    {
        $this->job($this->beta, ['title' => 'Beta Open']);
        $betaApplicant = $this->applicant('Hal Tester');
        $this->apply($betaApplicant, $this->job($this->beta, ['title' => 'Beta Second']));

        $response = $this->actingAs($this->alpha->user)
            ->get(route('dashboard', ['company_id' => $this->beta->id, 'company' => $this->beta->id]))
            ->assertOk()
            ->assertDontSee('Beta Open')
            ->assertDontSee('Hal Tester');

        $this->assertSame(0, $response->viewData('totalJobs'));
        $this->assertSame(0, $response->viewData('applicantsTotal'));
    }

    public function test_the_latest_five_postings_are_listed_newest_first_with_status_and_applicants(): void
    {
        foreach (range(1, 7) as $i) {
            $this->job($this->alpha, [
                'title' => "Alpha Posting {$i}",
                'date_posted' => now()->subDays(20 - $i)->toDateString(),
            ]);
        }
        $this->job($this->beta, ['title' => 'Beta Posting', 'date_posted' => now()->toDateString()]);

        $pending = $this->job($this->alpha, [
            'title' => 'Alpha Newest Pending',
            'approval_status' => 'pending',
            'date_posted' => now()->toDateString(),
        ]);
        $this->apply($this->applicant('Jo Tester'), $pending);

        $response = $this->actingAs($this->alpha->user)->get(route('dashboard'))->assertOk();

        $this->assertSame(
            ['Alpha Newest Pending', 'Alpha Posting 7', 'Alpha Posting 6', 'Alpha Posting 5', 'Alpha Posting 4'],
            $response->viewData('latestJobs')->pluck('title')->all()
        );

        $response->assertSee('Alpha Newest Pending')
            ->assertSee('Pending approval')
            ->assertSee('1 applicant')
            ->assertSee('Alpha Posting 4')
            ->assertDontSee('Alpha Posting 3')
            ->assertDontSee('Beta Posting');
    }

    // ---- applicants --------------------------------------------------------

    public function test_applicants_are_counted_without_withdrawn_and_without_other_companies(): void
    {
        $job = $this->job($this->alpha, ['title' => 'Alpha Open']);
        $betaJob = $this->job($this->beta, ['title' => 'Beta Open']);

        foreach (['Ana' => 'pending', 'Ben' => 'pending', 'Cara' => 'interview', 'Dino' => 'rejected', 'Eli' => 'pending', 'Fay' => 'interview', 'Gus' => 'withdrawn'] as $name => $status) {
            $this->apply($this->applicant("{$name} Tester"), $job, $status);
        }
        $this->apply($this->applicant('Hal Tester'), $betaJob, 'pending');

        $response = $this->actingAs($this->alpha->user)->get(route('dashboard'))->assertOk();

        $this->assertSame(6, $response->viewData('applicantsTotal'));   // Gus withdrew, Hal is Beta's
        $this->assertSame(3, $response->viewData('waitingCount'));      // the pending ones

        $response->assertSee('Applicants on your jobs')
            ->assertSee('3 waiting for your review')
            ->assertSee(route('company.applications.index'));
    }

    public function test_the_five_most_recent_applicants_link_to_their_application(): void
    {
        $job = $this->job($this->alpha, ['title' => 'Alpha Open']);
        $betaJob = $this->job($this->beta, ['title' => 'Beta Open']);

        $applications = [];
        $days = ['Ana' => 7, 'Ben' => 6, 'Cara' => 5, 'Dino' => 4, 'Eli' => 3, 'Fay' => 2];
        foreach ($days as $name => $daysAgo) {
            $applications[$name] = $this->apply($this->applicant("{$name} Tester"), $job, 'pending', ['date_applied' => now()->subDays($daysAgo)]);
        }
        // Newest of all, but withdrawn: not shown. Another company's applicant: not shown.
        $this->apply($this->applicant('Gus Tester'), $job, 'withdrawn', ['date_applied' => now()->subHour()]);
        $this->apply($this->applicant('Hal Tester'), $betaJob, 'pending', ['date_applied' => now()]);

        $response = $this->actingAs($this->alpha->user)->get(route('dashboard'))->assertOk();

        $this->assertSame(
            [$applications['Fay']->id, $applications['Eli']->id, $applications['Dino']->id, $applications['Cara']->id, $applications['Ben']->id],
            $response->viewData('recentApplicants')->pluck('id')->all()
        );

        $response->assertSee('Fay Tester')
            ->assertSee('Ben Tester')
            ->assertSee(route('company.applications.show', $applications['Fay']))
            ->assertDontSee('Ana Tester')
            ->assertDontSee('Gus Tester')
            ->assertDontSee('Hal Tester');
    }

    public function test_applicants_of_a_removed_job_are_still_counted_like_the_applicants_page(): void
    {
        $job = $this->job($this->alpha, ['title' => 'Removed Posting']);
        $this->apply($this->applicant('Ivy Tester'), $job, 'pending');
        $job->delete();

        $this->actingAs($this->alpha->user);

        $dashboard = $this->get(route('dashboard'))->assertOk()->assertSee('Ivy Tester');

        // The removed job is not one of the company's postings any more ...
        $this->assertSame(0, $dashboard->viewData('totalJobs'));
        $this->assertCount(0, $dashboard->viewData('latestJobs'));

        // ... but its applicant is still there to review, as on the Applicants page.
        $applicantsPage = $this->get(route('company.applications.index'))->assertOk();
        $this->assertSame(1, $dashboard->viewData('applicantsTotal'));
        $this->assertSame($dashboard->viewData('applicantsTotal'), $applicantsPage->viewData('activeCount'));
    }

    public function test_a_removed_applicant_account_does_not_break_the_list(): void
    {
        $job = $this->job($this->alpha, ['title' => 'Alpha Open']);
        $gone = $this->applicant('Kit Tester');
        $this->apply($gone, $job, 'pending');
        $gone->delete();

        $this->actingAs($this->alpha->user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Account removed');
    }

    // ---- helpers -----------------------------------------------------------

    /**
     * A posting of $company, posted by the company's own login, approved and open
     * unless the overrides say otherwise.
     */
    private function job(Company $company, array $overrides = []): JobPosting
    {
        return JobPosting::factory()->for($company)->create(array_merge([
            'posted_by' => $company->user->id,
            'approval_status' => 'approved',
            'status' => 'open',
            'application_deadline' => now()->addWeeks(2),
        ], $overrides));
    }

    /**
     * An active student with a ready CV and the given name on the profile.
     */
    private function applicant(string $name): User
    {
        $user = User::factory()->cvReady()->create(['activated_at' => now()]);
        $user->profile()->first()->update(['full_name' => $name]);

        return $user->fresh();
    }

    private function apply(User $applicant, JobPosting $job, string $status = 'pending', array $overrides = []): JobApplication
    {
        return JobApplication::create(array_merge([
            'job_posting_id' => $job->id,
            'applicant_id' => $applicant->id,
            'profile_id' => $applicant->profile()->first()?->id,
            'date_applied' => now(),
            'status' => $status,
        ], $overrides));
    }
}
