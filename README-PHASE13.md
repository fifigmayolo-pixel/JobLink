# Phase 13: Job vacancy slots

A job now has a **number of slots**. Each applicant moved to **Interview** fills one slot ("3 of 5 interview slots filled"). When the last slot is filled the job closes by itself and shows **Full**. It reopens when an Interview applicant is moved to another status or the number of slots is raised.

There is no Hired status. JobLink does not track hiring, so these are *interview* slots, and the pages say so.

## 0. Before you start

```powershell
cd C:\xamppp\htdocs\JobLink
git checkout -b phase-13-job-slots
```

Back up the database first (the migration changes existing rows, see section 5):
- Local: copy `database\database.sqlite` to `database\database.backup.sqlite`.
- Supabase: Dashboard > Database > Backups (or `pg_dump`).

## 1. Install the files

```powershell
Expand-Archive "$env:USERPROFILE\Downloads\phase13-files.zip" -DestinationPath $env:TEMP\phase13-extract -Force
cd C:\xamppp\htdocs\JobLink
Copy-Item $env:TEMP\phase13-extract\phase13\* -Destination . -Recurse -Force
php artisan migrate
```

If your zip is somewhere else, change the first path. `php artisan migrate` updates your local database; the tests build their own.

### Files in this phase

| File | What changed |
|---|---|
| `database/migrations/2026_11_18_000001_add_slots_to_job_postings_table.php` | **new**: `slots` (default 1) and `closed_by_slots` columns |
| `app/Models/JobPosting.php` | slot counting, labels, and the one rule that closes or reopens (`syncSlots`) |
| `app/Models/FieldChangeLog.php` | two new audit labels |
| `app/Http/Requests/StoreJobPostingRequest.php` | `slots` required, whole number 1 to 1000 |
| `app/Http/Requests/UpdateJobPostingRequest.php` | `slots` editable; cannot go below the people already invited; a full job cannot be set to Open |
| `app/Http/Controllers/JobPostingController.php` | saves slots, audits the change, runs the close/reopen rule |
| `app/Http/Controllers/Company/ApplicationController.php` | an invite fills a slot; inviting past the last slot is refused; rejecting frees one |
| `app/Http/Controllers/ApplicationController.php` | a full job refuses new applications |
| `app/Policies/JobApplicationPolicy.php` | `create` also requires the job not to be full |
| `app/Http/Controllers/DashboardController.php` | company dashboard loads the interview counts |
| `resources/views/partials/job-card.blade.php` | slots line and Full/Closed pill on the card |
| `resources/views/jobs/show.blade.php` | slots pill, Full/Closed banner, "an invitation is not a job offer" note |
| `resources/views/jobs/mine.blade.php` | slots shown in Your Posted Jobs, "Full" badge |
| `resources/views/dashboard/company.blade.php` | slots shown in latest postings, "Full" badge |
| `database/seeders/DemoDataSeeder.php` | demo jobs get slots (3, 5, 2, 1) |
| `tests/Feature/JobSlotsTest.php` | **new**: 21 tests for this phase |

The approval flow, notifications, emails and the existing audit entries are untouched.

## 2. Two form files to edit by hand

I have not seen `jobs/create.blade.php` and `jobs/edit.blade.php`, so I did not overwrite them. Add this next to the Job type or Application deadline field (match the surrounding column classes).

**`resources/views/jobs/create.blade.php`**

```blade
<div class="col-md-6">
    <label for="slots" class="form-label">Number of slots needed <span class="text-danger">*</span></label>
    <input type="number" name="slots" id="slots"
           class="form-control @error('slots') is-invalid @enderror"
           value="{{ old('slots', 1) }}" min="1" max="1000" step="1" required>
    <div class="form-text">How many applicants you can invite to interview. The job closes by itself when every slot is filled.</div>
    @error('slots') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
```

**`resources/views/jobs/edit.blade.php`**

```blade
<div class="col-md-6">
    <label for="slots" class="form-label">Number of slots needed <span class="text-danger">*</span></label>
    <input type="number" name="slots" id="slots"
           class="form-control @error('slots') is-invalid @enderror"
           value="{{ old('slots', $jobPosting->slots) }}" min="1" max="1000" step="1" required>
    <div class="form-text">
        {{ $jobPosting->slotsLabel() }}. It cannot be lower than the number of applicants already invited to interview.
        Raising it reopens a job that closed because it was full.
    </div>
    @error('slots') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
```

## 3. Existing tests that post a new job

The New Job form now requires `slots`, so these tests need one line. Add `'slots' => 3,` right after the `'job_type'` line in each place:

```php
'job_type' => 'full_time',
'slots' => 3,
```

- `tests/Feature/FullJourneyTest.php` (around line 35)
- `tests/Feature/JobPostingTaggingTest.php` (around lines 25, 41 and 51)
- `tests/Feature/RevisionJourneyTest.php` (around line 250)

`CompanyOwnJobsTest` needs no change: on the edit form `slots` is optional, so an update without it keeps the current number.

## 4. Optional: Saved Jobs

Saved Jobs shows job cards too. It works as is, but runs one small count query per card. To avoid that, find the query in the saved-jobs controller that loads the jobs and add `->withInterviewCount()` to it, for example:

```php
$jobs = JobPosting::query()->whereIn('id', $ids)->with('company')->withInterviewCount()->get();
```

I have not seen that controller, so I left it alone.

## 5. The migration closes jobs that are already full

Existing postings get 1 slot. A posting that **already has applicants in Interview** would otherwise read "3 of 1 slots filled", so for those the migration sets `slots` to the number of Interview applicants and, if the posting is open, closes it as Full. No audit rows are written for this. The company raises the number on the edit page to reopen it. Check this before running the migration on Supabase.

## 6. Linky (`resources/linky/knowledge.md`)

Replace line 68 (this also fixes a mistake of mine: the post form offers only Full-time and Part-time; Internship is only a filter on the Jobs list):

```
- The form asks for: Job title, Job type (Full-time or Part-time), Number of slots needed (a whole number, 1 or more), Location, Application deadline, Description, Qualifications and Requirements. Every field is required.
```

Add under **Jobs (students and alumni)**:

```
- Each job shows how many interview slots are filled, for example "3 of 5 interview slots filled". An interview invitation counts as one filled slot. It is not a job offer. When every slot is filled the job closes, shows "Full" and cannot be applied to. It reopens if the company frees a slot.
```

Add under **Companies (employers)**, after the Applicants line:

```
- Number of slots: set it when posting a job and change it later under Edit. Each applicant invited to interview fills one slot. You cannot invite more applicants than there are slots: raise the number, or move someone out of Interview. The job closes by itself when all slots are filled and reopens when one is freed.
```

## 7. Run the tests

```powershell
php artisan test
```

All earlier tests plus the 21 in `JobSlotsTest` should pass. These cover: slots validation, the default of 1, closing at the last slot (with the audit entry and pending applicants untouched), a full job refusing applications, inviting past the slots being blocked, reopening (status change and raised slots), a hand-closed job never auto-reopening, slots not going below the invited count, the notification and email still going out, and the labels on every page.

## 8. Quick manual check

1. Company posts a job with 2 slots. It shows "Pending approval", "0 of 2 interview slots filled".
2. Coordinator approves it. A student with both CV requirements applies.
3. The company invites the applicant to interview. The page shows "1 of 2". Invite a second applicant: the job shows **Full** and drops off the students' list.
4. A third student opens the job link: banner says it is Full and there is no Apply button.
5. Reject one invited applicant: the job is open again and the students' list shows it.
6. Check the Coordinator's Audit Log for "Job Posting Closed (Slots Full)" and "Job Posting Reopened (Slot Freed)".

Do not start Phase 14 until the suite is green.
