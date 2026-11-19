<?php

namespace App\Support;

use App\Models\ProfileClearance;
use App\Models\StudentAlumniProfile;
use Carbon\CarbonInterface;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Saves, replaces and removes clearance PDFs, so the rules live in one place:
 *
 *   - the file goes to a private disk, under a random name (never the uploaded name);
 *   - a replaced file is deleted from storage once the new one is saved;
 *   - if saving the row fails, the new file is deleted again, so nothing is left behind.
 */
class ClearanceStore
{
    /**
     * The private disk. Refuses the public one: a clearance must never be reachable by URL.
     */
    public static function diskName(): string
    {
        $name = config('clearances.disk') ?: config('filesystems.default');

        if ($name === 'public') {
            throw new RuntimeException('Clearances must never be stored on the public disk.');
        }

        return $name;
    }

    public static function disk(): FilesystemAdapter
    {
        return Storage::disk(static::diskName());
    }

    /**
     * Save a PDF as the profile's clearance of this type, replacing any earlier one.
     * The returned model has wasRecentlyCreated true for a first upload and false for a replacement.
     */
    public function save(StudentAlumniProfile $profile, string $type, UploadedFile $file, CarbonInterface $validUntil): ProfileClearance
    {
        $disk = static::disk();

        $folder = trim((string) config('clearances.folder', 'clearances'), '/').'/'.$profile->id;
        $name = $type.'-'.Str::lower((string) Str::ulid()).'.pdf';

        $path = $disk->putFileAs($folder, $file, $name);

        if ($path === false || $path === null) {
            throw new RuntimeException('The clearance file could not be written to storage.');
        }

        try {
            [$clearance, $oldPath] = DB::transaction(function () use ($profile, $type, $path, $file, $validUntil) {
                $attributes = [
                    'file_path' => $path,
                    'size_bytes' => (int) $file->getSize(),
                    'valid_until' => $validUntil->toDateString(),
                ];

                $existing = ProfileClearance::query()
                    ->where('profile_id', $profile->id)
                    ->where('type', $type)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    $oldPath = $existing->file_path;
                    $existing->update($attributes);

                    return [$existing, $oldPath];
                }

                return [
                    ProfileClearance::create($attributes + ['profile_id' => $profile->id, 'type' => $type]),
                    null,
                ];
            });
        } catch (Throwable $e) {
            $this->deleteQuietly($path);

            throw $e;
        }

        // The old file goes only after the new one is safely saved.
        if ($oldPath !== null && $oldPath !== $path) {
            $this->deleteQuietly($oldPath);
        }

        return $clearance;
    }

    /**
     * Remove the clearance row and its file.
     */
    public function remove(ProfileClearance $clearance): void
    {
        $path = $clearance->file_path;

        $clearance->delete();

        $this->deleteQuietly($path);
    }

    /**
     * A file that cannot be deleted is reported, not shown to the user: the row is already
     * gone or replaced, so nobody can open it any more.
     */
    private function deleteQuietly(string $path): void
    {
        try {
            static::disk()->delete($path);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
