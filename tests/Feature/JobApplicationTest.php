<?php

namespace Tests\Feature;

use App\Models\JobPosting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JobApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_student_can_apply_with_a_pdf_resume(): void
    {
        Storage::fake('local');

        $student = User::factory()->create(['role' => 'student']);
        $job = JobPosting::factory()->create();

        $response = $this->actingAs($student)->post(route('applications.store', $job), [
            'resume' => UploadedFile::fake()->create('resume.pdf', 500, 'application/pdf'),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('job_applications', [
            'job_posting_id' => $job->id,
            'applicant_id' => $student->id,
            'status' => 'pending',
        ]);
    }

    public function test_student_cannot_apply_twice_to_the_same_job(): void
    {
        Storage::fake('local');

        $student = User::factory()->create(['role' => 'student']);
        $job = JobPosting::factory()->create();
        $job->applications()->create([
            'applicant_id' => $student->id,
            'resume_path' => 'resumes/existing.pdf',
            'date_applied' => now(),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($student)->post(route('applications.store', $job), [
            'resume' => UploadedFile::fake()->create('resume.pdf', 500, 'application/pdf'),
        ]);

        $response->assertSessionHasErrors('resume');
        $this->assertSame(1, $job->applications()->count());
    }

    public function test_student_cannot_apply_to_a_closed_job(): void
    {
        Storage::fake('local');

        $student = User::factory()->create(['role' => 'student']);
        $job = JobPosting::factory()->closed()->create();

        $response = $this->actingAs($student)->post(route('applications.store', $job), [
            'resume' => UploadedFile::fake()->create('resume.pdf', 500, 'application/pdf'),
        ]);

        $response->assertForbidden();
    }

    public function test_non_pdf_upload_is_rejected(): void
    {
        Storage::fake('local');

        $student = User::factory()->create(['role' => 'student']);
        $job = JobPosting::factory()->create();

        $response = $this->actingAs($student)->post(route('applications.store', $job), [
            'resume' => UploadedFile::fake()->create('resume.docx', 500, 'application/msword'),
        ]);

        $response->assertSessionHasErrors('resume');
    }
}