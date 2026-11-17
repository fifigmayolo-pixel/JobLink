<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobPosting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 9: a company sees and manages only the jobs of its own company.
 * Students and alumni still see every company's approved, open jobs, and the
 * Job Coordinator keeps the full view and the approval flow.
 */
class CompanyOwnJobsTest extends TestCase
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

    // ---- the list ---------------------------------------------------------

    public function test_a_company_sees_only_its_own_jobs_in_every_status(): void
    {
        $coordinator = User::factory()->jobCoordinator()->create();

        $this->job($this->alpha, ['title' => 'Alpha Open Role']);
        $this->job($this->alpha, ['title' => 'Alpha Pending Role', 'approval_status' => 'pending']);
        $this->job($this->alpha, ['title' => 'Alpha Rejected Role', 'approval_status' => 'rejected', 'rejection_reason' => 'Salary range missing']);
        $this->job($this->alpha, ['title' => 'Alpha Closed Role', 'status' => 'closed']);
        $this->job($this->alpha, ['title' => 'Alpha Expired Role', 'application_deadline' => now()->subDay()]);
        // Posted by the Coordinator and tagged to Alpha: still Alpha's job.
        $this->job($this->alpha, ['title' => 'Alpha Tagged Role', 'posted_by' => $coordinator->id]);

        $this->job($this->beta, ['title' => 'Beta Open Role']);
        $this->job($this->beta, ['title' => 'Beta Pending Role', 'approval_status' => 'pending']);

        $this->actingAs($this->alpha->user)->get(route('jobs.index'))
            ->assertOk()
            ->assertSee('Your Posted Jobs')
            ->assertSee('6 postings')
            ->assertSee('Alpha Open Role')
            ->assertSee('Alpha Pending Role')
            ->assertSee('Waiting for the Job Coordinator')
            ->assertSee('Alpha Rejected Role')
            ->assertSee('Salary range missing')
            ->assertSee('Alpha Closed Role')
            ->assertSee('Alpha Expired Role')
            ->assertSee('deadline passed')
            ->assertSee('Alpha Tagged Role')
            ->assertDontSee('Beta Open Role')
            ->assertDontSee('Beta Pending Role');
    }

    public function test_a_company_with_no_jobs_sees_an_empty_state(): void
    {
        $this->job($this->beta, ['title' => 'Beta Open Role']);

        $this->actingAs($this->alpha->user)->get(route('jobs.index'))
            ->assertOk()
            ->assertSee('You have not posted a job yet')
            ->assertDontSee('Beta Open Role');
    }

    public function test_the_company_list_can_be_filtered_by_status_and_title(): void
    {
        $this->job($this->alpha, ['title' => 'Alpha Open Role']);
        $this->job($this->alpha, ['title' => 'Alpha Pending Role', 'approval_status' => 'pending']);
        $this->job($this->alpha, ['title' => 'Alpha Closed Role', 'status' => 'closed']);
        $this->job($this->beta, ['title' => 'Beta Pending Role', 'approval_status' => 'pending']);

        $this->actingAs($this->alpha->user);

        $this->get(route('jobs.index', ['status' => 'pending']))
            ->assertSee('Alpha Pending Role')
            ->assertDontSee('Alpha Open Role')
            ->assertDontSee('Alpha Closed Role')
            ->assertDontSee('Beta Pending Role');

        $this->get(route('jobs.index', ['status' => 'open']))
            ->assertSee('Alpha Open Role')
            ->assertDontSee('Alpha Pending Role')
            ->assertDontSee('Alpha Closed Role');

        $this->get(route('jobs.index', ['status' => 'closed']))
            ->assertSee('Alpha Closed Role')
            ->assertDontSee('Alpha Open Role');

        $this->get(route('jobs.index', ['search' => 'Closed']))
            ->assertSee('Alpha Closed Role')
            ->assertDontSee('Alpha Open Role');

        // A made-up status is ignored, and can never widen the list to another company.
        $this->get(route('jobs.index', ['status' => 'everything']))
            ->assertSee('Alpha Open Role')
            ->assertDontSee('Beta Pending Role');

        $this->get(route('jobs.index', ['search' => 'zzzz-no-such-job']))
            ->assertOk()
            ->assertSee('No postings match your filters');
    }

    public function test_a_missing_company_matches_nothing(): void
    {
        $this->job($this->alpha, ['title' => 'Alpha Open Role']);
        $this->job($this->beta, ['title' => 'Beta Open Role']);

        // A login with no company record must never be handed anyone's postings.
        $this->assertSame(0, JobPosting::query()->ofCompany(null)->count());
        $this->assertSame(1, JobPosting::query()->ofCompany($this->alpha->id)->count());
    }

    // ---- other companies' jobs by URL -------------------------------------

    public function test_a_company_cannot_open_another_companys_job_by_url(): void
    {
        $betaOpen = $this->job($this->beta, ['title' => 'Beta Open Role']);
        $betaPending = $this->job($this->beta, ['title' => 'Beta Pending Role', 'approval_status' => 'pending']);
        $alphaOwn = $this->job($this->alpha, ['title' => 'Alpha Open Role']);

        $this->actingAs($this->alpha->user);

        // Even an approved, open posting that every student can see.
        $this->get(route('jobs.show', $betaOpen))->assertNotFound();
        $this->get(route('jobs.show', $betaPending))->assertNotFound();
        $this->get(route('jobs.edit', $betaOpen))->assertNotFound();

        $this->get(route('jobs.show', $alphaOwn))
            ->assertOk()
            ->assertSee('Alpha Open Role')
            ->assertSee('Back to your jobs');
    }

    public function test_a_company_cannot_edit_close_or_delete_another_companys_job(): void
    {
        $betaJob = $this->job($this->beta, ['title' => 'Beta Open Role']);

        $this->actingAs($this->alpha->user);

        $this->get(route('jobs.edit', $betaJob))->assertNotFound();

        // Closing is an update. The request also tries to move the job to Alpha.
        $update = $this->patch(route('jobs.update', $betaJob), $this->payload([
            'title' => 'Hijacked Title',
            'status' => 'closed',
            'company_id' => $this->alpha->id,
        ]));
        $this->assertContains($update->getStatusCode(), [403, 404]);

        $this->delete(route('jobs.destroy', $betaJob))->assertNotFound();

        $betaJob->refresh();
        $this->assertSame('Beta Open Role', $betaJob->title);
        $this->assertSame('open', $betaJob->status);
        $this->assertEquals($this->beta->id, $betaJob->company_id);
        $this->assertNotSoftDeleted($betaJob);
    }

    public function test_a_company_can_still_edit_close_and_delete_its_own_job_but_not_move_it(): void
    {
        $job = $this->job($this->alpha, ['title' => 'Alpha Open Role']);

        $this->actingAs($this->alpha->user);

        $this->get(route('jobs.edit', $job))->assertOk();

        $this->patch(route('jobs.update', $job), $this->payload([
            'title' => 'Alpha Renamed Role',
            'status' => 'closed',
            'company_id' => $this->beta->id, // ignored
        ]))->assertSessionHasNoErrors()->assertRedirect(route('jobs.show', $job));

        $job->refresh();
        $this->assertSame('Alpha Renamed Role', $job->title);
        $this->assertSame('closed', $job->status);
        $this->assertEquals($this->alpha->id, $job->company_id);

        $this->delete(route('jobs.destroy', $job))->assertRedirect(route('jobs.index'));
        $this->assertSoftDeleted($job);
    }

    public function test_a_company_can_open_a_job_the_coordinator_tagged_to_it_but_not_change_it(): void
    {
        $coordinator = User::factory()->jobCoordinator()->create();
        $tagged = $this->job($this->alpha, ['title' => 'Alpha Tagged Role', 'posted_by' => $coordinator->id]);

        $this->actingAs($this->alpha->user);

        $this->get(route('jobs.show', $tagged))->assertOk()->assertSee('Alpha Tagged Role');
        $this->get(route('jobs.edit', $tagged))->assertForbidden();
        $this->delete(route('jobs.destroy', $tagged))->assertForbidden();

        $this->assertNotSoftDeleted($tagged);
    }

    // ---- students, alumni and the Coordinator -----------------------------

    public function test_students_and_alumni_still_see_every_companys_approved_open_jobs(): void
    {
        $this->job($this->alpha, ['title' => 'Alpha Open Role']);
        $this->job($this->beta, ['title' => 'Beta Open Role']);
        $this->job($this->beta, ['title' => 'Beta Pending Role', 'approval_status' => 'pending']);

        foreach ([User::factory()->cvReady()->create(), User::factory()->alumni()->cvReady()->create()] as $user) {
            $this->actingAs($user)->get(route('jobs.index'))
                ->assertOk()
                ->assertSee('2 open positions')
                ->assertSee('Alpha Open Role')
                ->assertSee('Beta Open Role')
                ->assertDontSee('Beta Pending Role')
                ->assertDontSee('Your Posted Jobs');
        }
    }

    public function test_the_coordinator_still_sees_the_full_listing_and_can_open_any_posting(): void
    {
        $coordinator = User::factory()->jobCoordinator()->create();
        $this->job($this->alpha, ['title' => 'Alpha Open Role']);
        $this->job($this->beta, ['title' => 'Beta Open Role']);
        $betaPending = $this->job($this->beta, ['title' => 'Beta Pending Role', 'approval_status' => 'pending']);

        $this->actingAs($coordinator)->get(route('jobs.index'))
            ->assertOk()
            ->assertSee('Alpha Open Role')
            ->assertSee('Beta Open Role')
            ->assertDontSee('Your Posted Jobs');

        $this->get(route('jobs.show', $betaPending))->assertOk()->assertSee('Beta Pending Role');
        $this->get(route('jobs.edit', $betaPending))->assertOk();
    }

    public function test_the_coordinator_approval_flow_still_works(): void
    {
        $coordinator = User::factory()->jobCoordinator()->create();
        $pending = $this->job($this->alpha, ['title' => 'Alpha Pending Role', 'approval_status' => 'pending']);

        $this->actingAs($coordinator)
            ->patch(route('coordinator.job-approvals.approve', $pending))
            ->assertSessionHasNoErrors();

        $this->assertTrue($pending->fresh()->isApproved());

        // A company cannot approve its own posting.
        $another = $this->job($this->alpha, ['title' => 'Alpha Second Pending', 'approval_status' => 'pending']);
        $this->actingAs($this->alpha->user)
            ->patch(route('coordinator.job-approvals.approve', $another))
            ->assertForbidden();
        $this->assertTrue($another->fresh()->isPending());
    }

    // ---- helpers ----------------------------------------------------------

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
        ], $overrides));
    }

    /**
     * What the Edit Job form submits.
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Updated Title',
            'job_type' => 'full_time',
            'status' => 'open',
            'location' => 'Alaminos City, Pangasinan',
            'application_deadline' => now()->addWeeks(2)->format('Y-m-d'),
            'description' => "First line of the description.\nSecond line of the description.",
            'qualifications' => 'Graduate of a four-year course.',
            'requirements' => 'A complete CV profile on JobLink.',
        ], $overrides);
    }
}