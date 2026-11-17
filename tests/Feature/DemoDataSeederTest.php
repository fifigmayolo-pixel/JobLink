<?php

namespace Tests\Feature;

use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\Notification;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_four_demo_logins_exist_and_are_ready_to_use(): void
    {
        $this->seed(DemoDataSeeder::class);

        $logins = [
            'job_coordinator' => User::where('email', 'coordinator@joblink.test')->first(),
            'company' => User::where('email', 'company@joblink.test')->first(),
            'student' => User::where('student_id_number', '99-0001-01')->first(),
            'alumni' => User::where('student_id_number', '99-0002-02')->first(),
        ];

        foreach ($logins as $role => $user) {
            $this->assertNotNull($user, "The $role demo login was not created.");
            $this->assertSame($role, $user->role);
            $this->assertTrue($user->hasRole($role), "The $role login has no matching Spatie role.");
            $this->assertTrue(Hash::check('password', $user->password), "The $role password is not the demo password.");
            $this->assertFalse((bool) $user->must_change_password, "The $role login is stuck on the forced password change.");
            $this->assertNotNull($user->activated_at);
        }

        // Students and alumni also have to be past terms and profile completion.
        foreach (['student', 'alumni'] as $role) {
            $this->assertNotNull($logins[$role]->terms_accepted_at);
            $this->assertNotNull($logins[$role]->profile->profile_completed_at);
        }
    }

    public function test_every_demo_login_gets_past_the_middleware(): void
    {
        $this->seed(DemoDataSeeder::class);

        foreach ([
            User::where('email', 'coordinator@joblink.test')->first(),
            User::where('email', 'company@joblink.test')->first(),
            User::where('student_id_number', '99-0001-01')->first(),
            User::where('student_id_number', '99-0002-02')->first(),
        ] as $user) {
            $this->actingAs($user)->get(route('jobs.index'))->assertOk();

            // The dashboard too: the Coordinator is sent to their own, everyone else gets one.
            $dashboard = $this->actingAs($user)->get(route('dashboard'));

            if ($user->role === 'job_coordinator') {
                $dashboard->assertRedirect(route('coordinator.dashboard'));
            } else {
                $dashboard->assertOk();
            }
        }
    }

    public function test_the_student_is_ready_to_apply_and_the_alumni_is_not_yet(): void
    {
        $this->seed(DemoDataSeeder::class);

        $student = User::where('student_id_number', '99-0001-01')->first()->profile;
        $alumni = User::where('student_id_number', '99-0002-02')->first()->profile;

        $this->assertTrue($student->cvReady());
        $this->assertSame(100, $student->cvCompleteness()['percent']);

        $this->assertFalse($alumni->cvReady());
        $this->assertSame(0, $alumni->cvCompleteness()['percent']);
    }

    public function test_the_sample_jobs_and_application_are_there(): void
    {
        $this->seed(DemoDataSeeder::class);

        $this->assertSame(2, JobPosting::listed()->count());
        $this->assertSame(1, JobPosting::awaitingApproval()->count());

        $application = JobApplication::firstOrFail();
        $this->assertSame('pending', $application->status);
        $this->assertTrue($application->hasCvSnapshot());

        $student = User::where('student_id_number', '99-0001-01')->first();
        $company = User::where('email', 'company@joblink.test')->first();

        // The student sees the two approved jobs and not the pending one.
        $this->actingAs($student)->get(route('jobs.index'))
            ->assertOk()
            ->assertSee('Junior Web Developer')
            ->assertSee('Customer Service Associate')
            ->assertDontSee('IT Support Intern');

        // The company sees its applicant.
        $this->actingAs($company)->get(route('company.applications.index'))
            ->assertOk()
            ->assertSee('Demo Student');
    }

    public function test_the_student_dashboard_has_something_to_show(): void
    {
        $this->seed(DemoDataSeeder::class);

        $student = User::where('student_id_number', '99-0001-01')->first();

        $response = $this->actingAs($student)->get(route('dashboard'))->assertOk();

        $this->assertSame(100, $response->viewData('completeness')['percent']);
        $this->assertSame(['pending' => 1, 'interview' => 0, 'rejected' => 0, 'withdrawn' => 0], $response->viewData('statusCounts'));

        // The job already applied to is left out; the other open one matches on "communication".
        $this->assertSame(['Customer Service Associate'], $response->viewData('suggestions')->pluck('job.title')->all());

        $response->assertSee('Junior Web Developer')            // in the latest applications
            ->assertSee('Welcome to JobLink')
            ->assertSee('Suggested jobs based on your profile');
    }

    public function test_the_alumni_dashboard_asks_for_the_cv(): void
    {
        $this->seed(DemoDataSeeder::class);

        $alumni = User::where('student_id_number', '99-0002-02')->first();

        $this->actingAs($alumni)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Your CV is not ready to apply with yet')
            ->assertSee('Finish your CV to see suggested jobs')
            ->assertSee('0 applications');
    }

    public function test_the_company_dashboard_has_something_to_show(): void
    {
        $this->seed(DemoDataSeeder::class);

        $company = User::where('email', 'company@joblink.test')->first();

        $response = $this->actingAs($company)->get(route('dashboard'))->assertOk();

        $this->assertEquals(['pending' => 1, 'rejected' => 0, 'open' => 2, 'closed' => 1], $response->viewData('jobCounts'));
        $this->assertSame(1, $response->viewData('applicantsTotal'));
        $this->assertSame(2, $response->viewData('registeredCount'), 'the demo student and the demo alumni');

        $response->assertSee('Demo Student')
            ->assertSee('Office Clerk');

        // The company's own notification is there on its Notifications page.
        $this->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Job posting approved');
    }

    public function test_the_company_can_find_the_demo_student_but_not_the_alumni(): void
    {
        $this->seed(DemoDataSeeder::class);

        $student = User::where('student_id_number', '99-0001-01')->first()->profile;
        $alumni = User::where('student_id_number', '99-0002-02')->first()->profile;

        $this->assertTrue($student->isSearchable());
        $this->assertNotNull($student->searchable_consented_at);

        $this->assertFalse($alumni->isSearchable());
        $this->assertNull($alumni->searchable_consented_at);

        $this->actingAs(User::where('email', 'company@joblink.test')->first())
            ->get(route('company.candidates.index'))
            ->assertOk()
            ->assertSee('Demo Student')
            ->assertDontSee('Demo Alumni');
    }

    public function test_the_closed_demo_job_is_never_shown_to_students(): void
    {
        $this->seed(DemoDataSeeder::class);

        $this->assertSame('closed', JobPosting::where('title', 'Office Clerk')->firstOrFail()->status);

        $this->actingAs(User::where('student_id_number', '99-0001-01')->first())
            ->get(route('jobs.index'))
            ->assertOk()
            ->assertDontSee('Office Clerk');
    }

    public function test_running_it_twice_does_not_duplicate_anything(): void
    {
        $this->seed(DemoDataSeeder::class);
        $before = $this->counts();

        $this->seed(DemoDataSeeder::class);

        $this->assertSame($before, $this->counts());
    }

    public function test_a_coordinator_password_that_was_changed_is_left_alone(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $coordinator = User::where('email', 'coordinator@joblink.test')->first();
        $coordinator->forceFill([
            'password' => 'A-Real-Password-9!',
            'must_change_password' => false,
        ])->save();

        $this->seed(DemoDataSeeder::class);

        $coordinator = $coordinator->fresh();
        $this->assertTrue(Hash::check('A-Real-Password-9!', $coordinator->password));
        $this->assertFalse(Hash::check('password', $coordinator->password));
    }

    public function test_the_normal_seed_adds_demo_data_outside_production_only(): void
    {
        $this->seed(); // DatabaseSeeder, running in the testing environment
        $this->assertNotNull(User::where('email', 'company@joblink.test')->first());
    }

    private function counts(): array
    {
        return [
            'users' => User::count(),
            'jobs' => JobPosting::count(),
            'applications' => JobApplication::count(),
            'skills' => \App\Models\ProfileSkill::count(),
            'experiences' => \App\Models\ProfileExperience::count(),
            'educations' => \App\Models\ProfileEducation::count(),
            'certifications' => \App\Models\ProfileCertification::count(),
            'notifications' => Notification::count(),
        ];
    }
}
