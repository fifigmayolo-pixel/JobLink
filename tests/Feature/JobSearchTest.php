<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobPosting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Job search ignores upper and lower case. SQLite does that on its own, PostgreSQL (the
 * live database) does not, so the controller lower-cases both sides. These tests keep the
 * behavior; the live check is to search for "developer" on the deployed site.
 */
class JobSearchTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->company = Company::factory()->create(['company_name' => 'Alpha Corp']);
    }

    public function test_students_find_jobs_by_title_in_any_case(): void
    {
        $this->job(['title' => 'Junior Web Developer', 'location' => 'Alaminos City, Pangasinan']);
        $this->job(['title' => 'Cashier', 'location' => 'Lingayen, Pangasinan']);

        $this->actingAs(User::factory()->cvReady()->create());

        foreach (['developer', 'DEVELOPER', 'Web Developer', 'web DEVELOPER'] as $search) {
            $this->get(route('jobs.index', ['search' => $search]))
                ->assertOk()
                ->assertSee('Junior Web Developer')
                ->assertDontSee('Cashier');
        }
    }

    public function test_students_find_jobs_by_location_in_any_case(): void
    {
        $this->job(['title' => 'Junior Web Developer', 'location' => 'Alaminos City, Pangasinan']);
        $this->job(['title' => 'Cashier', 'location' => 'Lingayen, Pangasinan']);

        $this->actingAs(User::factory()->cvReady()->create());

        $this->get(route('jobs.index', ['location' => 'ALAMINOS']))
            ->assertSee('Junior Web Developer')
            ->assertDontSee('Cashier');

        $this->get(route('jobs.index', ['location' => 'lingayen']))
            ->assertSee('Cashier')
            ->assertDontSee('Junior Web Developer');
    }

    public function test_the_public_search_still_only_lists_open_approved_jobs(): void
    {
        $this->job(['title' => 'Open Clerk']);
        $this->job(['title' => 'Pending Clerk', 'approval_status' => 'pending']);
        $this->job(['title' => 'Closed Clerk', 'status' => 'closed']);

        $this->actingAs(User::factory()->cvReady()->create())
            ->get(route('jobs.index', ['search' => 'CLERK']))
            ->assertOk()
            ->assertSee('Open Clerk')
            ->assertDontSee('Pending Clerk')
            ->assertDontSee('Closed Clerk');
    }

    public function test_a_company_finds_its_own_jobs_by_title_in_any_case(): void
    {
        $this->job(['title' => 'Alpha Open Role']);
        $this->job(['title' => 'Alpha Closed Role', 'status' => 'closed']);

        $beta = Company::factory()->create(['company_name' => 'Beta Corp']);
        $this->job(['title' => 'Beta Closed Role', 'status' => 'closed'], $beta);

        $this->actingAs($this->company->user);

        $this->get(route('jobs.index', ['search' => 'CLOSED']))
            ->assertOk()
            ->assertSee('Alpha Closed Role')
            ->assertDontSee('Alpha Open Role')
            ->assertDontSee('Beta Closed Role');

        $this->get(route('jobs.index', ['search' => 'open ROLE']))
            ->assertSee('Alpha Open Role')
            ->assertDontSee('Alpha Closed Role');
    }

    public function test_a_search_value_that_is_not_text_is_ignored(): void
    {
        $this->job(['title' => 'Alpha Open Role']);

        // A hand-made ?search[]=x must not break the company list.
        $this->actingAs($this->company->user)
            ->get(route('jobs.index', ['search' => ['x']]))
            ->assertOk()
            ->assertSee('Alpha Open Role');
    }

    private function job(array $overrides = [], ?Company $company = null): JobPosting
    {
        $company ??= $this->company;

        return JobPosting::factory()->for($company)->create(array_merge([
            'posted_by' => $company->user->id,
            'approval_status' => 'approved',
            'status' => 'open',
            'application_deadline' => now()->addWeeks(2),
        ], $overrides));
    }
}
