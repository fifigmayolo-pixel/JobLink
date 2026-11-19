<?php

namespace Tests\Feature;

use App\Mail\ApplicantNotificationMail;
use App\Models\Company;
use App\Models\FieldChangeLog;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Phase 13: a posting has a number of slots. Each applicant in Interview fills one.
 * When every slot is filled the posting closes by itself, and reopens when one frees up.
 * JobLink does not track hiring, so there is no Hired status: these are interview slots.
 */
class JobSlotsTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Mail::fake();

        $this->company = Company::factory()->create(['company_name' => 'Slots Corp']);
    }

    // ---- the form ---------------------------------------------------------

    public function test_a_new_posting_needs_a_whole_number_of_slots_from_one_up(): void
    {
        $this->actingAs($this->company->user);

        // Missing, zero, negative, not a number, a fraction, and far too many.
        foreach ([null, '', '0', '-2', 'abc', '2.5', '1001'] as $bad) {
            $payload = $this->storePayload(['title' => 'Bad slots '.var_export($bad, true)]);

            if ($bad === null) {
                unset($payload['slots']);
            } else {
                $payload['slots'] = $bad;
            }

            $this->post(route('jobs.store'), $payload)->assertSessionHasErrors('slots');
        }

        $this->assertSame(0, JobPosting::where('title', 'like', 'Bad slots%')->count());

        $this->post(route('jobs.store'), $this->storePayload(['title' => 'Five crew members', 'slots' => '5']))
            ->assertSessionHasNoErrors();

        $job = JobPosting::where('title', 'Five crew members')->firstOrFail();
        $this->assertSame(5, $job->slots);
        $this->assertFalse($job->closed_by_slots);
        $this->assertSame('pending', $job->approval_status, 'the approval flow is unchanged');
        $this->assertSame('open', $job->status);
    }

    public function test_the_coordinator_form_needs_slots_too(): void
    {
        Queue::fake(); // the "new job" notifications are sent in the background

        $coordinator = User::factory()->jobCoordinator()->create();
        $payload = $this->storePayload(['title' => 'Posted by coordinator', 'company_id' => $this->company->id]);

        unset($payload['slots']);
        $this->actingAs($coordinator)->post(route('jobs.store'), $payload)->assertSessionHasErrors('slots');

        $this->post(route('jobs.store'), $payload + ['slots' => 2])->assertSessionHasNoErrors();

        $job = JobPosting::where('title', 'Posted by coordinator')->firstOrFail();
        $this->assertSame(2, $job->slots);
        $this->assertSame('approved', $job->approval_status);
    }

    public function test_a_posting_saved_without_a_slot_value_gets_one(): void
    {
        $job = JobPosting::factory()->for($this->company)->create([
            'posted_by' => $this->company->user->id,
            'approval_status' => 'approved',
            'status' => 'open',
        ])->fresh();

        $this->assertSame(1, $job->slots);
        $this->assertFalse($job->closed_by_slots);
    }

    public function test_editing_without_the_slots_field_keeps_the_number_but_an_empty_one_is_refused(): void
    {
        $job = $this->job(['slots' => 4]);
        $this->actingAs($this->company->user);

        $this->patch(route('jobs.update', $job), $this->updatePayload(['title' => 'Renamed']))
            ->assertSessionHasNoErrors();
        $this->assertSame(4, $job->fresh()->slots);
        $this->assertSame('Renamed', $job->fresh()->title);

        $this->patch(route('jobs.update', $job), $this->updatePayload(['slots' => '']))
            ->assertSessionHasErrors('slots');
        $this->assertSame(4, $job->fresh()->slots);
    }

    public function test_the_number_of_slots_cannot_drop_below_the_people_already_invited(): void
    {
        $job = $this->job(['slots' => 3]);
        $this->invite($this->apply($job));
        $this->invite($this->apply($job));
        $this->actingAs($this->company->user);

        $this->patch(route('jobs.update', $job), $this->updatePayload(['slots' => 1]))
            ->assertSessionHasErrors('slots');
        $this->assertSame(3, $job->fresh()->slots);

        // Lowering it to exactly the invited number is fine: the posting is then full, so it closes.
        $this->patch(route('jobs.update', $job), $this->updatePayload(['slots' => 2]))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', fn ($message) => str_contains($message, 'closed'));

        $job->refresh();
        $this->assertSame(2, $job->slots);
        $this->assertSame('closed', $job->status);
        $this->assertTrue($job->closed_by_slots);
    }

    // ---- closing at the last slot -----------------------------------------

    public function test_the_job_closes_when_the_last_slot_is_taken(): void
    {
        $job = $this->job(['slots' => 2]);
        $first = $this->apply($job);
        $second = $this->apply($job);
        $waiting = $this->apply($job);

        $this->invite($first)->assertSessionHasNoErrors();

        $this->assertSame('open', $job->fresh()->status, 'one of two slots is not full');
        $this->assertDatabaseMissing('field_change_logs', ['action' => 'job_posting_closed_full']);

        $this->invite($second)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', fn ($message) => str_contains($message, 'closed'));

        $job->refresh();
        $this->assertSame('closed', $job->status);
        $this->assertTrue($job->closed_by_slots);
        $this->assertSame('Full', $job->closedLabel());

        $this->assertDatabaseHas('field_change_logs', [
            'action' => 'job_posting_closed_full',
            'job_posting_id' => $job->id,
            'changed_by' => $this->company->user->id,
        ]);

        // Someone still waiting keeps their status, and the company can still review them.
        $this->assertSame('pending', $waiting->fresh()->status);
        $this->actingAs($this->company->user)
            ->get(route('company.applications.show', $waiting))
            ->assertOk();
    }

    public function test_only_interview_fills_a_slot(): void
    {
        $job = $this->job(['slots' => 3]);

        $this->apply($job, status: 'pending');
        $this->apply($job, status: 'rejected');
        $this->apply($job, status: 'withdrawn');
        $this->apply($job, status: 'interview');

        $this->assertSame(1, $job->fresh()->filledSlots());
        $this->assertSame(1, (int) JobPosting::withInterviewCount()->find($job->id)->interview_count);
        $this->assertSame('1 of 3 interview slots filled', $job->fresh()->slotsLabel());
    }

    public function test_an_applicant_whose_account_was_removed_still_holds_the_slot(): void
    {
        $job = $this->job(['slots' => 2]);
        $application = $this->apply($job, status: 'interview');

        $application->applicant->delete();

        $this->assertSame(1, $job->fresh()->filledSlots());
    }

    public function test_slots_are_counted_per_posting(): void
    {
        $one = $this->job(['slots' => 1, 'title' => 'Opening One']);
        $two = $this->job(['slots' => 1, 'title' => 'Opening Two']);

        $this->invite($this->apply($one));

        $this->assertSame('closed', $one->fresh()->status);
        $this->assertSame('open', $two->fresh()->status);
        $this->assertSame(0, $two->fresh()->filledSlots());
    }

    // ---- a full job -------------------------------------------------------

    public function test_a_full_job_refuses_new_applications(): void
    {
        $job = $this->job(['slots' => 1, 'title' => 'Only One Opening', 'job_type' => 'part_time']);
        $this->invite($this->apply($job));

        $late = $this->applicant();

        $this->actingAs($late)->post(route('applications.store', $job))
            ->assertSessionHasErrors('application');

        $this->assertDatabaseMissing('job_applications', ['job_posting_id' => $job->id, 'applicant_id' => $late->id]);
        $this->assertTrue(Gate::forUser($late)->denies('create', [JobApplication::class, $job->fresh()]));

        // The page says so and offers no Apply button; the Jobs list no longer shows it.
        $this->get(route('jobs.show', $job))
            ->assertOk()
            ->assertSee('<strong>Full.</strong>', false)
            ->assertSee('filled all of its interview slots')
            ->assertDontSee('Apply now');

        $this->get(route('jobs.index'))->assertDontSee('Only One Opening');
    }

    public function test_the_policy_blocks_applying_to_an_open_posting_that_is_full(): void
    {
        // Not closed yet (nothing has run the rule), but every slot already holds an interview.
        $job = $this->job(['slots' => 1]);
        $this->apply($job, status: 'interview');

        $late = $this->applicant();

        $this->assertTrue(Gate::forUser($late)->denies('create', [JobApplication::class, $job->fresh()]));

        $this->actingAs($late)->post(route('applications.store', $job))->assertSessionHasErrors('application');
        $this->assertDatabaseMissing('job_applications', ['applicant_id' => $late->id]);
    }

    public function test_inviting_one_more_than_the_slots_is_blocked_but_rejecting_still_works(): void
    {
        $job = $this->job(['slots' => 1]);
        $invited = $this->apply($job);
        $other = $this->apply($job);

        $this->invite($invited);

        $this->invite($other)->assertSessionHasErrors('status');
        $this->assertSame('pending', $other->fresh()->status);
        $this->assertDatabaseMissing('notifications', [
            'recipient_id' => $other->applicant_id,
            'type' => 'interview_invitation',
        ]);

        // The count never goes above the slots.
        $this->assertSame(1, $job->fresh()->filledSlots());

        // Someone already in Interview can be sent the invitation again; that is not a new slot.
        $this->invite($invited)->assertSessionHasNoErrors();

        // And the other applicant can still be rejected, which keeps the job full.
        $this->setStatus($other, 'rejected')->assertSessionHasNoErrors();
        $this->assertSame('rejected', $other->fresh()->status);
        $this->assertSame('closed', $job->fresh()->status);
    }

    // ---- reopening --------------------------------------------------------

    public function test_the_job_reopens_when_an_invited_applicant_is_moved_out_of_interview(): void
    {
        $job = $this->job(['slots' => 1]);
        $application = $this->apply($job);

        $this->invite($application);
        $this->assertSame('closed', $job->fresh()->status);

        $this->setStatus($application, 'rejected')
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', fn ($message) => str_contains($message, 'open'));

        $job->refresh();
        $this->assertSame('open', $job->status);
        $this->assertFalse($job->closed_by_slots);
        $this->assertDatabaseHas('field_change_logs', [
            'action' => 'job_posting_reopened_slots',
            'job_posting_id' => $job->id,
            'changed_by' => $this->company->user->id,
        ]);

        // Students can apply again.
        $student = $this->applicant();
        $this->actingAs($student)->post(route('applications.store', $job))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('job_applications', ['job_posting_id' => $job->id, 'applicant_id' => $student->id]);
    }

    public function test_the_job_reopens_when_the_slots_are_raised(): void
    {
        $job = $this->job(['slots' => 1]);
        $this->invite($this->apply($job));
        $this->assertSame('closed', $job->fresh()->status);

        // The edit form sends the status it showed: Closed.
        $this->actingAs($this->company->user)
            ->patch(route('jobs.update', $job), $this->updatePayload(['slots' => 2, 'status' => 'closed']))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', fn ($message) => str_contains($message, 'open'));

        $job->refresh();
        $this->assertSame(2, $job->slots);
        $this->assertSame('open', $job->status);
        $this->assertFalse($job->closed_by_slots);
        $this->assertDatabaseHas('field_change_logs', ['action' => 'job_posting_reopened_slots', 'job_posting_id' => $job->id]);
    }

    public function test_a_full_job_cannot_be_opened_without_raising_the_slots(): void
    {
        $job = $this->job(['slots' => 1]);
        $this->invite($this->apply($job));

        $this->actingAs($this->company->user)
            ->patch(route('jobs.update', $job), $this->updatePayload(['slots' => 1, 'status' => 'open']))
            ->assertSessionHasErrors('slots');

        $this->assertSame('closed', $job->fresh()->status);
    }

    public function test_a_job_the_company_closed_by_hand_is_never_reopened_automatically(): void
    {
        $job = $this->job(['slots' => 2]);
        $application = $this->apply($job);

        $this->actingAs($this->company->user)
            ->patch(route('jobs.update', $job), $this->updatePayload(['status' => 'closed']))
            ->assertSessionHasNoErrors();

        $job->refresh();
        $this->assertSame('closed', $job->status);
        $this->assertFalse($job->closed_by_slots, 'closed by hand, so not "Full"');
        $this->assertSame('Closed', $job->closedLabel());

        // Inviting and then rejecting someone does not open it.
        $this->invite($application);
        $this->setStatus($application, 'rejected');
        $this->assertSame('closed', $job->fresh()->status);

        // Neither does raising the slots.
        $this->actingAs($this->company->user)
            ->patch(route('jobs.update', $job), $this->updatePayload(['status' => 'closed', 'slots' => 5]))
            ->assertSessionHasNoErrors();
        $this->assertSame('closed', $job->fresh()->status);

        $this->assertDatabaseMissing('field_change_logs', ['action' => 'job_posting_reopened_slots']);
        $this->assertDatabaseMissing('field_change_logs', ['action' => 'job_posting_closed_full']);
    }

    // ---- what is left alone -----------------------------------------------

    public function test_the_invitation_notification_and_email_still_go_out(): void
    {
        $job = $this->job(['slots' => 2]);
        $student = $this->applicant();
        $student->profile()->first()->forceFill(['personal_email' => 'slots.applicant@example.com'])->save();
        $application = $this->apply($job, $student);

        $this->invite($application);

        $this->assertDatabaseHas('notifications', [
            'recipient_id' => $student->id,
            'type' => 'interview_invitation',
            'related_application_id' => $application->id,
        ]);
        $this->assertSame(1, $student->notifications()->count(), 'no extra notification for filling a slot');

        Mail::assertQueued(
            ApplicantNotificationMail::class,
            fn (ApplicantNotificationMail $mail) => $mail->hasTo('slots.applicant@example.com')
        );
    }

    public function test_the_new_audit_actions_have_labels_and_belong_to_job_postings(): void
    {
        foreach ([
            'job_posting_closed_full' => 'Job Posting Closed (Slots Full)',
            'job_posting_reopened_slots' => 'Job Posting Reopened (Slot Freed)',
        ] as $action => $label) {
            $entry = new FieldChangeLog(['action' => $action]);

            $this->assertSame($label, $entry->actionLabel());
            $this->assertTrue($entry->concernsJobPosting());
        }
    }

    // ---- the labels -------------------------------------------------------

    public function test_interview_slots_show_on_the_card_the_details_page_and_the_company_pages(): void
    {
        $job = $this->job(['slots' => 3, 'title' => 'Three Crew Wanted', 'job_type' => 'part_time']);
        $this->apply($job, status: 'interview');

        $label = '1 of 3 interview slots filled';

        // A student: the job card in the list, and the details page.
        $student = $this->applicant();
        $this->actingAs($student)->get(route('jobs.index'))->assertOk()->assertSee($label);
        $this->get(route('jobs.show', $job))->assertOk()->assertSee($label)->assertDontSee('<strong>Full.</strong>', false);

        // The company: Your Posted Jobs, the job page, and the dashboard.
        $this->actingAs($this->company->user);
        $this->get(route('jobs.index'))->assertOk()->assertSee($label);
        $this->get(route('jobs.show', $job))->assertOk()->assertSee($label);
        $this->get(route('dashboard'))->assertOk()->assertSee($label);
    }

    public function test_a_single_slot_reads_in_the_singular(): void
    {
        $job = $this->job(['slots' => 1, 'title' => 'Single Opening']);

        $this->assertSame('0 of 1 interview slot filled', $job->fresh()->slotsLabel());

        $this->actingAs($this->company->user)->get(route('jobs.show', $job))
            ->assertSee('0 of 1 interview slot filled');
    }

    public function test_a_full_job_is_labelled_full_and_a_job_closed_by_hand_is_labelled_closed(): void
    {
        $full = $this->job(['slots' => 1, 'title' => 'Full Opening', 'job_type' => 'part_time']);
        $this->invite($this->apply($full));

        $byHand = $this->job(['slots' => 2, 'title' => 'Closed By Hand', 'job_type' => 'part_time', 'status' => 'closed']);

        $this->actingAs($this->company->user);

        // Details pages: the banner differs.
        $this->get(route('jobs.show', $full))->assertSee('<strong>Full.</strong>', false);
        $this->get(route('jobs.show', $byHand))
            ->assertSee('<strong>Closed.</strong>', false)
            ->assertDontSee('<strong>Full.</strong>', false);

        // Your Posted Jobs and the dashboard read the same labels.
        $list = $this->get(route('jobs.index'))->assertOk();
        $labels = $list->viewData('jobPostings')->getCollection()->mapWithKeys(fn ($j) => [$j->title => $j->closedLabel()]);
        $this->assertSame('Full', $labels['Full Opening']);
        $this->assertSame('Closed', $labels['Closed By Hand']);
        $list->assertSee('Full');

        $dashboard = $this->get(route('dashboard'))->assertOk();
        $this->assertEquals(['pending' => 0, 'rejected' => 0, 'open' => 0, 'closed' => 2], $dashboard->viewData('jobCounts'));
        $dashboard->assertSee('Full');

        // The job card shows it too (it appears for closed jobs on Saved Jobs, for example).
        $student = $this->applicant();
        $this->actingAs($student);
        $card = view('partials.job-card', ['job' => $full->fresh()->load('company')])->render();
        $this->assertStringContainsString('1 of 1 interview slot filled', $card);
        $this->assertStringContainsString('jl-job-pill-closed', $card);
        $this->assertStringContainsString('Full', $card);
    }

    // ---- helpers ----------------------------------------------------------

    private function job(array $overrides = []): JobPosting
    {
        return JobPosting::factory()->for($this->company)->create(array_merge([
            'posted_by' => $this->company->user->id,
            'approval_status' => 'approved',
            'status' => 'open',
            'application_deadline' => now()->addWeeks(2),
            'slots' => 1,
        ], $overrides));
    }

    private function applicant(): User
    {
        return User::factory()->cvReady()->create(['activated_at' => now()]);
    }

    private function apply(JobPosting $job, ?User $applicant = null, string $status = 'pending'): JobApplication
    {
        $applicant ??= $this->applicant();

        return JobApplication::create([
            'job_posting_id' => $job->id,
            'applicant_id' => $applicant->id,
            'profile_id' => $applicant->profile()->first()?->id,
            'date_applied' => now(),
            'status' => $status,
        ]);
    }

    private function setStatus(JobApplication $application, string $status): TestResponse
    {
        return $this->actingAs($this->company->user)
            ->patch(route('company.applications.status', $application), ['status' => $status]);
    }

    private function invite(JobApplication $application): TestResponse
    {
        return $this->setStatus($application, 'interview');
    }

    /**
     * What the New Job form submits.
     */
    private function storePayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Crew Member',
            'job_type' => 'full_time',
            'description' => "Serve customers.\nKeep the store clean.",
            'qualifications' => 'Senior high school graduate.',
            'requirements' => 'A complete CV profile on JobLink.',
            'location' => 'Alaminos City, Pangasinan',
            'application_deadline' => now()->addWeeks(2)->format('Y-m-d'),
            'slots' => 5,
        ], $overrides);
    }

    /**
     * What the Edit Job form submits. No slots unless the test sets it.
     */
    private function updatePayload(array $overrides = []): array
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
