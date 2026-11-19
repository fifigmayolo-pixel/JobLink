<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\FieldChangeLog;
use App\Models\ProfileClearance;
use App\Models\User;
use App\Support\ClearanceStore;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Concerns\MakesClearances;
use Tests\TestCase;

/**
 * Phase 14: uploading, replacing and removing the NBI and barangay clearance.
 * The rules under test: PDF only, 4 MB, a real validity date, a private disk with random
 * names, and the old file deleted whenever it is replaced or removed.
 */
class ClearanceUploadTest extends TestCase
{
    use MakesClearances;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->fakeClearanceDisk();
    }

    // ---- a normal upload ----------------------------------------------------

    public function test_a_student_uploads_a_pdf_with_its_validity_date(): void
    {
        $user = $this->studentWithoutClearances();
        $profile = $user->profile()->first();
        $validUntil = now()->addMonths(6)->toDateString();

        $this->uploadClearance($user, 'nbi', ['valid_until' => $validUntil, 'file' => $this->pdf('My Secret NBI Copy.pdf')])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('clearances.edit'));

        $clearance = ProfileClearance::firstOrFail();

        $this->assertSame($profile->id, $clearance->profile_id);
        $this->assertSame('nbi', $clearance->type);
        $this->assertSame($validUntil, $clearance->valid_until->toDateString());
        $this->assertGreaterThan(0, $clearance->size_bytes);

        // Saved on the private disk under a random name: nothing the person typed is in the path.
        $this->assertStringStartsWith('clearances/'.$profile->id.'/nbi-', $clearance->file_path);
        $this->assertStringEndsWith('.pdf', $clearance->file_path);
        $this->assertStringNotContainsStringIgnoringCase('secret', $clearance->file_path);
        Storage::disk(ClearanceStore::diskName())->assertExists($clearance->file_path);

        $this->assertNotNull($profile->fresh()->clearance_consent_at);

        $this->assertDatabaseHas('field_change_logs', [
            'action' => 'clearance_uploaded',
            'user_id' => $user->id,
            'changed_by' => $user->id,
            'field_name' => 'nbi_clearance',
        ]);
    }

    public function test_an_alumnus_can_upload_both_clearances(): void
    {
        $user = User::factory()->alumni()->cvReadyWithoutClearances()->create();

        $this->uploadClearance($user, 'nbi')->assertSessionHasNoErrors();
        $this->uploadClearance($user->fresh(), 'barangay', ['consent' => null])->assertSessionHasNoErrors();

        $this->assertSame(2, ProfileClearance::count());
        $this->assertNotNull($this->clearanceOf($user, 'nbi'));
        $this->assertNotNull($this->clearanceOf($user, 'barangay'));
    }

    public function test_only_students_and_alumni_can_upload(): void
    {
        foreach ([Company::factory()->create()->user, User::factory()->jobCoordinator()->create()] as $other) {
            $status = $this->uploadClearance($other, 'nbi')->getStatusCode();

            $this->assertContains($status, [302, 403, 404]);
        }

        $this->assertSame(0, ProfileClearance::count());
        $this->assertSame([], Storage::disk(ClearanceStore::diskName())->allFiles());
    }

    public function test_a_guest_is_sent_to_login(): void
    {
        $this->post(route('clearances.store', 'nbi'), ['file' => $this->pdf(), 'valid_until' => now()->addMonth()->toDateString()])
            ->assertRedirect(route('login'));

        $this->assertSame(0, ProfileClearance::count());
    }

    public function test_an_unknown_document_type_is_not_found(): void
    {
        $user = $this->studentWithoutClearances();

        $this->actingAs($user)
            ->post('/my-clearances/passport', ['file' => $this->pdf(), 'valid_until' => now()->addMonth()->toDateString(), 'consent' => '1'])
            ->assertNotFound();

        $this->assertSame(0, ProfileClearance::count());
    }

    // ---- the file rules -----------------------------------------------------

    public function test_anything_that_is_not_a_pdf_is_refused(): void
    {
        $jpeg = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01";

        $files = [
            'a photo' => UploadedFile::fake()->createWithContent('photo.jpg', $jpeg),
            'a Word file' => UploadedFile::fake()->createWithContent('letter.docx', 'PK'),
            'a program' => UploadedFile::fake()->createWithContent('setup.exe', 'MZ'),
            'a text file' => UploadedFile::fake()->createWithContent('notes.txt', 'hello'),
            // The name says PDF but the content is not: the signature check catches these.
            'a photo renamed to .pdf' => UploadedFile::fake()->createWithContent('scan.pdf', $jpeg),
            'a web page renamed to .pdf' => UploadedFile::fake()->createWithContent('page.pdf', '<html><script>alert(1)</script></html>'),
            'an empty file named .pdf' => UploadedFile::fake()->createWithContent('empty.pdf', ''),
        ];

        $user = $this->studentWithoutClearances();

        foreach ($files as $what => $file) {
            $this->uploadClearance($user, 'nbi', ['file' => $file])
                ->assertSessionHasErrors('file', null, 'default');

            $this->assertSame(0, ProfileClearance::count(), $what.' must not be saved');
            $this->assertSame([], Storage::disk(ClearanceStore::diskName())->allFiles(), $what.' must not be stored');
        }
    }

    public function test_the_limit_is_four_megabytes(): void
    {
        $user = $this->studentWithoutClearances();

        // One kilobyte over.
        $this->uploadClearance($user, 'nbi', ['file' => UploadedFile::fake()->create('big.pdf', 4097, 'application/pdf')])
            ->assertSessionHasErrors('file');
        $this->assertSame(0, ProfileClearance::count());

        // Exactly 4 MB is fine.
        $exact = UploadedFile::fake()->createWithContent('exact.pdf', '%PDF-1.4'.str_repeat(' ', 4096 * 1024 - 8));

        $this->uploadClearance($user, 'nbi', ['file' => $exact])->assertSessionHasNoErrors();
        $this->assertSame(1, ProfileClearance::count());
    }

    public function test_a_file_is_required(): void
    {
        $user = $this->studentWithoutClearances();

        $this->actingAs($user)->post(route('clearances.store', 'nbi'), [
            'valid_until' => now()->addMonth()->toDateString(),
            'consent' => '1',
        ])->assertSessionHasErrors('file');
    }

    // ---- the validity date --------------------------------------------------

    public function test_the_validity_date_has_to_be_real_current_and_not_far_ahead(): void
    {
        $user = $this->studentWithoutClearances();

        $bad = [
            'missing' => null,
            'not a date' => 'next year',
            'wrong format' => '15/03/2027',
            'yesterday' => now()->subDay()->toDateString(),
            'three years ahead' => now()->addYears(3)->toDateString(),
        ];

        foreach ($bad as $what => $value) {
            $this->uploadClearance($user, 'nbi', ['valid_until' => $value])
                ->assertSessionHasErrors('valid_until');

            $this->assertSame(0, ProfileClearance::count(), $what.' must not be saved');
        }

        // Today is still valid (it expires after today), and so is the two-year limit.
        $this->uploadClearance($user, 'nbi', ['valid_until' => now()->toDateString()])->assertSessionHasNoErrors();
        $this->uploadClearance($user->fresh(), 'barangay', ['valid_until' => now()->addYears(2)->toDateString(), 'consent' => null])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, ProfileClearance::count());
    }

    // ---- consent ------------------------------------------------------------

    public function test_the_first_upload_needs_the_consent_tick_and_later_ones_do_not(): void
    {
        $user = $this->studentWithoutClearances();

        $this->uploadClearance($user, 'nbi', ['consent' => null])->assertSessionHasErrors('consent');
        $this->assertSame(0, ProfileClearance::count());
        $this->assertSame([], Storage::disk(ClearanceStore::diskName())->allFiles());
        $this->assertNull($user->profile()->first()->clearance_consent_at);

        // The page asks for it...
        $this->actingAs($user->fresh())->get(route('clearances.edit'))->assertOk()->assertSee('name="consent"', false);

        $this->uploadClearance($user->fresh(), 'nbi')->assertSessionHasNoErrors();
        $this->assertNotNull($user->profile()->first()->clearance_consent_at);

        // ...and stops asking once the person has agreed.
        $this->uploadClearance($user->fresh(), 'barangay', ['consent' => null])->assertSessionHasNoErrors();

        $this->actingAs($user->fresh())->get(route('clearances.edit'))
            ->assertOk()
            ->assertDontSee('name="consent"', false)
            ->assertSee('You agreed on');
    }

    // ---- replacing and removing ---------------------------------------------

    public function test_replacing_a_clearance_deletes_the_old_file(): void
    {
        $user = $this->studentWithoutClearances();
        $disk = Storage::disk(ClearanceStore::diskName());

        $this->uploadClearance($user, 'nbi', ['valid_until' => now()->addMonths(3)->toDateString()]);
        $oldPath = $this->clearanceOf($user, 'nbi')->file_path;
        $disk->assertExists($oldPath);

        $newDate = now()->addMonths(8)->toDateString();
        $this->uploadClearance($user->fresh(), 'nbi', ['valid_until' => $newDate])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', fn ($message) => str_contains($message, 'old file was deleted'));

        $this->assertSame(1, ProfileClearance::count(), 'still one row for this document');

        $clearance = $this->clearanceOf($user, 'nbi');
        $this->assertNotSame($oldPath, $clearance->file_path);
        $this->assertSame($newDate, $clearance->valid_until->toDateString());

        $disk->assertMissing($oldPath);
        $disk->assertExists($clearance->file_path);
        $this->assertCount(1, $disk->allFiles(), 'only the new file is left in storage');

        $this->assertDatabaseHas('field_change_logs', ['action' => 'clearance_replaced', 'user_id' => $user->id, 'field_name' => 'nbi_clearance']);
    }

    public function test_a_failed_replacement_keeps_the_old_file_and_date(): void
    {
        $user = $this->studentWithoutClearances();
        $disk = Storage::disk(ClearanceStore::diskName());

        $this->uploadClearance($user, 'nbi', ['valid_until' => now()->addMonths(3)->toDateString()]);
        $before = $this->clearanceOf($user, 'nbi');

        // Bad date: nothing changes.
        $this->uploadClearance($user->fresh(), 'nbi', ['valid_until' => now()->subDay()->toDateString()])
            ->assertSessionHasErrors('valid_until');

        // Not a PDF: nothing changes.
        $this->uploadClearance($user->fresh(), 'nbi', ['file' => UploadedFile::fake()->createWithContent('x.pdf', 'not a pdf')])
            ->assertSessionHasErrors('file');

        $after = $this->clearanceOf($user, 'nbi');
        $this->assertSame($before->file_path, $after->file_path);
        $this->assertSame($before->valid_until->toDateString(), $after->valid_until->toDateString());
        $disk->assertExists($before->file_path);
        $this->assertCount(1, $disk->allFiles());
    }

    public function test_removing_a_clearance_deletes_its_file(): void
    {
        $user = $this->studentWithoutClearances();
        $disk = Storage::disk(ClearanceStore::diskName());

        $this->uploadClearance($user, 'barangay');
        $path = $this->clearanceOf($user, 'barangay')->file_path;

        $this->actingAs($user->fresh())->delete(route('clearances.destroy', 'barangay'))
            ->assertRedirect(route('clearances.edit'))
            ->assertSessionHas('status', fn ($message) => str_contains($message, 'file deleted'));

        $this->assertNull($this->clearanceOf($user, 'barangay'));
        $disk->assertMissing($path);
        $this->assertDatabaseHas('field_change_logs', ['action' => 'clearance_removed', 'user_id' => $user->id]);

        // Removing something that is not there is harmless.
        $this->delete(route('clearances.destroy', 'barangay'))
            ->assertRedirect(route('clearances.edit'))
            ->assertSessionHas('status', fn ($message) => str_contains($message, 'no Barangay clearance'));
    }

    public function test_a_student_only_ever_touches_their_own_clearance(): void
    {
        $maria = $this->studentWithoutClearances();
        $other = $this->studentWithoutClearances();

        $this->uploadClearance($maria, 'nbi');
        $path = $this->clearanceOf($maria, 'nbi')->file_path;

        // The other student uploads and removes THEIR nbi. Maria's is untouched.
        $this->uploadClearance($other, 'nbi');
        $this->actingAs($other->fresh())->delete(route('clearances.destroy', 'nbi'));

        $this->assertNotNull($this->clearanceOf($maria, 'nbi'));
        Storage::disk(ClearanceStore::diskName())->assertExists($path);
    }

    // ---- storage ------------------------------------------------------------

    public function test_clearances_are_never_stored_on_the_public_disk(): void
    {
        Storage::fake('public');

        $user = $this->studentWithoutClearances();
        $this->uploadClearance($user, 'nbi');
        $this->uploadClearance($user->fresh(), 'barangay', ['consent' => null]);

        $this->assertSame([], Storage::disk('public')->allFiles());

        // And the code refuses to be pointed at it.
        config(['clearances.disk' => 'public']);
        $this->expectException(RuntimeException::class);
        ClearanceStore::diskName();
    }

    public function test_a_storage_failure_shows_a_plain_message_and_saves_nothing(): void
    {
        $user = $this->studentWithoutClearances();

        $this->mock(ClearanceStore::class)
            ->shouldReceive('save')
            ->once()
            ->andThrow(new RuntimeException('bucket unreachable'));

        $this->uploadClearance($user, 'nbi')
            ->assertSessionHasErrors(['file' => 'We could not save the file. Please try again in a moment.']);

        $this->assertSame(0, ProfileClearance::count());
        $this->assertNull($user->profile()->first()->clearance_consent_at, 'consent is recorded only after a successful upload');
    }

    // ---- the pages ----------------------------------------------------------

    public function test_the_clearances_page_shows_both_documents_and_their_status(): void
    {
        $user = $this->studentWithoutClearances();

        $this->actingAs($user)->get(route('clearances.edit'))
            ->assertOk()
            ->assertSee('NBI clearance')
            ->assertSee('Barangay clearance')
            ->assertSee('Not uploaded')
            ->assertSee('PDF only, up to 4 MB');

        $this->uploadClearance($user, 'nbi', ['valid_until' => now()->addMonths(5)->toDateString()]);

        $this->actingAs($user->fresh())->get(route('clearances.edit'))
            ->assertOk()
            ->assertSee('Valid until '.now()->addMonths(5)->format('M j, Y'))
            ->assertSee('View PDF');
    }

    public function test_an_expired_clearance_is_labelled_on_the_page(): void
    {
        $user = $this->studentWithoutClearances();
        ProfileClearance::factory()->type('nbi')->expired()->create(['profile_id' => $user->profile()->first()->id]);

        $this->actingAs($user)->get(route('clearances.edit'))
            ->assertOk()
            ->assertSee('Expired')
            ->assertSee('cannot apply for jobs until you upload a new one');
    }

    public function test_my_cv_links_to_the_clearances(): void
    {
        // Needs the small edit to resources/views/cv/edit.blade.php from the README.
        $user = $this->studentWithoutClearances();

        $this->actingAs($user)->get(route('cv.edit'))
            ->assertOk()
            ->assertSee('Manage clearances')
            ->assertSee(route('clearances.edit'));
    }

    public function test_the_audit_log_knows_the_new_actions(): void
    {
        foreach ([
            'clearance_uploaded' => 'Clearance Uploaded',
            'clearance_replaced' => 'Clearance Replaced',
            'clearance_removed' => 'Clearance Removed',
            'clearance_opened' => 'Clearance Opened by Company',
        ] as $action => $label) {
            $this->assertSame($label, (new FieldChangeLog(['action' => $action]))->actionLabel());
            $this->assertFalse((new FieldChangeLog(['action' => $action]))->concernsJobPosting());
        }
    }

    public function test_the_privacy_policy_and_terms_explain_the_clearances(): void
    {
        $this->get(route('privacy'))
            ->assertOk()
            ->assertSee('NBI clearance')
            ->assertSee('barangay clearance')
            ->assertSee('while your application to that company is active');

        $terms = view('partials.terms-content')->render();

        $this->assertStringContainsString('Required Clearances (NBI and Barangay)', $terms);
        $this->assertStringContainsString('every company you apply to can open them', $terms);
        $this->assertStringContainsString('cannot open them', $terms);
    }
}
