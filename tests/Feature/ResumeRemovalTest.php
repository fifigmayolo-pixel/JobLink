<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResumeRemovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_resume_column_is_gone_and_the_cv_columns_are_there(): void
    {
        $this->assertFalse(Schema::hasColumn('job_applications', 'resume_path'));
        $this->assertTrue(Schema::hasColumn('job_applications', 'profile_id'));
        $this->assertTrue(Schema::hasColumn('job_applications', 'cv_snapshot'));
    }

    public function test_no_resume_code_is_left_in_the_application(): void
    {
        // Migrations and tests are not scanned: they legitimately mention the old names.
        $needles = ['resume_path', "'resumes'", 'applications.resume', 'downloadResume'];
        $hits = [];

        foreach (['app', 'routes', 'config', 'resources/views'] as $folder) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(base_path($folder), \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($files as $file) {
                if (! $file->isFile() || ! preg_match('/\.php$/', $file->getFilename())) {
                    continue;
                }

                // The cleanup command has to name the old folder to be able to delete it.
                if ($file->getFilename() === 'PurgeLegacyResumes.php') {
                    continue;
                }

                $contents = file_get_contents($file->getPathname());

                foreach ($needles as $needle) {
                    if (str_contains($contents, $needle)) {
                        $hits[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname())." contains {$needle}";
                    }
                }
            }
        }

        $this->assertSame([], $hits, "Leftover resume code:\n".implode("\n", $hits));
    }

    public function test_the_privacy_policy_no_longer_talks_about_uploaded_resumes(): void
    {
        $this->get(route('privacy'))
            ->assertOk()
            ->assertDontSee('resume')
            ->assertDontSee('Resume');
    }

    public function test_the_terms_no_longer_talk_about_resumes(): void
    {
        $terms = view('partials.terms-content')->render();

        $this->assertStringNotContainsStringIgnoringCase('resume', $terms);
    }

    // ---- the cleanup command -------------------------------------------------

    public function test_the_purge_command_does_a_dry_run_by_default(): void
    {
        Storage::fake();
        Storage::put('resumes/a.pdf', 'x');
        Storage::put('resumes/b.pdf', 'x');

        $this->artisan('joblink:purge-legacy-resumes')->assertExitCode(0);

        Storage::assertExists('resumes/a.pdf');
        Storage::assertExists('resumes/b.pdf');
    }

    public function test_the_purge_command_deletes_only_the_resumes_folder_and_only_when_confirmed(): void
    {
        Storage::fake();
        Storage::put('resumes/a.pdf', 'x');
        Storage::put('resumes/b.pdf', 'x');
        Storage::put('rosters/keep.xlsx', 'x');

        // Saying no changes nothing.
        $this->artisan('joblink:purge-legacy-resumes', ['--delete' => true])
            ->expectsConfirmation('Permanently delete 2 file(s) from the resumes folder? This cannot be undone.', 'no')
            ->assertExitCode(0);
        Storage::assertExists('resumes/a.pdf');

        // Saying yes removes the resumes and nothing else.
        $this->artisan('joblink:purge-legacy-resumes', ['--delete' => true])
            ->expectsConfirmation('Permanently delete 2 file(s) from the resumes folder? This cannot be undone.', 'yes')
            ->assertExitCode(0);

        Storage::assertMissing('resumes/a.pdf');
        Storage::assertMissing('resumes/b.pdf');
        Storage::assertExists('rosters/keep.xlsx');
    }
}
