# Phase 8 / 8b follow-up: what is in this folder

Built on top of joblink-phase8a-candidate-search.zip. Extract this OVER your project (same paths).
No migration is needed. routes/web.php, UserFactory, FieldChangeLog, CandidateController,
CandidateProfilePolicy and UpdateSearchabilityRequest from 8A are NOT in this folder and stay as they are.

## Does it conflict with 8A?
No. Every file here either is new, or contains the 8A version of that file plus my changes.
The one file that would have been replaced wholesale is tests/Feature/CandidateSearchTest.php.
It is included here as the 8A file with ONE assertion changed (see below), so all 22 of its tests are kept.

## New files
- app/Support/ContactMask.php                              hides emails, phone numbers, web addresses and @handles in CV text
- tests/Unit/ContactMaskTest.php                           11 tests
- tests/Feature/CandidateContactHidingTest.php             5 tests (contact hiding, search refusal, "still needed" message)

## 8A files changed
- tests/Feature/CandidateSearchTest.php   ONE change: the test "a company sees the CV but never the private details"
  used to assert the LinkedIn link IS shown to a company. Links are now hidden until the student applies, so it asserts the opposite.
- app/Support/CandidateCv.php                    drops links, masks free text
- app/Support/CandidateSearch.php                card excerpt goes through ContactMask
- app/Http/Requests/Company/CandidateSearchRequest.php   refuses searches that are an email / phone / web address
- app/Http/Controllers/ProfileSearchabilityController.php  readable "still needed" message
- app/Models/StudentAlumniProfile.php            + searchabilityBlockers()
- resources/views/company/candidates/show.blade.php, index.blade.php
- resources/views/profile/searchable-preview.blade.php, edit.blade.php
- resources/views/legal/privacy.blade.php        removed "or you agree to share", date -> Nov 16, 2026
- resources/views/partials/terms-content.blade.php

## Other files changed (not in 8A)
- resources/views/applications/show.blade.php    withdraw wording
- resources/views/coordinator/audit-log/index.blade.php  intro line

## Still yours to do
1. Add a hint under the summary field in resources/views/cv/edit.blade.php, e.g.:
   <div class="form-text">Companies you have not applied to can read this. Do not put your phone number, email or social links here.</div>
2. Fill the placeholders in legal/privacy.blade.php: [RETENTION PERIOD], [EMAIL OR OFFICE LOCATION], [NAME AND EMAIL].
3. Post an Announcement about the new "searchable" setting. No forced re-accept of the Terms.
4. Optional: a lower throttle on candidate profile pages only in routes/web.php:
   ->middleware('throttle:30,1,candidate-profile') on the company.candidates.show route.
5. Run:  php artisan test --filter=Candidate    and    php artisan test tests/Unit/ContactMaskTest.php
