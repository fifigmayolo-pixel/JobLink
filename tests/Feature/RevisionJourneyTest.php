<?php

namespace Tests\Feature;

use App\Jobs\ImportStudentRoster;
use App\Mail\ApplicantNotificationMail;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\Notification;
use App\Models\User;
use App\Support\PhilippineLocations;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 12, the final check: the whole revised system in one walk.
 *
 *   Coordinator adds a student and uploads a roster
 *   -> the student activates, completes the profile and builds the CV
 *   -> they switch on "searchable", and a company finds them (no contact details)
 *   -> the company posts a job, the Coordinator approves it
 *   -> the student applies, then edits the CV (the company keeps the copy it got)
 *   -> the company reads the CV and invites them to an interview
 *   -> the student is notified in JobLink and by email
 *   -> both dashboards show the right numbers
 *
 * and, all the way through, the Coordinator never gets to open an applicant's CV.
 *
 * The walk uses a student. Alumni take the same steps after a Tracer Study screen;
 * the alumni side of the roster is covered by the import step.
 */
class RevisionJourneyTest extends TestCase
{
    use RefreshDatabase;

    private const STUDENT_ID = '98-AC-0003';
    private const STUDENT_NAME = 'Maria Journey Santos';
    private const STUDENT_EMAIL = 'maria.journey@example.com';
    private const STUDENT_MOBILE = '09171234567';
    private const STUDENT_STREET = '12 Rizal Street';
    private const PASSWORD = 'Journey-Pass-2026!';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        // Nothing leaves the test: no real mail, and the roster import is only
        // recorded (every other queued job still runs, as it does in the app tests).
        Mail::fake();
        Queue::fake([ImportStudentRoster::class]);
        Storage::fake(config('filesystems.default'));
    }

    public function test_the_whole_revised_system_from_roster_to_interview(): void
    {
        $coordinator = User::factory()->jobCoordinator()->create();
        $company = Company::factory()->create(['company_name' => 'Journey Corp']);

        // ---- 1. The Coordinator adds a student and uploads an alumni roster ----

        $this->actingAs($coordinator)
            ->post(route('coordinator.roster.quick-add.store'), [
                'student_id_number' => self::STUDENT_ID,
                'full_name' => self::STUDENT_NAME,
                'role' => 'student',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        $maria = User::where('student_id_number', self::STUDENT_ID)->firstOrFail();
        $this->assertSame('student', $maria->role);
        $this->assertNull($maria->activated_at, 'a pre-registered account stays inactive until they activate it');
        $this->assertDatabaseHas('field_change_logs', [
            'action' => 'student_alumni_added',
            'user_id' => $maria->id,
            'changed_by' => $coordinator->id,
        ]);

        $this->post(route('coordinator.roster.import.store'), [
            'roster' => UploadedFile::fake()->create(
                'alumni.xlsx',
                20,
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ),
            'role' => 'alumni',
        ])->assertSessionHasNoErrors();

        Queue::assertPushed(ImportStudentRoster::class, function (ImportStudentRoster $job) use ($coordinator) {
            return $job->role === 'alumni'
                && $job->initiatedBy === $coordinator->id
                && Storage::disk(config('filesystems.default'))->exists($job->filePath);
        });

        // A person who is on the list but has not activated is not "registered" yet.
        $this->actingAs($company->user)->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('registeredCount', 0);

        // ---- 2. The student activates the account ----

        $this->asGuest();

        // The wrong name does not open the account.
        $this->post(route('activate.verify.store'), [
            'student_id_number' => self::STUDENT_ID,
            'full_name' => 'Somebody Else',
        ]);
        $this->assertFalse(session()->has('activation_user_id'));

        // The right name does (case and spaces do not matter).
        $this->post(route('activate.verify.store'), [
            'student_id_number' => self::STUDENT_ID,
            'full_name' => '  MARIA JOURNEY SANTOS ',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('activate.password'))
            ->assertSessionHas('activation_user_id', $maria->id);

        // The session already holds the id; stating it again only keeps this step
        // independent of how the test client carries a session between requests.
        $this->withSession(['activation_user_id' => $maria->id])
            ->post(route('activate.password.store'), [
                'password' => self::PASSWORD,
                'password_confirmation' => self::PASSWORD,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($maria);

        $maria = $maria->fresh();
        $this->assertNotNull($maria->activated_at);
        $this->assertFalse((bool) $maria->must_change_password);
        $this->assertTrue(Hash::check(self::PASSWORD, $maria->password));

        // ---- 3. She completes the profile (the CV page waits until she has) ----

        $early = $this->actingAs($maria)->get(route('cv.edit'));
        $early->assertRedirect();
        $this->assertContains(
            $early->headers->get('Location'),
            [route('profile.complete'), route('terms.show')],
            'the CV page is not open to someone who has not completed the profile'
        );

        $this->get(route('profile.complete'))->assertOk();

        $this->post(route('profile.complete.store'), $this->profilePayload())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $maria = $maria->fresh();
        $this->actingAs($maria);
        $profile = $maria->profile()->firstOrFail();
        $this->assertNotNull($profile->profile_completed_at);
        $this->assertSame(self::STUDENT_NAME, $profile->full_name);
        $this->assertSame('BSIT', $profile->course_program);
        $this->assertNotNull($maria->terms_accepted_at);

        // The profile alone is not a CV yet: nothing to apply or be found with.
        $dashboard = $this->get(route('dashboard'))->assertOk();
        $this->assertFalse($dashboard->viewData('cvReady'));
        $this->assertSame(0, $dashboard->viewData('completeness')['percent']);

        // ---- 4. She builds the CV on My CV ----

        $this->get(route('cv.edit'))->assertOk();

        $this->put(route('cv.update'), $this->cvPayload())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('cv.edit'));

        $profile = $maria->profile()->firstOrFail();
        $this->assertTrue($profile->cvCompleteness()['ready']);
        $this->assertSame(100, $profile->cvCompleteness()['percent']);
        $this->get(route('cv.edit'))->assertOk()->assertSee('100%');

        // ---- 5. Searchable is off by default: companies cannot find her ----

        $this->actingAs($company->user)->get(route('company.candidates.index'))
            ->assertOk()
            ->assertDontSee(self::STUDENT_NAME);
        $this->get(route('company.candidates.show', $profile))->assertNotFound();

        $this->actingAs($maria->fresh())->get(route('profile.searchable.preview'))
            ->assertOk()
            ->assertSee('You are not searchable');

        // ---- 6. She switches it on: now a company can find and read the CV ----

        $this->patch(route('profile.searchable.update'), ['searchable' => '1'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        $profile = $profile->fresh();
        $this->assertTrue($profile->searchable_by_companies);
        $this->assertNotNull($profile->searchable_consented_at);
        $this->assertDatabaseHas('field_change_logs', [
            'action' => 'searchable_enabled',
            'user_id' => $maria->id,
            'changed_by' => $maria->id,
        ]);

        $private = [self::STUDENT_EMAIL, self::STUDENT_MOBILE, self::STUDENT_STREET, '2002-03-04'];

        $list = $this->actingAs($company->user)
            ->get(route('company.candidates.index', ['skills' => 'php', 'course' => 'BSIT', 'status' => 'student']))
            ->assertOk()
            ->assertSee(self::STUDENT_NAME);
        foreach ($private as $value) {
            $list->assertDontSee($value);
        }

        // The same search for alumni does not list her.
        $this->get(route('company.candidates.index', ['status' => 'alumni']))
            ->assertDontSee(self::STUDENT_NAME);

        $page = $this->get(route('company.candidates.show', $profile))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee(self::STUDENT_NAME)
            ->assertSee('Motivated graduate')
            ->assertSee('IT Intern')
            ->assertSee('Contact details are hidden');
        foreach ($private as $value) {
            $page->assertDontSee($value);
        }

        $this->assertDatabaseHas('field_change_logs', [
            'action' => 'candidate_profile_viewed',
            'user_id' => $maria->id,
            'changed_by' => $company->user->id,
        ]);

        // The Coordinator cannot browse candidates, even though the page exists.
        $this->actingAs($coordinator)->get(route('company.candidates.index'))->assertForbidden();
        $this->get(route('company.candidates.show', $profile))->assertForbidden();

        // ---- 7. The company posts a job. It waits for approval. ----

        $this->actingAs($company->user)
            ->post(route('jobs.store'), [
                'title' => 'Journey PHP Developer',
                'description' => 'Build and maintain small PHP systems for the journey test.',
                'qualifications' => 'Any IT course.',
                'requirements' => 'PHP and a willingness to learn.',
                'location' => 'Alaminos City',
                'job_type' => 'full_time',
                'application_deadline' => now()->addWeek()->toDateString(),
            ])
            ->assertSessionHasNoErrors();

        $job = JobPosting::where('title', 'Journey PHP Developer')->firstOrFail();
        $this->assertSame('pending', $job->approval_status);
        $this->assertSame($company->id, $job->company_id);

        $own = $this->get(route('dashboard'))->assertOk();
        $this->assertEquals(['pending' => 1, 'rejected' => 0, 'open' => 0, 'closed' => 0], $own->viewData('jobCounts'));

        // Until it is approved, she can neither see it, be offered it, nor apply to it.
        $this->actingAs($maria)->get(route('jobs.index'))
            ->assertOk()
            ->assertDontSee('Journey PHP Developer');
        $this->post(route('applications.store', $job))->assertNotFound();
        $this->assertNotContains(
            'Journey PHP Developer',
            $this->get(route('dashboard'))->viewData('suggestions')->pluck('job.title')->all()
        );

        // ---- 8. The Coordinator approves it ----

        $this->actingAs($coordinator)
            ->patch(route('coordinator.job-approvals.approve', $job))
            ->assertSessionHasNoErrors();

        $this->assertSame('approved', $job->fresh()->approval_status);
        $this->assertDatabaseHas('notifications', [
            'type' => 'job_posting_approved',
            'related_job_posting_id' => $job->id,
        ]);

        // ---- 9. Now she sees it, is offered it on her dashboard, and applies ----

        $this->actingAs($maria)->get(route('jobs.index'))
            ->assertOk()
            ->assertSee('Journey PHP Developer');

        $this->assertContains(
            'Journey PHP Developer',
            $this->get(route('dashboard'))->viewData('suggestions')->pluck('job.title')->all(),
            'the job matches the skill PHP, so the dashboard suggests it'
        );

        $this->post(route('applications.store', $job))->assertSessionHasNoErrors();

        $application = JobApplication::where('job_posting_id', $job->id)->firstOrFail();
        $this->assertSame($maria->id, $application->applicant_id);
        $this->assertSame('pending', $application->status);
        $this->assertTrue($application->hasCvSnapshot());

        // Once applied, the job is no longer suggested and the counts move.
        $dashboard = $this->get(route('dashboard'))->assertOk();
        $this->assertNotContains('Journey PHP Developer', $dashboard->viewData('suggestions')->pluck('job.title')->all());
        $this->assertSame(
            ['pending' => 1, 'interview' => 0, 'rejected' => 0, 'withdrawn' => 0],
            $dashboard->viewData('statusCounts')
        );

        // ---- 10. She improves her CV afterwards: the company keeps the copy it got ----

        $this->put(route('cv.update'), $this->cvPayload([
            'cv_summary' => 'Changed after applying: now focused on web development roles in Pangasinan.',
        ]))->assertSessionHasNoErrors();

        // ---- 11. The company reads the CV it received ----

        $this->actingAs($company->user)
            ->get(route('company.applications.index'))
            ->assertOk()
            ->assertSee(self::STUDENT_NAME)
            ->assertSee('Journey PHP Developer');

        $this->get(route('company.applications.show', $application))
            ->assertOk()
            ->assertSee('Motivated graduate')                  // the CV as it was when she applied
            ->assertDontSee('Changed after applying')
            ->assertSee('IT Intern')
            ->assertSee(self::STUDENT_EMAIL);                  // contact details she sent with it

        $this->get(route('company.applications.cv', $application))
            ->assertOk()
            ->assertSee('Motivated graduate');

        // The searchable page shows the live CV, still without contact details,
        // and now points to her application.
        $this->get(route('company.candidates.show', $profile))
            ->assertOk()
            ->assertSee('Changed after applying')
            ->assertSee('Journey PHP Developer')
            ->assertDontSee(self::STUDENT_EMAIL);

        // Another company cannot open this application or its CV.
        $other = Company::factory()->create()->user;
        foreach ([
            route('company.applications.show', $application),
            route('company.applications.cv', $application),
        ] as $url) {
            $this->assertContains($this->actingAs($other)->get($url)->getStatusCode(), [403, 404], $url);
        }

        // ---- 12. The Coordinator can never open the CV ----

        $this->actingAs($coordinator);
        $this->get(route('company.applications.show', $application))->assertForbidden();
        $this->get(route('company.applications.cv', $application))->assertForbidden();
        $this->get(route('applications.cv', $application))->assertForbidden();
        $this->get(route('company.candidates.show', $profile))->assertForbidden();

        // Records is the Coordinator's page for a student; it carries no CV.
        $this->get(route('coordinator.records.show', $profile))
            ->assertOk()
            ->assertDontSee('Motivated graduate')
            ->assertDontSee('Changed after applying')
            ->assertDontSee('IT Intern');

        // ---- 13. The company invites her to an interview ----

        $this->actingAs($company->user)
            ->patch(route('company.applications.status', $application), ['status' => 'interview'])
            ->assertRedirect();

        $this->assertSame('interview', $application->fresh()->status);

        // ---- 14. She is told in JobLink and by email ----

        $this->assertDatabaseHas('notifications', [
            'recipient_id' => $maria->id,
            'related_application_id' => $application->id,
            'type' => 'interview_invitation',
        ]);

        Mail::assertQueued(
            ApplicantNotificationMail::class,
            fn (ApplicantNotificationMail $mail) => $mail->hasTo(self::STUDENT_EMAIL)
        );

        $this->actingAs($maria)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Interview Invitation');

        $this->get(route('applications.show', $application))
            ->assertOk()
            ->assertSee('invited to interview');

        $note = Notification::where('recipient_id', $maria->id)->where('type', 'interview_invitation')->firstOrFail();
        $this->assertNull($note->read_at);
        $this->patch(route('notifications.read', $note));
        $this->assertNotNull($note->fresh()->read_at);

        // ---- 15. Both dashboards tell the same story ----

        $student = $this->get(route('dashboard'))->assertOk();
        $this->assertSame(
            ['pending' => 0, 'interview' => 1, 'rejected' => 0, 'withdrawn' => 0],
            $student->viewData('statusCounts')
        );
        $student->assertSee('Journey PHP Developer')
            ->assertSee('Interview Invitation');

        $own = $this->actingAs($company->user)->get(route('dashboard'))->assertOk();
        $this->assertEquals(['pending' => 0, 'rejected' => 0, 'open' => 1, 'closed' => 0], $own->viewData('jobCounts'));
        $this->assertSame(1, $own->viewData('applicantsTotal'));
        $this->assertSame(0, $own->viewData('waitingCount'), 'the only applicant has been answered');
        $this->assertSame([$application->id], $own->viewData('recentApplicants')->pluck('id')->all());
        $this->assertSame(1, $own->viewData('registeredCount'), 'Maria is activated; the roster file added nobody yet');

        $this->actingAs($coordinator)->get(route('coordinator.dashboard'))->assertOk();
    }

    public function test_only_the_coordinator_can_add_or_import_students(): void
    {
        $company = Company::factory()->create()->user;
        $student = User::factory()->cvReady()->create();

        foreach ([$company, $student] as $user) {
            $this->actingAs($user)
                ->post(route('coordinator.roster.quick-add.store'), [
                    'student_id_number' => '98-AC-0009',
                    'full_name' => 'Not Allowed',
                    'role' => 'student',
                ])
                ->assertForbidden();

            $this->post(route('coordinator.roster.import.store'), [
                'roster' => UploadedFile::fake()->create(
                    'roster.xlsx',
                    20,
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                ),
                'role' => 'student',
            ])->assertForbidden();
        }

        $this->assertDatabaseMissing('users', ['student_id_number' => '98-0009-09']);
        Queue::assertNothingPushed();
    }

    public function test_a_name_that_is_not_on_the_roster_does_not_activate_anything(): void
    {
        $inactive = User::factory()->create([
            'student_id_number' => '98-AC-0005',
            'full_name' => 'Real Roster Name',
            'activated_at' => null,
            'must_change_password' => true,
        ]);

        $this->asGuest();

        // Right ID, wrong name.
        $this->post(route('activate.verify.store'), [
            'student_id_number' => '98-0005-05',
            'full_name' => 'Wrong Name',
        ]);
        $this->assertFalse(session()->has('activation_user_id'));

        // An ID that is not on the roster at all.
        $this->post(route('activate.verify.store'), [
            'student_id_number' => '98-AC-7777',
            'full_name' => 'Real Roster Name',
        ]);
       $this->assertFalse(session()->has('activation_user_id'));

        // Skipping the identity step: the password page does nothing on its own.
        $this->post(route('activate.password.store'), [
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ]);

        $this->assertGuest();
        $this->assertNull($inactive->fresh()->activated_at);
    }

    // ---- helpers ------------------------------------------------------------

    /** Forget the "acting as" user so the next request is a visitor's. */
    private function asGuest(): void
    {
        $this->app['auth']->forgetGuards();
    }

    /**
     * The Complete Profile form. Location values are taken from the app's own lists
     * (the form only accepts those), so this does not depend on any one city.
     */
    private function profilePayload(): array
    {
        $province = (string) collect(PhilippineLocations::provinces())->values()->first();
        $city = (string) collect(PhilippineLocations::citiesForProvince($province))->values()->first();
        $barangay = (string) collect(PhilippineLocations::barangaysForCity($province, $city))->values()->first();

        return [
            'date_of_birth' => '2002-03-04',
            'age' => 24,
            'gender' => 'Female',
            'civil_status' => 'Single',
            'nationality' => 'Filipino',
            'course_program' => 'BSIT',
            'year_level' => 'Fourth Year',
            'section' => 'A',
            'personal_email' => self::STUDENT_EMAIL,
            'mobile_number' => self::STUDENT_MOBILE,
            'house_street' => self::STUDENT_STREET,
            'province' => $province,
            'municipality_city' => $city,
            'barangay' => $barangay,
            'postal_code' => '2404',
            'accept_terms' => '1',
        ];
    }

    /** The My CV form, every section filled in. */
    private function cvPayload(array $overrides = []): array
    {
        return array_merge([
            'cv_summary' => 'Motivated graduate with hands-on OJT experience, looking for an entry-level role.',
            'skills' => ['PHP', 'Customer service'],
            'experiences' => [[
                'kind' => 'ojt',
                'position' => 'IT Intern',
                'organization' => 'Alaminos City Hall',
                'start_date' => '2025-06-01',
                'end_date' => '2025-08-30',
                'description' => 'Helped the IT office with computers and records.',
            ]],
            'educations' => [[
                'level' => 'Senior High School',
                'school_name' => 'Alaminos City National High School',
                'degree_program' => 'ICT strand',
                'start_year' => 2018,
                'end_year' => 2020,
            ]],
            'certifications' => [[
                'kind' => 'seminar',
                'title' => 'Digital Skills Seminar',
                'issuer' => 'DICT',
                'date_received' => '2025-03-10',
            ]],
            'linkedin_url' => 'https://www.linkedin.com/in/test-student',
            'portfolio_url' => 'https://example.com/portfolio',
        ], $overrides);
    }
}
