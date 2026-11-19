<?php

namespace Tests\Concerns;

use App\Models\ProfileClearance;
use App\Models\User;
use App\Support\ClearanceStore;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

/**
 * Helpers shared by the clearance tests (Phase 14).
 */
trait MakesClearances
{
    /** Swap the private disk for an in-memory fake, so no test writes a real file. */
    protected function fakeClearanceDisk(): void
    {
        Storage::fake(ClearanceStore::diskName());
    }

    /**
     * A small upload that really starts with %PDF-. (UploadedFile::fake()->create() writes an
     * empty file, which the server rightly refuses.)
     */
    protected function pdf(string $name = 'clearance.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj\n<< >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n");
    }

    /**
     * Post one clearance the way the page does: one file, its date and the consent tick.
     * Anything in $overrides replaces the default (pass 'consent' => null to leave it out).
     */
    protected function uploadClearance(User $user, string $type, array $overrides = []): TestResponse
    {
        return $this->actingAs($user)->post(route('clearances.store', $type), array_merge([
            'doc' => $type,
            'file' => $this->pdf($type.'.pdf'),
            'valid_until' => now()->addMonths(6)->toDateString(),
            'consent' => '1',
        ], $overrides));
    }

    /** Put a real file on the fake disk where a clearance row says it is. Returns its content. */
    protected function putClearanceFile(ProfileClearance $clearance, ?string $content = null): string
    {
        $content ??= "%PDF-1.4\n%".$clearance->type." clearance for tests\n%%EOF\n";

        ClearanceStore::disk()->put($clearance->file_path, $content);

        return $content;
    }

    /** A student who can apply except for the clearances (none uploaded). */
    protected function studentWithoutClearances(): User
    {
        return User::factory()->cvReadyWithoutClearances()->create();
    }

    protected function clearanceOf(User $user, string $type): ?ProfileClearance
    {
        return ProfileClearance::query()
            ->where('profile_id', $user->profile()->first()->id)
            ->where('type', $type)
            ->first();
    }
}
