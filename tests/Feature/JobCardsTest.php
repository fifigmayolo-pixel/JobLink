<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobPosting;
use App\Models\User;
use App\Support\JobCardData;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class JobCardsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Cache::forget('landing.featured-jobs.v2');
    }

    // ---- the Open Positions list ---------------------------------------------

    public function test_the_list_shows_open_approved_jobs_as_cards_with_their_details(): void
    {
        $company = Company::factory()->create(['company_name' => 'Acme Corp']);

        $this->job($company, [
            'title' => 'Visible Developer',
            'location' => 'Alaminos City, Pangasinan',
            'job_type' => 'full_time',
            'description' => "First line of the description.\nSecond line here.",
        ]);
        $this->job($company, ['title' => 'Hidden Pending', 'approval_status' => 'pending']);
        $this->job($company, ['title' => 'Hidden Closed', 'status' => 'closed']);
        $this->job($company, ['title' => 'Hidden Expired', 'application_deadline' => now()->subDay()]);

        $student = User::factory()->cvReady()->create();

        $this->actingAs($student)->get(route('jobs.index'))
            ->assertOk()
            ->assertSee('Visible Developer')
            ->assertSee('Acme Corp')
            ->assertSee('Alaminos City, Pangasinan')
            ->assertSee('Full-time')
            ->assertSee('First line of the description. Second line here.')
            ->assertSee('Posted today')
            ->assertSee('View job')
            ->assertSee('1 open position')
            ->assertDontSee('Hidden Pending')
            ->assertDontSee('Hidden Closed')
            ->assertDontSee('Hidden Expired');
    }

    public function test_only_students_and_alumni_get_the_save_button(): void
    {
        $company = Company::factory()->create();
        $this->job($company, ['title' => 'Some Job']);

        $this->actingAs(User::factory()->cvReady()->create())->get(route('jobs.index'))
            ->assertOk()
            ->assertSee('aria-label="Save job"', false);

        $this->actingAs($company->user)->get(route('jobs.index'))
            ->assertOk()
            ->assertSee('Some Job')
            ->assertDontSee('aria-label="Save job"', false);
    }

    public function test_a_saved_job_shows_as_saved_on_its_card(): void
    {
        $student = User::factory()->cvReady()->create();
        $job = $this->job(Company::factory()->create(), ['title' => 'Bookmark Me']);

        $this->actingAs($student)->post(route('saved-jobs.store', $job));

        $this->get(route('jobs.index'))
            ->assertOk()
            ->assertSee('Remove from saved jobs');
    }

    public function test_filters_narrow_the_list_and_survive_paging(): void
    {
        $company = Company::factory()->create();
        foreach (range(1, 16) as $n) {
            $this->job($company, ['title' => "Alpha Role $n"]);
        }
        $this->job($company, ['title' => 'Beta Designer', 'job_type' => 'internship']);

        $student = User::factory()->cvReady()->create();

        // Page 2 of the search keeps the search in its links (needs ->withQueryString() in the controller).
        $this->actingAs($student)->get(route('jobs.index', ['search' => 'Alpha', 'page' => 2]))
            ->assertOk()
            ->assertSee('search=Alpha', false)
            ->assertDontSee('Beta Designer');

        // The job type filter, including Internship.
        $this->get(route('jobs.index', ['job_type' => 'internship']))
            ->assertOk()
            ->assertSee('Beta Designer')
            ->assertDontSee('Alpha Role 1');
    }

    public function test_the_empty_state_when_no_job_is_open(): void
    {
        $this->actingAs(User::factory()->cvReady()->create())->get(route('jobs.index'))
            ->assertOk()
            ->assertSee('No open positions right now')
            ->assertDontSee('Clear filters');
    }

    public function test_the_empty_state_when_a_search_matches_nothing(): void
    {
        $this->job(Company::factory()->create(), ['title' => 'Some Job']);

        $this->actingAs(User::factory()->cvReady()->create())
            ->get(route('jobs.index', ['search' => 'zzzz-no-such-job']))
            ->assertOk()
            ->assertSee('No jobs match your search')
            ->assertSee('Clear filters');
    }

    public function test_a_job_from_an_archived_company_is_not_listed_anywhere_public(): void
    {
        $company = Company::factory()->create();
        $this->job($company, ['title' => 'Archived Company Job']);
        $company->user->delete(); // archiving the company login soft-deletes its user

        $this->get('/')->assertOk()->assertDontSee('Archived Company Job');

        $this->actingAs(User::factory()->cvReady()->create())->get(route('jobs.index'))
            ->assertOk()
            ->assertDontSee('Archived Company Job');
    }

    // ---- the landing page ----------------------------------------------------

    public function test_the_landing_card_shows_the_description_and_posting_date(): void
    {
        $this->job(Company::factory()->create(['company_name' => 'Landing Co']), [
            'title' => 'Landing Page Job',
            'description' => "Build and maintain the company website.\nWork with the design team.",
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Landing Page Job')
            ->assertSee('Landing Co')
            ->assertSee('Build and maintain the company website. Work with the design team.')
            ->assertSee('Posted today')
            ->assertSee('View job');
    }

    public function test_the_landing_page_keeps_its_empty_state(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('New openings are on the way');
    }

    // ---- the shared helper ---------------------------------------------------

    public function test_the_card_helper_wording(): void
    {
        $this->assertSame('Posted today', JobCardData::postedLabel(now()));
        $this->assertSame('Posted yesterday', JobCardData::postedLabel(now()->subDay()));
        $this->assertSame('Posted 5 days ago', JobCardData::postedLabel(now()->subDays(5)));
        $this->assertSame('Posted '.now()->subDays(45)->format('M j'), JobCardData::postedLabel(now()->subDays(45)));
        $this->assertNull(JobCardData::postedLabel(null));

        $this->assertTrue(JobCardData::isNew(now()->subDays(7)));
        $this->assertFalse(JobCardData::isNew(now()->subDays(8)));

        $this->assertSame(3, JobCardData::daysLeft(now()->addDays(3)));
        $this->assertSame(0, JobCardData::daysLeft(now()));
        $this->assertSame(-1, JobCardData::daysLeft(now()->subDay()));

        $this->assertSame('Full-time', JobCardData::typeLabel('full_time'));
        $this->assertSame('Internship', JobCardData::typeLabel('internship'));
        $this->assertSame('Contract Based', JobCardData::typeLabel('contract_based'));
    }

    // ---- helpers -------------------------------------------------------------

    private function job(Company $company, array $overrides = []): JobPosting
    {
        return JobPosting::factory()->for($company)->create(array_merge([
            'approval_status' => 'approved',
            'status' => 'open',
        ], $overrides));
    }
}
