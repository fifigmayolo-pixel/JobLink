<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobPosting;
use App\Models\User;
use App\Support\JobRelevance;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 16: students and alumni see the jobs related to their course and CV skills first.
 * It is a keyword match (config/course_keywords.php), not AI. Nothing is hidden.
 */
class JobRelevanceTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->company = Company::factory()->create(['company_name' => 'Relevance Corp']);
    }

    // ---- the order --------------------------------------------------------------

    public function test_a_bsit_student_sees_it_jobs_first_and_the_unrelated_ones_after_them(): void
    {
        $this->job('Sales Clerk', 0);
        $this->job('Kitchen Helper', 1);
        $this->job('IT Support Specialist', 3);
        $this->job('Web Developer', 5);
        $this->job('Network Technician', 10);

        $response = $this->actingAs($this->student('BSIT'))->get(route('jobs.index'))->assertOk();
        $titles = $response->viewData('jobPostings')->getCollection()->pluck('title')->all();

        // The three IT jobs come first, even though two of them are the oldest on the list...
        $this->assertEqualsCanonicalizing(['IT Support Specialist', 'Web Developer', 'Network Technician'], array_slice($titles, 0, 3));

        // ...and the unrelated jobs are still there afterward, newest first.
        $this->assertSame(['Sales Clerk', 'Kitchen Helper'], array_slice($titles, 3));
        $this->assertSame(5, $response->viewData('jobPostings')->total(), 'no job is hidden');
    }

    public function test_an_empty_profile_gets_the_normal_newest_first_order(): void
    {
        $this->job('Sales Clerk', 0);
        $this->job('Kitchen Helper', 1);
        $this->job('IT Support Specialist', 3);
        $this->job('Web Developer', 5);
        $this->job('Network Technician', 10);

        $response = $this->actingAs($this->student(''))->get(route('jobs.index'))->assertOk();

        $this->assertSame(
            ['Sales Clerk', 'Kitchen Helper', 'IT Support Specialist', 'Web Developer', 'Network Technician'],
            $response->viewData('jobPostings')->getCollection()->pluck('title')->all()
        );

        $this->assertFalse($response->viewData('relevanceActive'));
        $response->assertDontSee('Related to your course')->assertDontSee('listed first');
    }

    public function test_people_without_a_profile_or_with_another_role_get_no_ordering(): void
    {
        $this->job('Web Developer', 3);
        $scope = fn ($q) => $q;

        $this->assertFalse(JobRelevance::forUser(null, $scope)->active());
        $this->assertFalse(JobRelevance::forUser(User::factory()->create(['activated_at' => now()]), $scope)->active(), 'a student with no profile yet');
        $this->assertFalse(JobRelevance::forUser($this->company->user, $scope)->active());
        $this->assertFalse(JobRelevance::forUser(User::factory()->jobCoordinator()->create(), $scope)->active());

        // The same job is related for a BSIT student.
        $this->assertTrue(JobRelevance::forUser($this->student('BSIT'), $scope)->active());
    }

    public function test_cv_skills_count_too_and_one_passing_mention_is_not_enough(): void
    {
        $this->job('Office Assistant', 0, ['description' => 'Uses Excel sometimes.']);   // description only: 1 point
        $this->job('Sales Clerk', 1);
        $excel = $this->job('Excel Data Clerk', 8);                                        // title: 3 points

        $response = $this->actingAs($this->student('', ['Excel']))->get(route('jobs.index'))->assertOk();

        $this->assertSame(
            ['Excel Data Clerk', 'Office Assistant', 'Sales Clerk'],
            $response->viewData('jobPostings')->getCollection()->pluck('title')->all()
        );
        $this->assertSame([$excel->id], $response->viewData('relatedJobIds'));
    }

    public function test_requirements_count_more_than_the_description(): void
    {
        $inRequirements = $this->job('Front Desk Officer', 5, ['requirements' => 'Knowledge of network setup.']);   // 2 points
        $this->job('Receptionist', 0, ['description' => 'A friendly network of offices.']);                          // 1 point

        $response = $this->actingAs($this->student('BSIT'))->get(route('jobs.index'))->assertOk();

        $this->assertSame([$inRequirements->id], $response->viewData('relatedJobIds'));
        $this->assertSame(['Front Desk Officer', 'Receptionist'], $response->viewData('jobPostings')->getCollection()->pluck('title')->all());
    }

    public function test_the_pronoun_it_is_not_mistaken_for_the_word_it(): void
    {
        // Newer, and full of the little word "it" in lower case.
        $this->job('Front Desk Officer', 0, [
            'description' => 'It is a friendly place and we think it is great. It pays well.',
            'requirements' => 'It is required to be on time.',
        ]);
        $real = $this->job('IT Assistant', 6);

        $response = $this->actingAs($this->student('BSIT'))->get(route('jobs.index'))->assertOk();

        $this->assertSame(['IT Assistant', 'Front Desk Officer'], $response->viewData('jobPostings')->getCollection()->pluck('title')->all());
        $this->assertSame([$real->id], $response->viewData('relatedJobIds'));
    }

    public function test_ties_go_to_the_newest_job_first(): void
    {
        $this->job('Web Developer Old', 9);
        $this->job('Web Developer New', 1);
        $this->job('Web Developer Middle', 4);

        $titles = $this->actingAs($this->student('BSIT'))->get(route('jobs.index'))
            ->viewData('jobPostings')->getCollection()->pluck('title')->all();

        $this->assertSame(['Web Developer New', 'Web Developer Middle', 'Web Developer Old'], $titles);
    }

    public function test_alumni_get_the_same_ordering(): void
    {
        $this->job('Sales Clerk', 0);
        $this->job('Web Developer', 7);

        $alumni = $this->student('BSIT', [], alumni: true);

        $titles = $this->actingAs($alumni)->get(route('jobs.index'))->viewData('jobPostings')->getCollection()->pluck('title')->all();

        $this->assertSame(['Web Developer', 'Sales Clerk'], $titles);
    }

    public function test_the_coordinator_keeps_the_normal_order(): void
    {
        $this->job('Sales Clerk', 0);
        $this->job('Web Developer', 7);

        $response = $this->actingAs(User::factory()->jobCoordinator()->create())->get(route('jobs.index'))->assertOk();

        $this->assertSame(['Sales Clerk', 'Web Developer'], $response->viewData('jobPostings')->getCollection()->pluck('title')->all());
        $this->assertFalse($response->viewData('relevanceActive'));
        $response->assertDontSee('Related to your course');
    }

    // ---- the labels -------------------------------------------------------------

    public function test_only_the_related_cards_carry_the_label_and_the_note_explains_the_order(): void
    {
        $this->job('Sales Clerk', 0);
        $this->job('Kitchen Helper', 1);
        $this->job('IT Support Specialist', 3);
        $this->job('Web Developer', 5);
        $this->job('Network Technician', 10);

        $response = $this->actingAs($this->student('BSIT'))->get(route('jobs.index'))->assertOk();

        $this->assertSame(3, substr_count($response->getContent(), 'Related to your course'), 'one label per related card');
        $this->assertCount(3, $response->viewData('relatedJobIds'));
        $response->assertSee('Jobs related to your course and CV skills are listed first.');
    }

    public function test_no_label_and_no_note_when_nothing_is_related(): void
    {
        $this->job('Sales Clerk', 0);
        $this->job('Kitchen Helper', 1);

        $response = $this->actingAs($this->student('BSIT'))->get(route('jobs.index'))->assertOk();

        $this->assertFalse($response->viewData('relevanceActive'));
        $response->assertDontSee('Related to your course')->assertDontSee('listed first');

        $this->assertSame(['Sales Clerk', 'Kitchen Helper'], $response->viewData('jobPostings')->getCollection()->pluck('title')->all());
    }

    public function test_the_wording_never_calls_it_ai(): void
    {
        $this->job('Web Developer', 0);

        $html = $this->actingAs($this->student('BSIT'))->get(route('jobs.index'))->assertOk()->getContent();

        $this->assertStringNotContainsStringIgnoringCase('AI match', $html);
        $this->assertStringNotContainsStringIgnoringCase('artificial', $html);
        $this->assertStringNotContainsString('Recommended by AI', $html);
    }

    // ---- pagination and filters --------------------------------------------------

    public function test_pages_keep_the_order_and_every_job_appears_exactly_once(): void
    {
        // 15 unrelated jobs, newest; 5 related ones, all older than every unrelated job.
        foreach (range(1, 15) as $d) {
            $this->job("Plain Role {$d}", $d);
        }
        foreach (range(20, 24) as $d) {
            $this->job("Web Developer {$d}", $d);
        }

        $student = $this->student('BSIT');

        $page1 = $this->actingAs($student)->get(route('jobs.index'))->assertOk();
        $page2 = $this->get(route('jobs.index', ['page' => 2]))->assertOk();

        $first = $page1->viewData('jobPostings')->getCollection()->pluck('title')->all();
        $second = $page2->viewData('jobPostings')->getCollection()->pluck('title')->all();

        $this->assertSame(20, $page1->viewData('jobPostings')->total());

        // Related first (best, then newest), then the rest newest first, carried over to page 2.
        $this->assertSame(
            array_merge(
                array_map(fn ($d) => "Web Developer {$d}", range(20, 24)),
                array_map(fn ($d) => "Plain Role {$d}", range(1, 10))
            ),
            $first
        );
        $this->assertSame(array_map(fn ($d) => "Plain Role {$d}", range(11, 15)), $second);
        $this->assertCount(20, array_unique(array_merge($first, $second)));
    }

    public function test_search_and_filters_still_work_with_the_ordering(): void
    {
        $this->job('Web Developer', 0);
        $this->job('Sales Clerk', 1);
        $this->job('Kitchen Helper', 2, ['job_type' => 'part_time']);
        $this->job('Network Admin', 9, ['job_type' => 'part_time']);

        $student = $this->student('BSIT');

        $partTime = $this->actingAs($student)->get(route('jobs.index', ['job_type' => 'part_time']))->assertOk();
        $this->assertSame(['Network Admin', 'Kitchen Helper'], $partTime->viewData('jobPostings')->getCollection()->pluck('title')->all());

        // A search with no related job in it keeps the plain order and shows no label.
        $clerk = $this->get(route('jobs.index', ['search' => 'clerk']))->assertOk();
        $this->assertSame(['Sales Clerk'], $clerk->viewData('jobPostings')->getCollection()->pluck('title')->all());
        $this->assertFalse($clerk->viewData('relevanceActive'));
        $clerk->assertDontSee('Related to your course');

        $developer = $this->get(route('jobs.index', ['search' => 'DEVELOPER']))->assertOk();
        $this->assertSame(['Web Developer'], $developer->viewData('jobPostings')->getCollection()->pluck('title')->all());
        $this->assertTrue($developer->viewData('relevanceActive'));
    }

    // ---- the mapping ---------------------------------------------------------------

    public function test_the_mapping_comes_from_the_config_file(): void
    {
        config(['course_keywords.courses' => [
            'custom' => ['aliases' => ['bsit'], 'keywords' => ['zebra']],
        ]]);

        $this->job('Web Developer', 0);
        $this->job('Zebra Keeper', 7);

        $titles = $this->actingAs($this->student('BSIT'))->get(route('jobs.index'))
            ->viewData('jobPostings')->getCollection()->pluck('title')->all();

        $this->assertSame(['Zebra Keeper', 'Web Developer'], $titles);
    }

    public function test_the_course_can_be_written_in_different_ways(): void
    {
        foreach (['BSIT', 'bsit', 'BS IT', 'BS Information Technology', 'Bachelor of Science in Information Technology'] as $course) {
            $this->assertContains('developer', JobRelevance::courseKeywords($course), $course);
        }

        $this->assertContains('hotel', JobRelevance::courseKeywords('BSHM'));
        $this->assertContains('marketing', JobRelevance::courseKeywords('BSBA - Operations Management'));
        $this->assertSame([], JobRelevance::courseKeywords(''));
        $this->assertSame([], JobRelevance::courseKeywords(null));
        $this->assertNotContains('hotel', JobRelevance::courseKeywords('BSIT'));
    }

    public function test_a_course_that_is_not_in_the_config_uses_its_own_words(): void
    {
        $this->job('Sales Clerk', 0);
        $this->job('Psychology Aide', 5);

        $titles = $this->actingAs($this->student('BS Psychology'))->get(route('jobs.index'))
            ->viewData('jobPostings')->getCollection()->pluck('title')->all();

        $this->assertSame(['Psychology Aide', 'Sales Clerk'], $titles);
    }

    // ---- nothing is hidden, and the page stays light ----------------------------------

    public function test_jobs_outside_the_scored_pool_are_still_listed(): void
    {
        config(['course_keywords.pool' => 2]);   // only the two newest jobs are scored

        $this->job('Plain Role A', 0);
        $this->job('Plain Role B', 1);
        $this->job('Web Developer', 5);          // related, but outside the pool

        $response = $this->actingAs($this->student('BSIT'))->get(route('jobs.index'))->assertOk();

        $this->assertSame(3, $response->viewData('jobPostings')->total());
        $this->assertSame(['Plain Role A', 'Plain Role B', 'Web Developer'], $response->viewData('jobPostings')->getCollection()->pluck('title')->all());
    }

    public function test_only_open_approved_jobs_are_ever_listed_or_scored(): void
    {
        $this->job('Web Developer Open', 3);
        $this->job('Web Developer Pending', 1, ['approval_status' => 'pending']);
        $this->job('Web Developer Closed', 2, ['status' => 'closed']);
        $this->job('Web Developer Expired', 2, ['application_deadline' => now()->subDay()]);

        $titles = $this->actingAs($this->student('BSIT'))->get(route('jobs.index'))
            ->viewData('jobPostings')->getCollection()->pluck('title')->all();

        $this->assertSame(['Web Developer Open'], $titles);
    }

    public function test_the_number_of_queries_does_not_grow_with_the_number_of_jobs(): void
    {
        $student = $this->student('BSIT');

        foreach (range(1, 16) as $i) {
            $this->job($i % 2 ? "Web Developer {$i}" : "Plain Role {$i}", $i);
        }

        $this->queryCount($student);                 // warm-up: caches that fill once per request cycle
        $withSixteen = $this->queryCount($student);

        foreach (range(17, 40) as $i) {
            $this->job($i % 2 ? "Web Developer {$i}" : "Plain Role {$i}", $i);
        }

        $withForty = $this->queryCount($student);

        $this->assertSame($withSixteen, $withForty, 'scoring reads the pool in one query, whatever the number of jobs');
    }

    // ---- helpers -------------------------------------------------------------------

    private function queryCount(User $user): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($user)->get(route('jobs.index'))->assertOk();

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    /**
     * An open, approved job of the test company, posted $daysAgo days ago. Nothing in it matches a
     * student's keywords unless the test puts it there.
     */
    private function job(string $title, int $daysAgo, array $overrides = []): JobPosting
    {
        return JobPosting::factory()->for($this->company)->create(array_merge([
            'title' => $title,
            'description' => 'General duties.',
            'qualifications' => 'Graduate.',
            'requirements' => 'Willing to learn.',
            'location' => 'Alaminos City',
            'job_type' => 'full_time',
            'slots' => 5,
            'date_posted' => now()->subDays($daysAgo)->toDateString(),
            'posted_by' => $this->company->user->id,
            'approval_status' => 'approved',
            'status' => 'open',
            'application_deadline' => now()->addWeeks(3),
        ], $overrides));
    }

    /**
     * A student or alumnus with a finished basic profile, the given course and no skills unless listed.
     * Pass '' for the course and no skills to get an empty profile.
     */
    private function student(string $course, array $skills = [], bool $alumni = false): User
    {
        $factory = $alumni ? User::factory()->alumni()->withProfile() : User::factory()->withProfile();
        $user = $factory->create(['activated_at' => now()]);

        $profile = $user->profile()->first();
        $profile->update(['course_program' => $course, 'profile_completed_at' => now()]);

        foreach ($skills as $name) {
            $profile->skills()->create(['name' => $name]);
        }

        return $user->fresh();
    }
}
