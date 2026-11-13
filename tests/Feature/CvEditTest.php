<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CvEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    // ---- access -----------------------------------------------------------

    public function test_a_guest_is_sent_to_login(): void
    {
        $this->get(route('cv.edit'))->assertRedirect(route('login'));
        $this->put(route('cv.update'), $this->payload())->assertRedirect(route('login'));
    }

    public function test_a_company_and_a_coordinator_cannot_use_the_cv_page(): void
    {
        $company = Company::factory()->create()->user;
        $coordinator = User::factory()->jobCoordinator()->create();

        foreach ([$company, $coordinator] as $user) {
            $this->actingAs($user)->get(route('cv.edit'))->assertForbidden();
            $this->actingAs($user)->put(route('cv.update'), $this->payload())->assertForbidden();
        }
    }

    public function test_a_student_without_a_completed_profile_is_sent_to_complete_it_first(): void
    {
        $student = User::factory()->create(); // no profile yet

        $this->actingAs($student)->get(route('cv.edit'))
            ->assertRedirect(route('profile.complete'));
    }

    public function test_a_student_sees_the_cv_page_with_their_saved_data(): void
    {
        $student = User::factory()->cvReady()->create();

        $this->actingAs($student)->get(route('cv.edit'))
            ->assertOk()
            ->assertSee('Personal summary')
            ->assertSee('CV strength')
            ->assertSee('55%')
            ->assertSee('Communication');
    }

    // ---- saving -----------------------------------------------------------

    public function test_a_student_can_save_every_section(): void
    {
        $student = User::factory()->withProfile()->create();

        $this->actingAs($student)->put(route('cv.update'), $this->payload())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('cv.edit'))
            ->assertSessionHas('status');

        $profile = $student->profile()->first();

        $this->assertStringContainsString('Motivated graduate', $profile->cv_summary);
        $this->assertSame('https://www.linkedin.com/in/test-student', $profile->linkedin_url);
        $this->assertSame('https://example.com/portfolio', $profile->portfolio_url);
        $this->assertSame(['Customer service', 'PHP'], $profile->skills()->orderBy('name')->pluck('name')->all());

        $experience = $profile->experiences()->first();
        $this->assertSame('IT Intern', $experience->position);
        $this->assertSame('ojt', $experience->kind);
        $this->assertSame('2025-06-01', $experience->start_date->toDateString());
        $this->assertSame('2025-08-30', $experience->end_date->toDateString());

        $education = $profile->educations()->first();
        $this->assertSame('Alaminos City National High School', $education->school_name);
        $this->assertSame(2018, $education->start_year);

        $this->assertSame('Digital Skills Seminar', $profile->certifications()->first()->title);

        $this->assertTrue($profile->fresh()->cvReady());
        $this->assertSame(100, $profile->fresh()->cvCompleteness()['percent']);
    }

    public function test_an_alumni_can_save_a_cv_too(): void
    {
        $alumni = User::factory()->alumni()->withProfile()->create();

        $this->actingAs($alumni)->put(route('cv.update'), $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertTrue($alumni->profile()->first()->cvReady());
    }

    public function test_a_short_summary_can_be_saved_as_a_draft(): void
    {
        $student = User::factory()->withProfile()->create();

        $this->actingAs($student)->put(route('cv.update'), ['cv_summary' => 'Too short.'])
            ->assertSessionHasNoErrors();

        $profile = $student->profile()->first();

        $this->assertSame('Too short.', $profile->cv_summary);
        $this->assertFalse($profile->cvReady());
    }

    public function test_saving_twice_replaces_the_rows_instead_of_duplicating_them(): void
    {
        $student = User::factory()->withProfile()->create();

        $this->actingAs($student)->put(route('cv.update'), $this->payload())->assertSessionHasNoErrors();
        $this->actingAs($student)->put(route('cv.update'), $this->payload())->assertSessionHasNoErrors();

        $profile = $student->profile()->first();

        $this->assertSame(2, $profile->skills()->count());
        $this->assertSame(1, $profile->experiences()->count());
        $this->assertSame(1, $profile->educations()->count());
        $this->assertSame(1, $profile->certifications()->count());
    }

    public function test_removing_a_whole_section_clears_it(): void
    {
        $student = User::factory()->withProfile()->create();

        $this->actingAs($student)->put(route('cv.update'), $this->payload())->assertSessionHasNoErrors();
        // The page sends nothing for a section whose rows were all removed.
        $this->actingAs($student)->put(route('cv.update'), ['cv_summary' => 'Only a summary is left here now.'])
            ->assertSessionHasNoErrors();

        $profile = $student->profile()->first();

        $this->assertSame(0, $profile->skills()->count());
        $this->assertSame(0, $profile->experiences()->count());
        $this->assertSame(0, $profile->educations()->count());
        $this->assertSame(0, $profile->certifications()->count());
        $this->assertNull($profile->linkedin_url);
    }

    // ---- cleaning ---------------------------------------------------------

    public function test_duplicate_skills_are_removed_ignoring_case_and_spaces(): void
    {
        $student = User::factory()->withProfile()->create();

        $this->actingAs($student)->put(route('cv.update'), [
            'skills' => ['PHP', 'php', '  PHP  ', 'Excel', '', 'C'],
        ])->assertSessionHasNoErrors();

        $this->assertSame(
            ['C', 'Excel', 'PHP'],
            $student->profile()->first()->skills()->orderBy('name')->pluck('name')->all()
        );
    }

    public function test_rows_that_were_added_but_left_empty_are_ignored(): void
    {
        $student = User::factory()->withProfile()->create();

        $this->actingAs($student)->put(route('cv.update'), [
            'experiences' => [['kind' => 'work', 'position' => '', 'organization' => '', 'start_date' => '', 'end_date' => '', 'description' => '']],
            'educations' => [['level' => 'Senior High School', 'school_name' => '', 'start_year' => '', 'end_year' => '']],
            'certifications' => [['kind' => 'certification', 'title' => '', 'issuer' => '', 'date_received' => '']],
        ])->assertSessionHasNoErrors();

        $profile = $student->profile()->first();

        $this->assertSame(0, $profile->experiences()->count());
        $this->assertSame(0, $profile->educations()->count());
        $this->assertSame(0, $profile->certifications()->count());
    }

    // ---- validation -------------------------------------------------------

    public function test_an_end_date_before_the_start_date_is_rejected_and_nothing_is_saved(): void
    {
        $student = User::factory()->withProfile()->create();

        $payload = $this->payload([
            'experiences' => [[
                'kind' => 'work', 'position' => 'Cashier', 'organization' => 'Local Store',
                'start_date' => '2025-08-01', 'end_date' => '2025-06-01',
            ]],
        ]);

        $this->actingAs($student)->put(route('cv.update'), $payload)
            ->assertSessionHasErrors('experiences.0.end_date');

        $profile = $student->profile()->first();

        $this->assertSame(0, $profile->experiences()->count());
        $this->assertNull($profile->cv_summary); // the whole save is refused, not just one section
    }

    public function test_a_currently_ongoing_entry_is_saved_without_an_end_date(): void
    {
        $student = User::factory()->withProfile()->create();

        $payload = $this->payload([
            'experiences' => [[
                'kind' => 'work', 'position' => 'Crew', 'organization' => 'Fast Food Co.',
                'start_date' => '2025-01-15', 'is_current' => '1', 'end_date' => '2025-02-01', // leftover value is ignored
            ]],
        ]);

        $this->actingAs($student)->put(route('cv.update'), $payload)->assertSessionHasNoErrors();

        $this->assertNull($student->profile()->first()->experiences()->first()->end_date);
    }

    public function test_an_entry_needs_an_end_date_unless_it_is_ongoing(): void
    {
        $student = User::factory()->withProfile()->create();

        $payload = $this->payload([
            'experiences' => [[
                'kind' => 'work', 'position' => 'Crew', 'organization' => 'Fast Food Co.', 'start_date' => '2025-01-15',
            ]],
        ]);

        $this->actingAs($student)->put(route('cv.update'), $payload)
            ->assertSessionHasErrors('experiences.0.end_date');
    }

    public function test_a_half_filled_row_is_rejected(): void
    {
        $student = User::factory()->withProfile()->create();

        $this->actingAs($student)->put(route('cv.update'), [
            'experiences' => [['kind' => 'work', 'position' => 'Cashier']],
        ])->assertSessionHasErrors(['experiences.0.organization', 'experiences.0.start_date']);
    }

    public function test_education_end_year_cannot_be_before_the_start_year(): void
    {
        $student = User::factory()->withProfile()->create();

        $this->actingAs($student)->put(route('cv.update'), [
            'educations' => [[
                'level' => 'Senior High School', 'school_name' => 'Some School', 'start_year' => 2020, 'end_year' => 2018,
            ]],
        ])->assertSessionHasErrors('educations.0.end_year');
    }

    public function test_unknown_types_and_levels_are_rejected(): void
    {
        $student = User::factory()->withProfile()->create();

        $this->actingAs($student)->put(route('cv.update'), [
            'experiences' => [[
                'kind' => 'astronaut', 'position' => 'Pilot', 'organization' => 'NASA',
                'start_date' => '2025-01-01', 'end_date' => '2025-02-01',
            ]],
            'educations' => [['level' => 'Hogwarts', 'school_name' => 'Hogwarts', 'start_year' => 2018]],
        ])->assertSessionHasErrors(['experiences.0.kind', 'educations.0.level']);
    }

    public function test_only_https_links_are_accepted_and_linkedin_must_be_linkedin(): void
    {
        $student = User::factory()->withProfile()->create();

        $bad = [
            ['portfolio_url' => 'javascript:alert(1)'],
            ['portfolio_url' => 'http://example.com'],
            ['linkedin_url' => 'https://example.com/in/someone'],
            ['linkedin_url' => 'https://notlinkedin.com/in/someone'],
        ];

        foreach ($bad as $fields) {
            $this->actingAs($student)->put(route('cv.update'), $fields)
                ->assertSessionHasErrors(array_key_first($fields));
        }
    }

    public function test_a_linkedin_subdomain_and_a_normal_https_portfolio_are_accepted(): void
    {
        $student = User::factory()->withProfile()->create();

        $this->actingAs($student)->put(route('cv.update'), [
            'linkedin_url' => 'https://ph.linkedin.com/in/someone',
            'portfolio_url' => 'https://example.com',
        ])->assertSessionHasNoErrors();
    }

    public function test_the_limits_are_enforced(): void
    {
        $student = User::factory()->withProfile()->create();

        $this->actingAs($student)->put(route('cv.update'), [
            'cv_summary' => str_repeat('a', 1001),
            'skills' => array_map(fn ($n) => "Skill $n", range(1, 31)),
        ])->assertSessionHasErrors(['cv_summary', 'skills']);
    }

    // ---- ownership --------------------------------------------------------

    public function test_a_student_cannot_change_another_students_cv(): void
    {
        $a = User::factory()->withProfile()->create();
        $b = User::factory()->cvReady()->create();
        $bProfile = $b->profile()->first();
        $bProfile->experiences()->create([
            'kind' => 'work', 'position' => 'Barista', 'organization' => 'Cafe', 'start_date' => '2024-01-01',
        ]);

        // A tries to smuggle B's ids into the request.
        $payload = $this->payload(['profile_id' => $bProfile->id]);
        $payload['experiences'][0]['profile_id'] = $bProfile->id;
        $payload['experiences'][0]['id'] = 1;

        $this->actingAs($a)->put(route('cv.update'), $payload)->assertSessionHasNoErrors();

        // B is untouched.
        $this->assertSame(['Communication'], $bProfile->skills()->pluck('name')->all());
        $this->assertSame(['Barista'], $bProfile->experiences()->pluck('position')->all());
        $this->assertStringStartsWith('Fresh graduate', $bProfile->fresh()->cv_summary);
        $this->assertNull($bProfile->fresh()->linkedin_url);

        // A's rows belong to A.
        $aProfile = $a->profile()->first();
        $this->assertSame(1, $aProfile->experiences()->count());
        $this->assertSame($aProfile->id, $aProfile->experiences()->first()->profile_id);
    }

    // ---- redisplay --------------------------------------------------------

    public function test_a_failed_save_shows_what_the_student_typed_not_the_old_data(): void
    {
        $student = User::factory()->withProfile()->create();

        $payload = $this->payload([
            'experiences' => [[
                'kind' => 'work', 'position' => 'Typed But Not Saved', 'organization' => 'Local Store',
                'start_date' => '2025-08-01', 'end_date' => '2025-06-01',
            ]],
        ]);

        $this->actingAs($student)->from(route('cv.edit'))->put(route('cv.update'), $payload)
            ->assertRedirect(route('cv.edit'));

        $this->get(route('cv.edit'))->assertOk()->assertSee('Typed But Not Saved');
    }

    public function test_saved_rows_are_shown_on_the_page(): void
    {
        $student = User::factory()->withProfile()->create();

        $this->actingAs($student)->put(route('cv.update'), $this->payload())->assertSessionHasNoErrors();

        $this->get(route('cv.edit'))
            ->assertOk()
            ->assertSee('IT Intern')
            ->assertSee('Alaminos City National High School')
            ->assertSee('Digital Skills Seminar');
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'cv_summary' => 'Motivated graduate with hands-on OJT experience, looking for an entry-level role.',
            'skills' => ['PHP', 'Customer service'],
            'experiences' => [[
                'kind' => 'ojt',
                'position' => 'IT Intern',
                'organization' => 'Alaminos City Hall',
                'start_date' => '2025-06-01',
                'end_date' => '2025-08-30',
                'description' => 'Fixed printers and maintained the office network.',
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
