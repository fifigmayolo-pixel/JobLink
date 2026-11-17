# Phase 9: Company sees only its own posted jobs

Extract OVER your project (same paths). No migration needed. routes/web.php is unchanged.

## New files
- resources/views/jobs/mine.blade.php          the "Your Posted Jobs" page (company accounts only)
- tests/Feature/CompanyOwnJobsTest.php         11 tests

## Changed files (full files included)
- app/Policies/JobPostingPolicy.php
    view():   Coordinator = all. Company = only its own (tagged to its company, or posted by its login), any status,
              so another company's APPROVED job is no longer openable by URL. Students/alumni = approved only (unchanged).
    update()/delete(): Coordinator, or the company login that posted it (was: anyone whose id matched posted_by).
- app/Models/JobPosting.php                    + scopeOfCompany(), scopeInListingState(), listingState(), LISTING_STATES
    ofCompany(null) matches NOTHING (a plain where('company_id', null) would return every untagged job).
- app/Http/Controllers/JobPostingController.php
    index():  company accounts get companyIndex() (own jobs, all statuses, search + status filter, applicant counts);
              everyone else keeps the public listing exactly as before.
    edit/update/destroy: another company's job -> 404 (same as show); your own tagged-by-Coordinator job -> 403.
    update(): company_id / posted_by in the request are ignored.
- resources/views/jobs/show.blade.php          back link says "Back to your jobs" for company accounts

## Behaviour you may want to change
- A job the Coordinator posted and tagged to a company shows in that company's list and can be OPENED by it,
  but only the Coordinator can edit/close/delete it (this is how update() already worked).
- Company list shows pending, not approved, open and closed postings; a closed one with "deadline passed" is an open
  posting whose deadline has gone by.

## Optional: sidebar label (resources/views/layouts/app.blade.php)
Find   <i class="bi bi-briefcase"></i> Jobs
Change to   <i class="bi bi-briefcase"></i> {{ auth()->user()->role === 'company' ? 'Your Posted Jobs' : 'Jobs' }}

## Run
php artisan test                      (whole suite: the policy change affects every test that opens a job as a company)
php artisan test --filter=CompanyOwnJobsTest
