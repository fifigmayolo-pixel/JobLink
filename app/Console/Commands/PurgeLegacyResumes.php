<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Before the profile-based CV, applicants uploaded PDF resumes to the "resumes"
 * folder of the private disk (the private Supabase bucket in production).
 * Nothing reads them any more. This finds them and, only when asked, deletes them.
 *
 *   php artisan joblink:purge-legacy-resumes            (dry run: just counts)
 *   php artisan joblink:purge-legacy-resumes --delete   (asks, then deletes)
 *
 * Only the "resumes" folder is touched. Roster spreadsheets and everything else
 * in the same bucket are left alone.
 */
class PurgeLegacyResumes extends Command
{
    protected $signature = 'joblink:purge-legacy-resumes {--delete : Actually delete the files (the default is a dry run)}';

    protected $description = 'Find (and optionally delete) old PDF resumes left in private storage';

    public function handle(): int
    {
        // The default disk: local storage in development, the private bucket when FILESYSTEM_DISK=s3.
        $disk = Storage::disk();
        $files = $disk->allFiles('resumes');
        $count = count($files);

        if ($count === 0) {
            $this->info('No legacy resume files found. Nothing to do.');

            return self::SUCCESS;
        }

        $this->line("Found {$count} legacy resume file(s) in the \"resumes\" folder.");

        if (! $this->option('delete')) {
            $this->warn('Dry run: nothing was deleted. Run again with --delete to remove them.');

            return self::SUCCESS;
        }

        if (! $this->confirm("Permanently delete {$count} file(s) from the resumes folder? This cannot be undone.", false)) {
            $this->line('Cancelled. Nothing was deleted.');

            return self::SUCCESS;
        }

        foreach (array_chunk($files, 100) as $chunk) {
            $disk->delete($chunk);
        }

        $this->info("Deleted {$count} file(s).");

        return self::SUCCESS;
    }
}
