# Phase 12 (part B): the full-journey test and the leftover check

This is the second half of Phase 12, the final check. There is no Phase 13.

## Files in this drop

New
- `tests/Feature/RevisionJourneyTest.php` (3 tests)

Replaced
- `resources/linky/knowledge.md`: Linky's knowledge base, based on the copy you sent today. The two `[FILL IN]` blocks (contact details, and whether Coordinator-posted jobs need approval) are unchanged. If you have filled them in since you sent it, copy your values back after extracting.

Nothing else changes: no app code, no migration, no view.

## What the journey test walks through

One test, in this order. It fails at the step that broke, and every step says what it is checking.

1. The Coordinator adds a student by Student ID and name, and uploads an alumni roster (the import job is queued with the right role and the file is stored).
2. Before activation the company dashboard counts 0 registered.
3. A wrong name does not activate the account. The right name (any case) does; the student sets a password and is signed in.
4. The CV page is closed until the profile is completed. She completes the profile (T&C ticked).
5. She builds the CV: 100% and ready.
6. Searchable is off by default: the company cannot find or open her.
7. She switches it on: the company finds her, reads the CV, sees no email, mobile, street or birth date, and the view is in the audit log. The Coordinator is refused on both candidate pages.
8. The company posts a job. It is pending, she cannot see it, be offered it or apply.
9. The Coordinator approves it. She sees it, her dashboard suggests it (skill PHP), she applies.
10. She edits her CV after applying. The company still reads the copy it received, and the live candidate page shows the new one.
11. Another company cannot open the application. The Coordinator is refused on the application, its CV and the candidate page, and the Coordinator's Records page for her shows no CV.
12. The company invites her to an interview. She gets the notification in JobLink and an email at her personal email, the application page says "invited to interview", and marking the notification read works.
13. Both dashboards agree: her counts, the company's job and applicant counts, and 1 registered.

The two small tests: only the Coordinator can add or import students, and a name that is not on the roster activates nothing.

The walk uses a student. Alumni do the same after the Tracer Study screen, and I have not seen that screen's code, so I did not guess at it. The alumni side of the roster is covered by step 1.

## After extracting

```powershell
$zip = "C:\path\to\phase12b-files.zip"
Expand-Archive $zip -DestinationPath $env:TEMP\phase12b-extract -Force
cd C:\xamppp\htdocs\JobLink
Copy-Item $env:TEMP\phase12b-extract\phase12b\* -Destination . -Recurse -Force
php artisan test
```

You should see 3 more tests than before.

## If a step fails

I could not run your app here, so these are the few places where the test leans on something I have not seen:

- **Quick add or activation says the Student ID is invalid:** the test uses `98-0003-03` (the same `##-####-##` shape as your demo and factory IDs). If your ID rule wants something else, change `STUDENT_ID` at the top of the test.
- **The roster upload is refused as "not an xlsx":** the test sends a fake file with the xlsx mime type. If your rule inspects the content, tell me and I will build a real one. That needs `app/Imports/StudentRosterImport.php`, which I have not seen. The test does not read the spreadsheet, only that the import is queued.
- **A page assertion fails on wording:** the strings come from your views as sent (for example "You are not searchable", "Contact details are hidden"). Send me the failing line.

## Leftover check: anything still on the old PDF flow?

- Your suite already has `ResumeRemovalTest`: it checks the column is gone, no resume code is left, and the privacy policy and terms no longer mention it. Those all passed in your last run.
- The routes file, the Linky service and the Linky knowledge base have no resume wording. The knowledge base now says plainly that there is no file upload and the CV is built from the profile.
- Linky was out of date, not wrong: it did not know about My CV, the searchable switch, the dashboards, Find Candidates, Your Posted Jobs or the Internship job type. That is what the new `knowledge.md` fixes.
- Not checked, because I have not seen it: `README.md` and the `public/` folder. If you want a last sweep, run this and send me the output if anything shows:

```powershell
cd C:\xamppp\htdocs\JobLink
Select-String -Path app,resources,routes,config,public,README.md -Pattern "resume|résumé" -Recurse | Select-Object Path,LineNumber,Line
```

Old migrations that mention it are fine: they are the history of removing it.

## One small thing I found, your choice

On the public Jobs page the search boxes print the URL value directly. A hand-made address like `/jobs?search[]=x` would give an error page instead of ignoring it. The controller already ignores it now, so only the page needs a change. In `resources/views/jobs/index.blade.php`, lines 29 and 35, replace:

```blade
value="{{ request('search') }}"
value="{{ request('location') }}"
```

with:

```blade
value="{{ is_string(request('search')) ? request('search') : '' }}"
value="{{ is_string(request('location')) ? request('location') : '' }}"
```

## Still yours from part A

- Fill `[RETENTION PERIOD]`, `[EMAIL OR OFFICE LOCATION]`, `[NAME AND EMAIL]` in the privacy policy, and add its three small edits.
- On Vercel: `SESSION_SECURE_COOKIE=true`, confirm `QUEUE_CONNECTION=sync`, and check Max Duration.
- On the live site: search `developer` in lower case on the Jobs page.
- Post an Announcement telling students and alumni about the new searchable setting.
