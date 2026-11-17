<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\Notification;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 10: the student and alumni dashboard. A student sees only their own
 * applications and notifications, suggestions come from a plain keyword match,
 * and the company and Coordinator redirects are unchanged.
 */
class StudentDashboardTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->company = Company::factory()->create(['company_name' => 'Alpha Corp']);
    }

    // ---- who gets what -----------------------------------------------------

    public function test_a_guest_is_sent_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_the_coordinator_keeps_their_own_landing_page(): void
    {
        $this->actingAs(User::factory()->jobCoordinator()->create())->get(route('dashboard'))
            ->assertRedirect(route('coordinator.dashboard'));
    }

    public function test_a_company_does_not_get_the_student_dashboard(): void
    {
        // Phase 11 gives companies a dashboard of their own (CompanyDashboardTest).
        $this->actingAs($this->company->user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Suggested jobs based on your profile')
            ->assertDontSee('Profile completeness');
    }

    public function test_students_and_alumni_see_every_part_of_the_dashboard(): void
    {
        foreach ([$this->student(), $this->student(alumni: true)] as $user) {
            $this->actingAs($user)->get(route('dashboard'))
                ->assertOk()
                ->assertSee('Dashboard')
                ->assertSee('Profile completeness')
                ->assertSee('Your applications')
                ->assertSee('Pending')
                ->assertSee('Interview')
                ->assertSee('Rejected')
                ->assertSee('Withdrawn')
                ->assertSee('Latest applications')
                ->assertSee('Recent notifications')
                ->assertSee('Suggested jobs based on your profile')
                ->assertDontSee('AI matching');
        }
    }

    public function test_students_and_alumni_have_the_dashboard_link_in_the_sidebar(): void
    {
        $this->actingAs($this->student())->get(route('dashboard'))->assertSee('bi-speedometer2', false);
        $this->actingAs($this->student(alumni: true))->get(route('jobs.index'))->assertSee('bi-speedometer2', false);
    }

    // ---- profile completeness ----------------------------------------------

    public function test_a_ready_cv_shows_its_percent_and_a_way_to_improve_it(): void
    {
        $student = $this->student();
        $profile = $student->profile()->first();
        $percent = $profile->loadCv()->cvCompleteness()['percent'];

        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertSee($percent.'%')
            ->assertSee('role="progressbar"', false)
            ->assertSee('aria-valuenow="'.$percent.'"', false)
            ->assertSee(route('cv.edit'));
    }

    public function test_a_cv_that_is_not_ready_says_what_is_missing_and_shows_no_suggestions(): void
    {
        $this->job($this->company, ['title' => 'Bookkeeping Assistant']);

        $student = $this->student(skills: ['Bookkeeping']);
        $student->profile()->first()->skills()->delete();

        $this->actingAs($student->fresh())->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Your CV is not ready to apply with yet')
            ->assertSee('Skills')
            ->assertSee('Finish your CV to see suggested jobs')
            ->assertSee('Finish my CV')
            ->assertDontSee('Bookkeeping Assistant');
    }

    public function test_a_student_whose_basic_profile_is_not_done_never_breaks_the_page(): void
    {
        $student = $this->student();
        $student->profile()->first()->update(['profile_completed_at' => null]);

        $response = $this->actingAs($student->fresh())->get(route('dashboard'));

        // The app may send such a student to finish the profile first, or the page
        // may show that prompt itself. Either way: no error, and the way forward.
        if ($response->isRedirect()) {
            $response->assertRedirect(route('profile.complete'));
        } else {
            $response->assertOk()->assertSee('Complete my profile');
        }
    }

    // ---- applications ------------------------------------------------------

    public function test_the_counts_are_the_students_own_and_match_my_applications(): void
    {
        $student = $this->student();
        $other = $this->student();

        foreach (['pending', 'pending', 'interview', 'rejected', 'withdrawn'] as $status) {
            $this->apply($student, $status);
        }
        // Removed from the student's own list: not counted, like on My Applications.
        $this->apply($student, 'rejected', ['hidden_by_applicant_at' => now()]);
        // Someone else's applications never count.
        foreach (['pending', 'pending', 'pending', 'interview'] as $status) {
            $this->apply($other, $status);
        }

        $response = $this->actingAs($student)->get(route('dashboard'))->assertOk();

        $this->assertSame(
            ['pending' => 2, 'interview' => 1, 'rejected' => 1, 'withdrawn' => 1],
            $response->viewData('statusCounts')
        );
        $this->assertSame(5, $response->viewData('totalApplications'));
        $response->assertSee('5 applications');

        // Each card leads to the matching My Applications tab.
        $response->assertSee(route('applications.index', ['tab' => 'active']))
            ->assertSee(route('applications.index', ['tab' => 'past']));
    }

    public function test_the_latest_five_applications_are_listed_newest_first_with_a_link_each(): void
    {
        $student = $this->student();
        $other = $this->student();

        $applications = [];
        foreach (range(1, 7) as $i) {
            $applications[$i] = $this->apply($student, 'pending', ['date_applied' => now()->subDays(10 - $i)], ['title' => "Own Job {$i}"]);
        }
        $this->apply($other, 'pending', ['date_applied' => now()], ['title' => 'Somebody Elses Job']);

        $response = $this->actingAs($student)->get(route('dashboard'))->assertOk();

        $this->assertSame(
            [$applications[7]->id, $applications[6]->id, $applications[5]->id, $applications[4]->id, $applications[3]->id],
            $response->viewData('latestApplications')->pluck('id')->all()
        );

        $response->assertSee('Own Job 7')
            ->assertSee(route('applications.show', $applications[7]))
            ->assertDontSee('Own Job 1')
            ->assertDontSee('Own Job 2')
            ->assertDontSee('Somebody Elses Job');
    }

    public function test_with_no_applications_the_page_says_so(): void
    {
        $this->actingAs($this->student())->get(route('dashboard'))
            ->assertOk()
            ->assertSee('You have not applied to anything yet')
            ->assertSee('0 applications');
    }

    public function test_a_removed_job_does_not_break_the_latest_list(): void
    {
        $student = $this->student();
        $application = $this->apply($student, 'pending', [], ['title' => 'Gone Soon']);
        $application->jobPosting->delete();

        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Gone Soon')
            ->assertSee(route('applications.show', $application));
    }

    // ---- notifications -----------------------------------------------------

    public function test_the_panel_shows_the_five_newest_own_notifications_and_counts_every_unread(): void
    {
        $student = $this->student();
        $other = $this->student();

        foreach (range(1, 7) as $i) {
            $this->notify($student, "Own note {$i}", minutesAgo: 70 - $i, read: $i > 4);
        }
        $this->notify($other, 'Somebody Elses Note', minutesAgo: 1);

        $response = $this->actingAs($student)->get(route('dashboard'))->assertOk();

        $response->assertSee('Own note 7')
            ->assertSee('Own note 3')
            ->assertDontSee('Own note 2')
            ->assertDontSee('Own note 1')
            ->assertDontSee('Somebody Elses Note')
            ->assertSee(route('notifications.index'))
            ->assertSee('Mark all as read');

        // Notes 1 to 4 are unread; two of them are not in the five shown.
        $this->assertSame(4, $response->viewData('unreadCount'));
        $response->assertSee('4 new');
    }

    public function test_with_no_unread_notifications_there_is_no_mark_all_button(): void
    {
        $student = $this->student();
        $this->notify($student, 'Already read', minutesAgo: 5, read: true);

        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Already read')
            ->assertDontSee('Mark all as read');

        $this->actingAs($this->student())->get(route('dashboard'))->assertSee('No notifications yet');
    }

    // ---- suggested jobs ----------------------------------------------------

    public function test_suggestions_match_skills_course_and_titles_and_rank_the_best_first(): void
    {
        $student = $this->student(course: 'Bachelor of Science in Accountancy', skills: ['Bookkeeping', 'Excel']);

        $this->job($this->company, ['title' => 'Accountancy Staff']);                                 // course in title: 3
        $this->job($this->company, ['title' => 'Bookkeeping Assistant', 'requirements' => 'Excel']);  // 3 + 2
        $this->job($this->company, ['title' => 'Welder', 'description' => 'Steel work.']);            // nothing

        $response = $this->actingAs($student)->get(route('dashboard'))->assertOk();

        $this->assertSame(
            ['Bookkeeping Assistant', 'Accountancy Staff'],
            $response->viewData('suggestions')->pluck('job.title')->all()
        );

        $response->assertSee('Suggested jobs based on your profile')
            ->assertSee('Bookkeeping Assistant')
            ->assertSee('Accountancy Staff')
            ->assertDontSee('Welder')
            ->assertSee('Matches:');
    }

    public function test_suggestions_include_every_companys_open_approved_jobs_and_nothing_else(): void
    {
        $student = $this->student(skills: ['Bookkeeping']);
        $beta = Company::factory()->create(['company_name' => 'Beta Corp']);

        $this->job($this->company, ['title' => 'Bookkeeping Clerk Alpha']);
        $this->job($beta, ['title' => 'Bookkeeping Clerk Beta']);
        $this->job($beta, ['title' => 'Bookkeeping Clerk Pending', 'approval_status' => 'pending']);
        $this->job($beta, ['title' => 'Bookkeeping Clerk Rejected', 'approval_status' => 'rejected']);
        $this->job($beta, ['title' => 'Bookkeeping Clerk Closed', 'status' => 'closed']);
        $this->job($beta, ['title' => 'Bookkeeping Clerk Expired', 'application_deadline' => now()->subDay()]);

        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Bookkeeping Clerk Alpha')
            ->assertSee('Bookkeeping Clerk Beta')
            ->assertDontSee('Bookkeeping Clerk Pending')
            ->assertDontSee('Bookkeeping Clerk Rejected')
            ->assertDontSee('Bookkeeping Clerk Closed')
            ->assertDontSee('Bookkeeping Clerk Expired');
    }

    public function test_a_job_already_applied_to_is_left_out_but_a_withdrawn_one_comes_back(): void
    {
        $student = $this->student(skills: ['Bookkeeping']);

        $applied = $this->job($this->company, ['title' => 'Bookkeeping Applied']);
        $withdrawn = $this->job($this->company, ['title' => 'Bookkeeping Withdrawn']);
        $this->job($this->company, ['title' => 'Bookkeeping Fresh']);

        $this->applyTo($student, $applied, 'pending');
        $this->applyTo($student, $withdrawn, 'withdrawn');

        $response = $this->actingAs($student)->get(route('dashboard'))->assertOk();

        $titles = $response->viewData('suggestions')->pluck('job.title')->all();
        $this->assertContains('Bookkeeping Fresh', $titles);
        $this->assertContains('Bookkeeping Withdrawn', $titles);
        $this->assertNotContains('Bookkeeping Applied', $titles);
    }

    public function test_only_the_top_five_are_shown_and_a_tie_goes_to_the_newest(): void
    {
        $student = $this->student(skills: ['Bookkeeping']);

        foreach (range(1, 7) as $i) {
            $this->job($this->company, [
                'title' => "Bookkeeping Role {$i}",
                'date_posted' => now()->subDays(20 - $i)->toDateString(),
            ]);
        }

        $titles = $this->actingAs($student)->get(route('dashboard'))
            ->viewData('suggestions')->pluck('job.title')->all();

        $this->assertSame(
            ['Bookkeeping Role 7', 'Bookkeeping Role 6', 'Bookkeeping Role 5', 'Bookkeeping Role 4', 'Bookkeeping Role 3'],
            $titles
        );
    }

    public function test_one_passing_mention_in_a_description_is_not_enough(): void
    {
        $student = $this->student(skills: ['Bookkeeping']);

        $this->job($this->company, ['title' => 'Store Helper', 'description' => 'Some bookkeeping may be needed.']);

        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('No matching jobs right now')
            ->assertDontSee('Store Helper');
    }

    public function test_a_ready_cv_with_no_matches_gets_the_no_match_message(): void
    {
        $this->actingAs($this->student(skills: ['Bookkeeping']))->get(route('dashboard'))
            ->assertOk()
            ->assertSee('No matching jobs right now')
            ->assertSee('Browse all jobs');
    }

    public function test_alumni_get_suggestions_too(): void
    {
        $alumni = $this->student(alumni: true, skills: ['Bookkeeping']);
        $this->job($this->company, ['title' => 'Bookkeeping Assistant']);

        $this->actingAs($alumni)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Bookkeeping Assistant');
    }

    public function test_another_students_skills_never_shape_my_suggestions(): void
    {
        $this->student(skills: ['Welding']);
        $me = $this->student(skills: ['Bookkeeping']);

        $this->job($this->company, ['title' => 'Welding Technician']);
        $this->job($this->company, ['title' => 'Bookkeeping Assistant']);

        $this->actingAs($me)->get(route('dashboard'))
            ->assertSee('Bookkeeping Assistant')
            ->assertDontSee('Welding Technician');
    }

    // ---- helpers -----------------------------------------------------------

    /**
     * A student (or alumni) whose CV is ready. Skills and course are replaced when given,
     * so the keyword matching is predictable. Returned fresh so no relation is stale.
     */
    private function student(bool $alumni = false, ?string $course = null, ?array $skills = null): User
    {
        $factory = User::factory();
        $factory = $alumni ? $factory->alumni()->cvReady() : $factory->cvReady();
        $user = $factory->create();

        $profile = $user->profile()->first();

        // A student on the dashboard has finished the basic profile.
        $profile->update(['profile_completed_at' => $profile->profile_completed_at ?? now()]);

        if ($course !== null) {
            $profile->update(['course_program' => $course]);
        }

        if ($skills !== null) {
            $profile->skills()->delete();
            $profile->skills()->createMany(array_map(fn ($name) => ['name' => $name], $skills));
        }

        return $user->fresh();
    }

    /**
     * A job of $company, approved and open unless the overrides say otherwise.
     * Nothing in it matches a student's keywords unless the test puts it there.
     */
    private function job(Company $company, array $overrides = []): JobPosting
    {
        return JobPosting::factory()->for($company)->create(array_merge([
            'title' => 'Plain Role',
            'description' => 'General duties.',
            'qualifications' => 'Graduate.',
            'requirements' => 'Willing to learn.',
            'approval_status' => 'approved',
            'status' => 'open',
            'application_deadline' => now()->addWeeks(2),
        ], $overrides));
    }

    /**
     * An application by $user, to a new job unless one is passed.
     */
    private function apply(User $user, string $status, array $application = [], array $job = []): JobApplication
    {
        return $this->applyTo($user, $this->job($this->company, $job + ['title' => 'Plain Role']), $status, $application);
    }

    private function applyTo(User $user, JobPosting $job, string $status, array $application = []): JobApplication
    {
        return JobApplication::create(array_merge([
            'job_posting_id' => $job->id,
            'applicant_id' => $user->id,
            'profile_id' => $user->profile()->first()?->id,
            'date_applied' => now(),
            'status' => $status,
        ], $application));
    }

    private function notify(User $user, string $title, int $minutesAgo, bool $read = false): Notification
    {
        $notification = Notification::create([
            'recipient_id' => $user->id,
            'type' => 'announcement',
            'title' => $title,
            'message' => 'Message of '.$title,
        ]);

        $notification->forceFill([
            'created_at' => now()->subMinutes($minutesAgo),
            'read_at' => $read ? now() : null,
        ])->save();

        return $notification;
    }
}
