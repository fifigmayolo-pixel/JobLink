# Phase 11: partner company dashboard

## Files

New
- `resources/views/dashboard/company.blade.php`
- `tests/Feature/CompanyDashboardTest.php` (12 tests)

Replaced
- `app/Http/Controllers/DashboardController.php`: companies get the page instead of a redirect to Applicants (students and alumni as in Phase 10, Coordinator redirect unchanged)
- `resources/views/layouts/app.blade.php`: same as the Phase 10 version, with one line changed
- `tests/Feature/StudentDashboardTest.php`: two Phase 10 tests changed (they asserted that a company is redirected, and that a company has no Dashboard link, both of which stop being true)

If you changed the layout since Phase 10, don't overwrite it. Just change this one line in the sidebar:

```blade
@role('student|alumni')          {{-- the block that holds the Dashboard link --}}
```
to
```blade
@role('student|alumni|company')
```

## What the page shows

- Registered students and alumni: one number, active accounts counted exactly like the Coordinator dashboard (activated, not removed). No names, no list, no link.
- Your jobs: Pending, Open, Closed, Not approved. Same meaning as Your Posted Jobs, and each card opens it already filtered.
- Applicants on your jobs: people who applied and have not withdrawn, plus how many are waiting for review (pending).
- Your latest postings: the 5 newest, with status and applicant count.
- Recent applicants: the 5 newest non-withdrawn, each linking to the existing application page.
- Post a new job: button to `jobs.create`.

## Rules that matter

- The company id comes from the logged-in account only. Query-string tricks (`?company_id=`) change nothing; there's a test for it.
- Applicant numbers follow the Applicants page, which includes applicants of jobs the company later removed.
- A login with no company record sees empty numbers, never someone else's.

## After extracting

```powershell
php artisan test
```

Any old test that expects a company's `/dashboard` to redirect to the Applicants page will now fail. Change it to expect a 200 and the Dashboard page.
