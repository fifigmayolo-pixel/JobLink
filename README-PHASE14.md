# Phase 14: Required NBI and barangay clearances

Students and alumni upload an **NBI clearance** and a **barangay clearance** (PDF, up to 4 MB, with a validity date) on a new **My Clearances** page. Applying is blocked until both are uploaded and neither has expired.

The files are kept in your **private** Supabase bucket and are opened only through a controller that checks a policy: no public links, no temporary links.

| Who | Can open a clearance file? |
|---|---|
| The owner | Yes, any time |
| A company the owner applied to | Yes, while the application is not withdrawn. Access ends when they withdraw or the account is removed |
| The Job Coordinator | No |
| Any other company, any other student | No |
| A company that only found them in Find Candidates | No |

Anyone who is refused gets a plain 404.

## 0. Before you start

```powershell
cd C:\xamppp\htdocs\JobLink
git checkout -b phase-14-clearances
Copy-Item database\database.sqlite database\database.backup14.sqlite
```

Take a Supabase backup too (Dashboard > Database > Backups). The two migrations only add a table and one empty column, so nothing existing changes, but a backup is the rule.

## 1. Install the files

```powershell
Expand-Archive "$env:USERPROFILE\Downloads\phase14-files.zip" -DestinationPath $env:TEMP\phase14-extract -Force
cd C:\xamppp\htdocs\JobLink
Copy-Item $env:TEMP\phase14-extract\phase14\* -Destination . -Recurse -Force
php artisan config:clear
php artisan migrate
```

Change the zip name if Windows saved it as `phase14-files (1).zip`. `php artisan migrate` is for your local database only (the tests build their own). Run it on Supabase only when you deploy.

### What is in the zip

**New**

| File | What it is |
|---|---|
| `config/clearances.php` | The rules in one place: 4 MB, 2-year limit on the date, the two document types |
| `database/migrations/2026_11_19_000001_create_profile_clearances_table.php` | The `profile_clearances` table |
| `database/migrations/2026_11_19_000002_add_clearance_consent_to_student_alumni_profiles_table.php` | When the person agreed that companies can open their clearances |
| `app/Models/ProfileClearance.php` | The model: expired or valid, label, size |
| `app/Policies/ProfileClearancePolicy.php` | Who can open a file (the table above) |
| `app/Support/ClearanceStore.php` | Saves, replaces and removes files: random names, old file deleted, public disk refused |
| `app/Http/Requests/StoreClearanceRequest.php` | The server checks: PDF extension, PDF content type, `%PDF-` signature, 4 MB, date rules, consent |
| `app/Http/Controllers/ClearanceController.php` | My Clearances: page, upload or replace, remove |
| `app/Http/Controllers/ClearanceFileController.php` | Streams the PDF after the policy says yes |
| `database/factories/ProfileClearanceFactory.php` | Test data |
| `resources/views/clearances/edit.blade.php` | The My Clearances page |
| `resources/views/cv/_clearances-summary.blade.php` | The summary card for My CV (see section 3) |
| `resources/views/partials/clearance-badge.blade.php` | The Valid until / Expired / Not uploaded label |
| `resources/views/partials/applicant-clearances.blade.php` | The company's clearance card (see section 3) |
| `resources/views/partials/applicant-clearances-print.blade.php` | The dates on the printable CV (see section 3) |
| `api/php.ini` | Lets PHP accept a file a little over 4 MB on Vercel (see section 4) |
| `tests/Concerns/MakesClearances.php` | Helpers for the three test files |
| `tests/Feature/ClearanceUploadTest.php` | 21 tests: file rules, dates, consent, replace, remove, storage |
| `tests/Feature/ClearancePolicyTest.php` | 22 tests: who can and cannot open a file, and what the company sees |
| `tests/Feature/ClearanceApplyBlockTest.php` | 15 tests: missing, expired, boundary dates, unblocking |

**Replaced** (they overwrite your copy; if you edited any of them since I last saw them, merge by hand)

| File | What changed |
|---|---|
| `routes/web.php` | 4 routes: `clearances.edit`, `.store`, `.destroy` (students and alumni) and `clearances.show` (the policy decides) |
| `app/Models/StudentAlumniProfile.php` | Clearance rules: `clearanceStates()`, `clearanceApplyMessage()`, `clearancesReady()`, `canApply()`. The CV strength percent and `cvReady()` are unchanged |
| `app/Http/Controllers/ApplicationController.php` | Refuses to apply without both valid clearances, with a clear message |
| `app/Models/FieldChangeLog.php` | Four audit labels |
| `resources/views/jobs/show.blade.php` | "Upload your clearances to apply" box, and the confirmation says the company can open them |
| `resources/views/partials/cv-completeness.blade.php` | A "Required documents" list; "Ready to apply" now needs the clearances too |
| `resources/views/legal/privacy.blade.php` | Clearances: what is collected, why, who sees them, how long |
| `resources/views/partials/terms-content.blade.php` | New section 5 "Required Clearances"; later sections renumbered 6 to 12 |
| `database/factories/UserFactory.php` | `cvReady()` now also creates two valid clearances, so every existing apply test keeps working. New `cvReadyWithoutClearances()` |
| `database/seeders/DemoDataSeeder.php` | The demo student gets both clearances (sample PDFs) |

## 2. Test changes you make by hand

`tests/Feature/RevisionJourneyTest.php`: Maria now has to upload her clearances before she can apply. `$profile`, `$maria`, `$company`, `$coordinator`, `$other` and `UploadedFile` already exist in that test.

**(a)** In step 9, just above `$this->post(route('applications.store', $job))->assertSessionHasNoErrors();`, add:

```php
        // She cannot apply yet: the NBI and barangay clearances are required.
        $this->post(route('applications.store', $job))->assertSessionHasErrors('application');
        $this->assertDatabaseMissing('job_applications', ['applicant_id' => $maria->id]);

        // She uploads both, one file per request.
        $this->get(route('clearances.edit'))->assertOk()->assertSee('Who can open your clearances');

        foreach (['nbi', 'barangay'] as $type) {
            $this->post(route('clearances.store', $type), [
                'doc' => $type,
                'file' => UploadedFile::fake()->createWithContent($type.'.pdf', "%PDF-1.4\n%journey\n%%EOF\n"),
                'valid_until' => now()->addMonths(6)->toDateString(),
                'consent' => '1',
            ])->assertSessionHasNoErrors();
        }

        $nbi = \App\Models\ProfileClearance::where('profile_id', $profile->id)->where('type', 'nbi')->firstOrFail();
```

**(b)** In step 11, right after
`$this->get(route('company.applications.cv', $application))->assertOk()->assertSee('Motivated graduate');`
add:

```php
        // The company can open the clearances she uploaded.
        $this->get(route('company.applications.show', $application))
            ->assertSee('NBI clearance')
            ->assertSee(route('clearances.show', $nbi), false);
        $this->get(route('clearances.show', $nbi))->assertOk()->assertHeader('Content-Type', 'application/pdf');
```

**(c)** Right after the `foreach (...) { ... $other ... }` loop that checks another company (just before `// ---- 12.`), add:

```php
        $this->actingAs($other)->get(route('clearances.show', $nbi))->assertNotFound();
```

**(d)** In step 12, after the line `$this->get(route('company.candidates.show', $profile))->assertForbidden();`, add:

```php
        $this->get(route('clearances.show', $nbi))->assertNotFound();   // the Coordinator can never open a clearance
```

**`FullJourneyTest`**: no edit expected, because its student comes from `User::factory()->cvReady()`. If it fails with "Upload your NBI clearance...", paste it here and I will fix it.

## 3. View edits by hand (five small ones)

I did not replace these files, so your edits are safe.

**3a. My CV** (`resources/views/cv/edit.blade.php`): right after the "Locked school record" card (the closing `</div>` on line 77) and before `<form method="POST" action="{{ route('cv.update') }}" id="cvForm" novalidate>`, add:

```blade
                @include('cv._clearances-summary', ['profile' => $profile])
```

**3b. Company application page** (`resources/views/company/applications/show.blade.php`): after the `@else ... @endif` block that shows the CV (line 52) and before the `{{-- Reply --}}` comment, add:

```blade
            {{-- The clearances the applicant uploaded --}}
            @include('partials.applicant-clearances', ['application' => $application])
```

**3c. Printable CV** (`resources/views/company/applications/cv-print.blade.php`): inside `<main class="cv-sheet">`, after `@include('cv._document', ['cv' => $cv])`, add:

```blade
        @include('partials.applicant-clearances-print', ['application' => $application])
```

**3d. Terms checkbox** (`resources/views/profile/complete.blade.php`): replace

```blade
                <a href="{{ route('privacy') }}" class="fw-semibold" target="_blank" rel="noopener">Privacy Policy</a>.
```

with

```blade
                <a href="{{ route('privacy') }}" class="fw-semibold" target="_blank" rel="noopener">Privacy Policy</a>,
                including that the NBI and barangay clearances I upload to apply for jobs are collected and shared with the companies I apply to.
```

and replace the paragraph below it with

```blade
             <p class="small text-secondary mb-3">
                Agreeing to the Terms &amp; Conditions does not make your profile searchable. After you finish, you can choose in Profile settings whether partner companies may find your CV. It stays off unless you turn it on. Your clearances are never shown in search: a company can open them only after you apply to one of its jobs.
            </p>
```

**3e. Optional, candidate page** (`resources/views/company/candidates/show.blade.php`): in the gold note, after "...when they apply to one of your jobs.", add: "Their NBI and barangay clearances are also shared only when they apply."

## 4. Supabase and Vercel

**No new bucket and no new keys.** Clearances go in the `clearances/` folder of the same private bucket your roster spreadsheets already use (`FILESYSTEM_DISK=s3`, `AWS_BUCKET`). Locally they go to `storage/app/private`.

1. **Check the bucket is private:** Supabase > Storage > your `AWS_BUCKET` bucket > it must **not** be marked Public. (`AWS_PUBLIC_BUCKET`, the one for logos, is the public one and must never be used for clearances. The code refuses to run if the clearance disk is `public`.)
2. **Upload size on Vercel.** Vercel rejects any request over about 4.5 MB, so the page sends **one file per request**, and the app limit is 4 MB. PHP's own default upload limit can be smaller still, so I added `api/php.ini` (it sets `upload_max_filesize = 5M` and `post_max_size = 6M`; the Vercel PHP runtime reads that file). I could not confirm from the runtime's documentation that those two settings are honoured, so **test it after deploying**: upload a 3 to 4 MB PDF.
   - It saves: the setting works.
   - You get "The file could not be uploaded...": the setting is not applied. Tell me and we will look at another way.
3. Deploy, then run the migrations on Supabase the same way you did for the earlier phases.

Optional: `CLEARANCE_DISK=` in the environment points clearances at a different private disk. Leave it empty.

## 5. Things to know

- **Existing students and alumni cannot apply until they upload both clearances.** Applications they already sent are not affected. Use **Announce** to tell them.
- **Company view is live, not a snapshot.** A company sees the clearance on file now, so a renewed clearance replaces the old one for everyone. Withdrawing an application ends the company's access. A removed account ends it too.
- **Dates:** valid through the date entered, Expired from the next day. A past date is refused when uploading, and a date more than 2 years ahead is refused as a typo (change it in `config/clearances.php`). To fix a wrong date, upload the file again.
- **Consent:** the first upload asks the person to tick that companies they apply to can open the file. It is saved with the date and not asked again.
- **Audit log** gets four actions: Clearance Uploaded, Replaced, Removed, and Opened by Company (at most once a day per company and document). They never contain the file.
- **Remove button:** my addition, so a person can delete a file themselves. It deletes the file from storage.
- **Findable by companies** does not depend on clearances.
- **Hard-deleted accounts:** if a profile row is deleted directly in the database, its files stay in the bucket. Normal removal in JobLink only hides the account, so the files stay in the bucket too, but no company can open them.
- **Not a lawyer:** the privacy policy and terms wording is a draft of what the code does. Please have your school's data privacy officer read it.
- **Linky:** add this under "My CV (students and alumni)" in `resources/linky/knowledge.md`:
  `- To apply, you also need two clearances: your NBI clearance and your barangay clearance. Open My Clearances (from My CV), upload each as a PDF of 4 MB or less, and enter the date it is valid until. An expired clearance must be replaced before you can apply. Only you and the companies you apply to can open the files. The Job Coordinator and other companies cannot.`
  And in "Completing the profile", change the last bullet to end with: `...then build your CV under My CV and upload your clearances under My Clearances.`

## 6. Run the tests

```powershell
php artisan test --filter="ClearanceUploadTest|ClearancePolicyTest|ClearanceApplyBlockTest"
php artisan test
```

The three new files hold 58 tests. Four of them check the view edits in section 3 (`test_my_cv_links_to_the_clearances`, `test_the_company_application_page_lists_the_clearances_with_links`, `test_the_printable_cv_lists_the_dates_but_no_file_links`, and the My CV card test), so if only those fail, a snippet is missing or in the wrong place.

## 7. Quick manual check (the full journey)

1. Company posts a job with 2 slots. The Coordinator approves it.
2. A student with a ready CV opens the job. There is **no Apply button**: it says to upload clearances. Try an upload with a `.jpg`, a file over 4 MB and a past date: each is refused with a plain message.
3. Upload both clearances (PDF, future date). The job page now shows **Apply now**. Apply.
4. Log in as the company, open the applicant, and open both PDFs. Invite to interview. Log in as the Coordinator: the Audit Log shows the uploads and the company's opens, but the Coordinator cannot open the files (a direct link gives 404).
5. Fill the second slot with another applicant: the job closes as Full (Phase 13).
6. As the student, replace one clearance, then open the old file's address if you have it: it is gone.

Do not start Phase 15 until the suite is green.
