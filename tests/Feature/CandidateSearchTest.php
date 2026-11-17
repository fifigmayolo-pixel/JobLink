<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\FieldChangeLog;
use App\Models\JobPosting;
use App\Models\StudentAlumniProfile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    // ---- the student's switch -----------------------------------------------

    public function test_nobody_is_searchable_by_default(): void
    {
        $student = User::factory()->cvReady()->create();

        $this->assertFalse((bool) $student->profile()->first()->searchable_by_companies);
        $this->assertSame(0, StudentAlumniProfile::searchable()->count());

        $this->actingAs(Company::factory()->create()->user)
            ->get(route('company.candidates.index'))
            ->assertOk()
            ->assertSee('No searchable profiles yet');
    }

    public function test_a_student_can_turn_the_switch_on_and_then_companies_can_find_them(): void
    {
        $student = User::factory()->cvReady()->create();
        $student->profile()->first()->update(['full_name' => 'Opted In Person']);

        $this->actingAs($student)
            ->patch(route('profile.searchable.update'), ['searchable' => '1'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        $profile = $student->profile()->first();
        $this->assertTrue($profile->searchable_by_companies);
        $this->assertNotNull($profile->searchable_consented_at);

        // The consent trail.
        $this->assertDatabaseHas('field_change_logs', [
            'action' => 'searchable_enabled',
            'user_id' => $student->id,
            'changed_by' => $student->id,
        ]);

        $this->actingAs(Company::factory()->create()->user)
            ->get(route('company.candidates.index'))
            ->assertOk()
            ->assertSee('Opted In Person');
    }

    public function test_turning_it_off_hides_them_at_once_and_clears_the_consent(): void
    {
        $student = User::factory()->searchable()->create();
        $student->profile()->first()->update(['full_name' => 'Soon Hidden']);
        $company = Company::factory()->create();

        $this->actingAs($company->user)->get(route('company.candidates.index'))->assertSee('Soon Hidden');

        $this->actingAs($student)->patch(route('profile.searchable.update'), ['searchable' => '0'])
            ->assertSessionHasNoErrors();

        $profile = $student->profile()->first();
        $this->assertFalse($profile->searchable_by_companies);
        $this->assertNull($profile->searchable_consented_at);
        $this->assertDatabaseHas('field_change_logs', ['action' => 'searchable_disabled', 'user_id' => $student->id]);

        $this->actingAs($company->user)->get(route('company.candidates.index'))->assertDontSee('Soon Hidden');
        $this->get(route('company.candidates.show', $profile))->assertNotFound();
    }

    public function test_saving_the_same_setting_again_changes_and_logs_nothing(): void
    {
        $student = User::factory()->cvReady()->create();

        $this->actingAs($student)->patch(route('profile.searchable.update'), ['searchable' => '0'])
            ->assertSessionHas('status');

        $this->assertSame(0, FieldChangeLog::whereIn('action', ['searchable_enabled', 'searchable_disabled'])->count());
    }

    public function test_the_switch_can_be_on_but_they_stay_hidden_until_the_cv_is_ready(): void
    {
        $student = User::factory()->withProfile()->create(); // no CV yet
        $student->profile()->first()->update(['full_name' => 'No Cv Yet']);

        $this->actingAs($student)->patch(route('profile.searchable.update'), ['searchable' => '1'])
            ->assertSessionHasNoErrors();

        $this->assertTrue($student->profile()->first()->searchable_by_companies);

        $this->actingAs(Company::factory()->create()->user)
            ->get(route('company.candidates.index'))
            ->assertDontSee('No Cv Yet');
    }

    public function test_only_students_and_alumni_can_change_the_switch(): void
    {
        $company = Company::factory()->create()->user;
        $coordinator = User::factory()->jobCoordinator()->create();

        $this->actingAs($company)->patch(route('profile.searchable.update'), ['searchable' => '1'])->assertForbidden();
        $this->actingAs($coordinator)->patch(route('profile.searchable.update'), ['searchable' => '1'])->assertForbidden();
    }

    public function test_the_settings_page_shows_the_switch_and_the_preview_page_works(): void
    {
        $student = User::factory()->cvReady()->create();

        $this->actingAs($student)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Make my profile searchable by partner companies')
            ->assertSee('Not searchable');

        $this->get(route('profile.searchable.preview'))
            ->assertOk()
            ->assertSee('What companies see')
            ->assertSee('You are not searchable');

        $student->profile()->first()->update(['searchable_by_companies' => true]);
        $student->unsetRelation('profile');

        $this->get(route('profile.edit'))->assertSee('Visible to partner companies');
        $this->get(route('profile.searchable.preview'))->assertSee('Your profile is searchable');
    }

    // ---- who can be found ---------------------------------------------------

    public function test_the_visibility_rules_for_who_can_be_found(): void
    {
        $ok = User::factory()->searchable()->create();

        $removed = User::factory()->searchable()->create();
        $removed->delete(); // a removed account is soft-deleted

        $unfinished = User::factory()->searchable()->create();
        $unfinished->profile()->first()->update(['profile_completed_at' => null]);

        $shortSummary = User::factory()->searchable()->create();
        $shortSummary->profile()->first()->update(['cv_summary' => 'Too short.']);

        $noSkill = User::factory()->searchable()->create();
        $noSkill->profile()->first()->skills()->delete();

        $off = User::factory()->cvReady()->create();

        $found = StudentAlumniProfile::searchable()->pluck('id')->all();

        $this->assertSame([$ok->profile()->first()->id], $found);
        $this->assertTrue($ok->profile()->first()->isSearchable());
        $this->assertFalse($off->profile()->first()->isSearchable());
    }

    // ---- what a company sees ------------------------------------------------

    public function test_a_company_sees_the_cv_but_never_the_private_details(): void
    {
        $student = User::factory()->searchable()->create();
        $profile = $student->profile()->first();
        $profile->update([
            'full_name' => 'Visible Vera',
            'personal_email' => 'secret.mail@example.com',
            'mobile_number' => '09998887777',
            'house_street' => 'MARKER-STREET 1',
            'gender' => 'MARKER-GENDER',
            'civil_status' => 'MARKER-CIVIL',
            'date_of_birth' => '1999-01-02',
            'municipality_city' => 'Alaminos City',
            'province' => 'Pangasinan',
            'linkedin_url' => 'https://www.linkedin.com/in/vera',
        ]);
        $profile->experiences()->create([
            'kind' => 'ojt', 'position' => 'IT Intern', 'organization' => 'City Hall', 'start_date' => '2025-06-01',
        ]);

        $company = Company::factory()->create()->user;

        $private = ['secret.mail@example.com', '09998887777', 'MARKER-STREET', 'MARKER-GENDER', 'MARKER-CIVIL', '1999-01-02'];

        $list = $this->actingAs($company)->get(route('company.candidates.index'))
            ->assertOk()
            ->assertSee('Visible Vera')
            ->assertSee('Alaminos City, Pangasinan')
            ->assertSee('Communication');
        foreach ($private as $value) {
            $list->assertDontSee($value);
        }

        $page = $this->get(route('company.candidates.show', $profile))
            ->assertOk()
            ->assertSee('Visible Vera')
            ->assertSee('Fresh graduate')
            ->assertSee('IT Intern')
            ->assertSee('Contact details are hidden')
            // Phase 8 change: web links are contact too, so they wait for an application.
            ->assertDontSee('linkedin.com/in/vera');
        foreach ($private as $value) {
            $page->assertDontSee($value);
        }
    }

    // ---- searching ----------------------------------------------------------

    public function test_keywords_are_case_insensitive_and_every_word_must_match(): void
    {
        $dev = User::factory()->searchable()->create();
        $dev->profile()->first()->update(['full_name' => 'Dev Person']);
        $dev->profile()->first()->experiences()->create([
            'kind' => 'work', 'position' => 'Web Developer', 'organization' => 'Acme', 'start_date' => '2024-01-01',
        ]);

        $other = User::factory()->searchable()->create();
        $other->profile()->first()->update(['full_name' => 'Other Person']);

        $company = Company::factory()->create()->user;

        $this->actingAs($company)->get(route('company.candidates.index', ['q' => 'DEVELOPER']))
            ->assertSee('Dev Person')
            ->assertDontSee('Other Person');

        $this->get(route('company.candidates.index', ['q' => 'web developer']))->assertSee('Dev Person');

        // Both words must appear somewhere in the CV.
        $this->get(route('company.candidates.index', ['q' => 'web photographer']))
            ->assertDontSee('Dev Person')
            ->assertSee('No candidates match your search');
    }

    public function test_skills_match_any_of_them_and_the_best_match_comes_first(): void
    {
        $a = User::factory()->searchable()->create();
        $a->profile()->first()->update(['full_name' => 'Alice Alpha']);
        $a->profile()->first()->skills()->createMany([['name' => 'PHP'], ['name' => 'Excel']]);

        $b = User::factory()->searchable()->create();
        $b->profile()->first()->update(['full_name' => 'Bob Beta']);
        $b->profile()->first()->skills()->create(['name' => 'PHP']);

        $c = User::factory()->searchable()->create();
        $c->profile()->first()->update(['full_name' => 'Carl Gamma']); // only has Communication

        $this->actingAs(Company::factory()->create()->user)
            ->get(route('company.candidates.index', ['skills' => 'php, excel']))
            ->assertOk()
            ->assertSeeInOrder(['Alice Alpha', 'Bob Beta'])
            ->assertSee('2 matching skills')
            ->assertSee('1 matching skill')
            ->assertDontSee('Carl Gamma');
    }

    public function test_the_course_and_student_or_alumni_filters(): void
    {
        $it = User::factory()->searchable()->create();
        $it->profile()->first()->update(['full_name' => 'Ivy IT', 'course_program' => 'BSIT']);

        $hm = User::factory()->searchable()->create();
        $hm->profile()->first()->update(['full_name' => 'Hal HM', 'course_program' => 'BSHM']);

        $alumni = User::factory()->alumni()->searchable()->create();
        $alumni->profile()->first()->update(['full_name' => 'Al Alumni', 'course_program' => 'BSIT']);

        $company = Company::factory()->create()->user;

        $this->actingAs($company)->get(route('company.candidates.index', ['course' => 'BSIT']))
            ->assertSee('Ivy IT')->assertSee('Al Alumni')->assertDontSee('Hal HM');

        $this->get(route('company.candidates.index', ['status' => 'alumni']))
            ->assertSee('Al Alumni')->assertDontSee('Ivy IT')->assertDontSee('Hal HM');

        $this->get(route('company.candidates.index', ['course' => 'BSIT', 'status' => 'student']))
            ->assertSee('Ivy IT')->assertDontSee('Al Alumni');
    }

    public function test_typing_a_wildcard_does_not_match_everything(): void
    {
        User::factory()->searchable()->create();

        $company = Company::factory()->create()->user;

        foreach (['%', '_'] as $wildcard) {
            $this->actingAs($company)->get(route('company.candidates.index', ['q' => $wildcard]))
                ->assertOk()
                ->assertSee('No candidates match your search');
        }
    }

    public function test_results_are_paged_and_the_filters_survive_paging(): void
    {
        foreach (range(1, 13) as $n) {
            User::factory()->searchable()->create();
        }

        $company = Company::factory()->create()->user;

        $this->actingAs($company)->get(route('company.candidates.index', ['skills' => 'communication']))
            ->assertOk()
            ->assertSee('13 candidates')
            ->assertSee('skills=communication', false);

        $this->get(route('company.candidates.index', ['skills' => 'communication', 'page' => 2]))
            ->assertOk()
            ->assertSee('skills=communication', false);
    }

    public function test_bad_filter_values_are_rejected(): void
    {
        $company = Company::factory()->create()->user;

        $this->actingAs($company)->get(route('company.candidates.index', ['course' => 'Underwater Basket Weaving']))
            ->assertSessionHasErrors('course');
    }

    // ---- access -------------------------------------------------------------

    public function test_only_companies_can_search_or_open_candidates(): void
    {
        $candidate = User::factory()->searchable()->create();
        $profile = $candidate->profile()->first();

        $this->get(route('company.candidates.index'))->assertRedirect(route('login'));

        foreach ([
            User::factory()->cvReady()->create(),
            User::factory()->alumni()->cvReady()->create(),
            User::factory()->jobCoordinator()->create(),
        ] as $user) {
            $this->actingAs($user)->get(route('company.candidates.index'))->assertForbidden();
            $this->actingAs($user)->get(route('company.candidates.show', $profile))->assertForbidden();
        }
    }

    public function test_a_profile_that_is_not_searchable_looks_like_it_does_not_exist(): void
    {
        $off = User::factory()->cvReady()->create();   // switch off
        $company = Company::factory()->create()->user;

        $this->actingAs($company)->get(route('company.candidates.show', $off->profile()->first()))->assertNotFound();
        $this->get(route('company.candidates.show', 999999))->assertNotFound();
    }

    // ---- the audit log ------------------------------------------------------

    public function test_opening_a_candidate_is_logged_once_per_company_and_day(): void
    {
        $candidate = User::factory()->searchable()->create();
        $profile = $candidate->profile()->first();

        $companyA = Company::factory()->create(['company_name' => 'Alpha Corp']);
        $companyB = Company::factory()->create(['company_name' => 'Beta Corp']);

        $this->actingAs($companyA->user)->get(route('company.candidates.show', $profile))->assertOk();
        $this->get(route('company.candidates.show', $profile))->assertOk(); // reload: no second entry

        $this->assertSame(1, FieldChangeLog::where('action', 'candidate_profile_viewed')->count());
        $this->assertDatabaseHas('field_change_logs', [
            'action' => 'candidate_profile_viewed',
            'user_id' => $candidate->id,
            'changed_by' => $companyA->user->id,
        ]);

        $this->actingAs($companyB->user)->get(route('company.candidates.show', $profile))->assertOk();
        $this->assertSame(2, FieldChangeLog::where('action', 'candidate_profile_viewed')->count());

        // The Coordinator can see it in the Audit Log.
        $coordinator = User::factory()->jobCoordinator()->create();
        $this->actingAs($coordinator)->get(route('coordinator.audit-log.index'))
            ->assertOk()
            ->assertSee('Candidate Profile Viewed')
            ->assertSee('Alpha Corp opened this searchable profile.');
    }

    public function test_the_search_list_itself_is_not_logged(): void
    {
        User::factory()->searchable()->create();

        $this->actingAs(Company::factory()->create()->user)->get(route('company.candidates.index'))->assertOk();

        $this->assertSame(0, FieldChangeLog::where('action', 'candidate_profile_viewed')->count());
    }

    // ---- links to applications ----------------------------------------------

    public function test_the_candidate_page_points_to_an_existing_application_to_this_company_only(): void
    {
        $candidate = User::factory()->searchable()->create();
        $profile = $candidate->profile()->first();

        $company = Company::factory()->create();
        $other = Company::factory()->create();

        $job = JobPosting::factory()->for($company)->create(['approval_status' => 'approved', 'status' => 'open', 'title' => 'Applied Role']);
        $job->applications()->create(['applicant_id' => $candidate->id, 'date_applied' => now(), 'status' => 'pending']);

        $this->actingAs($company->user)->get(route('company.candidates.show', $profile))
            ->assertSee('Applied Role')
            ->assertSee('Open the application');

        // Another company is not told about it.
        $this->actingAs($other->user)->get(route('company.candidates.show', $profile))
            ->assertDontSee('Applied Role')
            ->assertDontSee('Open the application');
    }

    // ---- the Coordinator and the policy text --------------------------------

    public function test_the_coordinators_records_page_does_not_show_the_cv(): void
    {
        $student = User::factory()->searchable()->create();
        $coordinator = User::factory()->jobCoordinator()->create();

        $this->actingAs($coordinator)
            ->get(route('coordinator.records.show', $student->profile()->first()))
            ->assertOk()
            ->assertDontSee('Fresh graduate')   // the CV summary
            ->assertDontSee('Communication');   // a CV skill
    }

    public function test_the_privacy_policy_and_terms_explain_searchable_profiles(): void
    {
        $this->get(route('privacy'))
            ->assertOk()
            ->assertSee('Make my profile searchable by partner companies')
            ->assertSee('turn it off at any time');

        $terms = view('partials.terms-content')->render();

        $this->assertStringContainsString('Make my profile searchable by partner companies', $terms);
        $this->assertStringContainsString('does not make your profile searchable', $terms);
    }
}
