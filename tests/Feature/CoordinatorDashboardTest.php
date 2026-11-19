<?php

namespace Tests\Feature;

use App\Mail\ApplicantNotificationMail;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\Notification;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 15: the Coordinator dashboard no longer shows a rejected-applications count.
 * "Rejected" itself is still a real application status for companies and applicants.
 */
class CoordinatorDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $coordinator;
    private Company $company;
    private JobPosting $job;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Mail::fake();

        $this->coordinator = User::factory()->jobCoordinator()->create();
        $this->company = Company::factory()->create(['company_name' => 'Overview Corp']);
        $this->job = JobPosting::factory()->for($this->company)->create([
            'title' => 'Overview Test Job',
            'posted_by' => $this->company->user->id,
            'approval_status' => 'approved',
            'status' => 'open',
            'application_deadline' => now()->addWeeks(2),
            'slots' => 20,
        ]);
    }

    // ---- the count is gone -------------------------------------------------------

    public function test_the_overview_no_longer_has_a_rejected_count(): void
    {
        $this->applications(['pending' => 2, 'interview' => 1, 'rejected' => 3, 'withdrawn' => 1]);

        $response = $this->actingAs($this->coordinator)->get(route('coordinator.dashboard'))->assertOk();

        // Nothing is computed or passed to the page for it any more.
        $response->assertViewMissing('rejectedCount');
        $this->assertSame(2, $response->viewData('pendingCount'));
        $this->assertSame(1, $response->viewData('interviewCount'));
        $this->assertSame(3, $response->viewData('totalForRange'), 'only the applications still in play are counted');

        // The legend has Pending and Interview only.
        $response->assertSee('</i>Pending</span>', false)
            ->assertSee('</i>Interview</span>', false)
            ->assertDontSee('</i>Rejected</span>', false)
            ->assertSee('Active Applications');

        $overview = Str::between($response->getContent(), 'Application Overview', 'Quick Actions');
        $this->assertStringNotContainsStringIgnoringCase('rejected', $overview);

        $response->assertSee('(67%)', false)->assertSee('(33%)', false);
    }

    public function test_the_donut_has_two_slices_and_no_rejected_colour(): void
    {
        $this->applications(['pending' => 1, 'interview' => 1]);

        $response = $this->actingAs($this->coordinator)->get(route('coordinator.dashboard'))->assertOk();

        $response->assertSee('conic-gradient(var(--jl-slate) 0deg 180deg, var(--jl-gold) 180deg 360deg)', false);
        $this->assertDoesNotMatchRegularExpression('/conic-gradient\([^)]*jl-rose/', $response->getContent());
    }

    public function test_the_two_percentages_always_add_up_to_100(): void
    {
        // 1 of 8 is 12.5%: rounding both slices up would show 13% + 88% = 101%.
        $this->applications(['pending' => 1, 'interview' => 7]);

        $this->actingAs($this->coordinator)->get(route('coordinator.dashboard'))
            ->assertOk()
            ->assertSee('(13%)', false)
            ->assertSee('(87%)', false);
    }

    public function test_only_rejected_or_withdrawn_applications_leave_the_overview_empty(): void
    {
        $this->applications(['rejected' => 3, 'withdrawn' => 1]);

        $response = $this->actingAs($this->coordinator)->get(route('coordinator.dashboard'))->assertOk();

        $this->assertSame(0, $response->viewData('pendingCount'));
        $this->assertSame(0, $response->viewData('interviewCount'));
        $this->assertSame(0, $response->viewData('totalForRange'));
        $response->assertSee('(0%)', false);
    }

    // ---- everything else still works ---------------------------------------------

    public function test_the_other_dashboard_items_still_work(): void
    {
        $this->applications(['pending' => 2, 'interview' => 1, 'rejected' => 3, 'withdrawn' => 1]);

        $response = $this->actingAs($this->coordinator)->get(route('coordinator.dashboard'))->assertOk();

        foreach (['Total Students', 'Total Alumni', 'Total Companies', 'Jobs Posted', 'Applications Submitted', 'Recent Job Postings', 'Application Overview', 'Quick Actions'] as $label) {
            $response->assertSee($label);
        }

        $response->assertSee('Overview Test Job');
        $this->assertSame(1, $response->viewData('totalCompanies'));
        $this->assertSame(1, $response->viewData('totalJobsPosted'));

        // "Applications Submitted" is every application ever sent, rejected ones included.
        $this->assertSame(7, $response->viewData('totalApplications'));
    }

    public function test_the_date_range_still_filters_the_overview(): void
    {
        $this->applications(['pending' => 1]);                                  // today
        $this->applications(['interview' => 1], now()->subDays(10));            // ten days ago

        $this->actingAs($this->coordinator);

        $today = $this->get(route('coordinator.dashboard', ['range' => 'today']))->assertOk();
        $this->assertSame(1, $today->viewData('pendingCount'));
        $this->assertSame(0, $today->viewData('interviewCount'));

        $week = $this->get(route('coordinator.dashboard', ['range' => 'last_7_days']))->assertOk();
        $this->assertSame(1, $week->viewData('totalForRange'));

        $month = $this->get(route('coordinator.dashboard', ['range' => 'last_30_days']))->assertOk();
        $this->assertSame(2, $month->viewData('totalForRange'));

        $this->get(route('coordinator.dashboard', ['range' => 'this_month']))->assertOk();
        $this->get(route('coordinator.dashboard', ['range' => 'last_100_days']))->assertOk();
    }

    public function test_another_role_still_cannot_open_the_coordinator_dashboard(): void
    {
        $student = User::factory()->create(['activated_at' => now()]);

        // Whatever the app answers (a refusal or a redirect), the page itself is not served.
        $this->assertNotSame(200, $this->actingAs($student)->get(route('coordinator.dashboard'))->getStatusCode());
        $this->assertNotSame(200, $this->actingAs($this->company->user)->get(route('coordinator.dashboard'))->getStatusCode());
    }

    // ---- rejected is still a real status ------------------------------------------

    public function test_a_company_can_still_reject_an_application_and_the_applicant_is_told(): void
    {
        // The email goes to the personal email on the applicant's profile.
        $applicant = User::factory()->withProfile()->create(['activated_at' => now()]);
        $profile = $applicant->profile()->first();
        $profile->update(['personal_email' => 'rejected.applicant@example.com']);

        $application = JobApplication::create([
            'job_posting_id' => $this->job->id,
            'applicant_id' => $applicant->id,
            'profile_id' => $profile->id,
            'date_applied' => now(),
            'status' => 'pending',
        ]);

        $this->actingAs($this->company->user)
            ->patch(route('company.applications.status', $application), ['status' => 'rejected'])
            ->assertRedirect();

        $this->assertSame('rejected', $application->fresh()->status);

        $this->assertDatabaseHas('notifications', [
            'recipient_id' => $applicant->id,
            'related_application_id' => $application->id,
            'type' => 'rejection_notice',
        ]);
        $this->assertSame('Application Update', Notification::where('recipient_id', $applicant->id)->firstOrFail()->title);

        Mail::assertQueued(ApplicantNotificationMail::class, fn (ApplicantNotificationMail $mail) => $mail->hasTo('rejected.applicant@example.com'));
    }

    // ---- helpers -------------------------------------------------------------------

    /**
     * Create applications on the test job, each from its own applicant.
     *
     * @param  array<string,int>  $byStatus  e.g. ['pending' => 2, 'rejected' => 1]
     */
    private function applications(array $byStatus, $appliedAt = null): void
    {
        foreach ($byStatus as $status => $count) {
            for ($i = 0; $i < $count; $i++) {
                $applicant = User::factory()->create(['activated_at' => now()]);

                JobApplication::create([
                    'job_posting_id' => $this->job->id,
                    'applicant_id' => $applicant->id,
                    'profile_id' => null,
                    'date_applied' => $appliedAt ?? now(),
                    'status' => $status,
                ]);
            }
        }
    }
}
