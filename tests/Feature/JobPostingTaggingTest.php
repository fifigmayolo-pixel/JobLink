<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobPostingTaggingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_a_company_posting_a_job_is_auto_tagged_to_itself(): void
    {
        $company = Company::factory()->create();

        $this->actingAs($company->user)->post(route('jobs.store'), $this->jobPayload());

        $this->assertDatabaseHas('job_postings', [
            'title' => 'QA Tester',
            'company_id' => $company->id,
            'posted_by' => $company->user->id,
        ]);
    }

    public function test_coordinator_must_choose_a_company_to_tag(): void
    {
        $coordinator = User::factory()->jobCoordinator()->create();

        $response = $this->actingAs($coordinator)->post(route('jobs.store'), $this->jobPayload());

        $response->assertSessionHasErrors('company_id');
        $this->assertDatabaseMissing('job_postings', ['title' => 'QA Tester']);
    }

    public function test_a_student_cannot_post_a_job(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->post(route('jobs.store'), $this->jobPayload());

        $response->assertForbidden();
    }

    private function jobPayload(): array
    {
        return [
            'title' => 'QA Tester',
            'description' => 'Test description',
            'qualifications' => 'Test qualifications',
            'requirements' => 'Test requirements',
            'location' => 'Alaminos City',
            'application_deadline' => now()->addWeek()->toDateString(),
        ];
    }
}