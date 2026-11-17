<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobPosting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 8 follow-up: a company must not get a student's contact details from
 * candidate search, whether from the contact fields, the web links, or text the
 * student typed into their CV. Sits next to CandidateSearchTest (which covers the
 * switch, the visibility rules, filters and the audit trail) and does not repeat it.
 */
class CandidateContactHidingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_contact_fields_links_and_contact_typed_into_the_cv_are_hidden(): void
    {
        $student = User::factory()->searchable()->create();
        $profile = $student->profile()->first();
        $profile->update([
            'full_name' => 'Vera Visible',
            'personal_email' => 'vera.private@example.com',
            'mobile_number' => '09171234567',
            'linkedin_url' => 'https://www.linkedin.com/in/vera-private',
            'portfolio_url' => 'https://vera-portfolio.example.org',
            'cv_summary' => 'Fresh graduate who builds web apps. Email me at vera.text@example.com or call 0917 765 4321 or see https://vera-site.example.net/cv today.',
        ]);
        $profile->experiences()->create([
            'kind' => 'work',
            'position' => 'Intern',
            'organization' => 'City Hall',
            'start_date' => '2025-06-01',
            'description' => 'Reach me at +63 918 111 2222 for references.',
        ]);

        $secrets = [
            'vera.private@example.com', '09171234567',
            'linkedin.com/in/vera-private', 'vera-portfolio.example.org',
            'vera.text@example.com', '0917 765 4321', 'vera-site.example.net', '+63 918 111 2222', '111 2222',
        ];

        $company = Company::factory()->create()->user;

        $list = $this->actingAs($company)->get(route('company.candidates.index'))
            ->assertOk()
            ->assertSee('Vera Visible')
            ->assertSee('Fresh graduate who builds web apps');

        $page = $this->get(route('company.candidates.show', $profile))
            ->assertOk()
            ->assertSee('Vera Visible')
            ->assertSee('Fresh graduate who builds web apps')
            ->assertSee('[contact hidden]');

        foreach ($secrets as $secret) {
            $list->assertDontSee($secret);
            $page->assertDontSee($secret);
        }
    }

    public function test_the_student_preview_hides_the_same_things(): void
    {
        $student = User::factory()->searchable()->create();
        $student->profile()->first()->update([
            'personal_email' => 'vera.private@example.com',
            'mobile_number' => '09171234567',
            'linkedin_url' => 'https://www.linkedin.com/in/vera-private',
            'cv_summary' => 'Fresh graduate. Call me on 0917 765 4321 any time you like, I answer quickly.',
        ]);

        $this->actingAs($student)->get(route('profile.searchable.preview'))
            ->assertOk()
            ->assertSee('Your profile is searchable')
            ->assertSee('[contact hidden]')
            ->assertDontSee('vera.private@example.com')
            ->assertDontSee('09171234567')
            ->assertDontSee('0917 765 4321')
            ->assertDontSee('linkedin.com/in/vera-private');
    }

    public function test_contact_and_links_arrive_only_with_an_application_and_leave_with_a_withdrawal(): void
    {
        $company = Company::factory()->create();
        // Applying only works on an approved, open posting (same as JobApplicationTest::openJob()).
        $job = JobPosting::factory()->create([
            'company_id' => $company->id,
            'approval_status' => 'approved',
            'status' => 'open',
        ]);
        $student = User::factory()->searchable()->create();
        $profile = $student->profile()->first();
        $profile->update([
            'personal_email' => 'vera.private@example.com',
            'mobile_number' => '09171234567',
            'linkedin_url' => 'https://www.linkedin.com/in/vera-private',
        ]);

        // Before applying.
        $this->actingAs($company->user)->get(route('company.candidates.show', $profile))
            ->assertOk()
            ->assertDontSee('vera.private@example.com')
            ->assertDontSee('They applied to');

        $this->actingAs($student)->post(route('applications.store', $job))->assertSessionHasNoErrors();
        $this->assertSame(1, $job->applications()->count(), 'The application was not created.');
        $application = $job->applications()->first();

        // After applying: the candidate page still hides it and points to the application,
        // which carries what the student chose to send.
        $this->actingAs($company->user)->get(route('company.candidates.show', $profile))
            ->assertOk()
            ->assertSee('They applied to')
            ->assertDontSee('vera.private@example.com')
            ->assertDontSee('09171234567')
            ->assertDontSee('linkedin.com/in/vera-private');

        $this->actingAs($company->user)->get(route('company.applications.show', $application))
            ->assertOk()
            ->assertSee('vera.private@example.com')
            ->assertSee('09171234567');

        // Withdrawing removes the pointer again.
        $this->actingAs($student)->patch(route('applications.withdraw', $application));
        $this->actingAs($company->user)->get(route('company.candidates.show', $profile))
            ->assertDontSee('They applied to');
    }

    public function test_searching_for_an_email_phone_number_or_web_address_is_refused(): void
    {
        $company = Company::factory()->create()->user;
        User::factory()->searchable()->create()->profile()->first()
            ->update(['personal_email' => 'vera.private@example.com']);

        foreach ([
            ['q' => 'vera.private@example.com'],
            ['q' => '09171234567'],
            ['skills' => 'facebook.com/vera'],
        ] as $query) {
            $this->actingAs($company)->get(route('company.candidates.index', $query))
                ->assertRedirect(route('company.candidates.index'))
                ->assertSessionHasErrors(array_key_first($query));
        }

        // Ordinary searches, including ones with dots and digits, are fine.
        foreach ([['q' => 'node.js'], ['q' => '2025'], ['skills' => 'PHP, Excel']] as $query) {
            $this->actingAs($company)->get(route('company.candidates.index', $query))
                ->assertOk()
                ->assertSessionHasNoErrors();
        }
    }

    public function test_turning_the_switch_on_without_a_ready_cv_says_what_is_missing(): void
    {
        $student = User::factory()->withProfile()->create(); // no summary, no skill

        $this->actingAs($student)->patch(route('profile.searchable.update'), ['searchable' => 1])
            ->assertSessionHas('status', fn ($status) => str_contains($status, 'a personal summary of at least 50 characters')
                && str_contains($status, 'at least one skill')
                && ! str_contains($status, 'add .'));
    }
}
