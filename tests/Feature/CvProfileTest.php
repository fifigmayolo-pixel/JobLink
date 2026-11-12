<?php

namespace Tests\Feature;

use App\Models\JobPosting;
use App\Models\StudentAlumniProfile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CvProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_a_new_profile_has_an_empty_cv_and_is_not_ready(): void
    {
        $profile = $this->profileOf(User::factory()->withProfile()->create());

        $cv = $profile->cvCompleteness();

        $this->assertSame(0, $cv['percent']);
        $this->assertFalse($cv['ready']);
        $this->assertFalse($profile->cvReady());
        $this->assertSame(['Personal summary', 'Skills'], $cv['missing_required']);
    }

    public function test_a_summary_and_one_skill_make_the_cv_ready(): void
    {
        $profile = $this->profileOf(User::factory()->cvReady()->create());

        $this->assertTrue($profile->cvReady());
        $this->assertSame(55, $profile->cvCompleteness()['percent']);
    }

    public function test_a_short_summary_does_not_count(): void
    {
        $profile = $this->profileOf(User::factory()->cvReady()->create());

        $profile->update(['cv_summary' => 'Too short.']);

        $this->assertFalse($profile->cvReady());
    }

    public function test_every_section_together_reaches_100_percent(): void
    {
        $profile = $this->profileOf(User::factory()->cvReady()->create());

        $profile->experiences()->create([
            'kind' => 'ojt',
            'position' => 'IT Intern',
            'organization' => 'City Hall',
            'start_date' => '2025-06-01',
        ]);
        $this->assertSame(75, $profile->cvCompleteness()['percent']);

        $profile->educations()->create([
            'school_name' => 'Alaminos City National High School',
            'level' => 'Senior High School',
            'start_year' => 2018,
            'end_year' => 2020,
        ]);
        $profile->certifications()->create([
            'kind' => 'seminar',
            'title' => 'Digital Skills Seminar',
        ]);
        $profile->update(['linkedin_url' => 'https://www.linkedin.com/in/test']);

        $this->assertSame(100, $profile->cvCompleteness()['percent']);
    }

    public function test_the_same_skill_cannot_be_added_twice(): void
    {
        $profile = $this->profileOf(User::factory()->cvReady()->create());

        $this->expectException(QueryException::class);

        $profile->skills()->create(['name' => 'Communication']); // cvReady() already added it
    }

    public function test_a_removed_profile_keeps_the_submitted_cv_on_the_application(): void
    {
        $user = User::factory()->cvReady()->create();
        $profile = $this->profileOf($user);
        $job = JobPosting::factory()->create();

        $application = $job->applications()->create([
            'applicant_id' => $user->id,
            'profile_id' => $profile->id,
            'cv_snapshot' => ['v' => 1, 'summary' => 'Snapshot text'],
            'date_applied' => now(),
            'status' => 'pending',
        ]);

        $this->assertSame(['v' => 1, 'summary' => 'Snapshot text'], $application->fresh()->cv_snapshot);

        $profile->delete();

        $application = $application->fresh();
        $this->assertNull($application->profile_id);
        $this->assertSame('Snapshot text', $application->cv_snapshot['summary']);
        $this->assertDatabaseMissing('profile_skills', ['profile_id' => $profile->id]);
    }

    private function profileOf(User $user): StudentAlumniProfile
    {
        return $user->profile()->first();
    }
}