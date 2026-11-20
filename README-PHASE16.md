# Phase 16: jobs related to your course first

Branch: `phase-16-related-jobs`. Database backup first, please. No migration, no new route.

## 1. Install

```powershell
Expand-Archive "$env:USERPROFILE\Downloads\phase16-files.zip" -DestinationPath "$env:TEMP\phase16-extract" -Force
Copy-Item "$env:TEMP\phase16-extract\phase16\*" -Destination . -Recurse -Force
php artisan config:clear
php artisan test --filter=JobRelevanceTest
php artisan test
```

| File | What it is |
|---|---|
| `config/course_keywords.php` | New. The one place to edit: courses, their aliases and their related words. |
| `app/Support/JobRelevance.php` | New. Builds the keywords, scores the jobs, gives the order. |
| `app/Http/Controllers/JobPostingController.php` | Replaced (identical to your Phase 13 copy except `index()`). |
| `resources/views/jobs/index.blade.php` | Replaced. One note line under the count, and `relatedJobIds` passed to the card. |
| `resources/views/partials/job-card.blade.php` | Replaced. One small "Related to your course" pill and its style. The card design is otherwise unchanged. |
| `tests/Feature/JobRelevanceTest.php` | New. 20 tests. |

## 2. How it works (plain keyword match, not AI)

- **Keywords** = the related words the config lists for the student's course + the words of the course itself + CV skills + other education (the same words the dashboard suggestions use).
- **Score**: a keyword found in the job title = 3, requirements or qualifications = 2, description = 1, counted once per keyword. A job needs **2** to be "related" (`min_score`), so one passing mention in a description is not enough.
- **Order**: related jobs first (best score, then newest), then every other job newest first. Nothing is hidden.
- **Pagination stays on the server.** The order is part of the database query (`ORDER BY CASE job_postings.id WHEN .. THEN ..`), then a normal `paginate(15)`. Page 2 continues where page 1 stopped, totals are right, and search and filters work as before.
- **Speed.** One extra query reads only the text columns of the newest 300 jobs that pass the current search and filters (`pool`). Jobs beyond the pool are not hidden, they just follow in normal order. With no course words and no skills, nothing is scored and no extra query runs.
- **Who.** Students and alumni with a profile. Coordinators, companies and guests are unchanged.
- **The word "IT".** A short ALL-CAPITAL keyword (IT, ICT, HR) only matches when it is written in capitals in the job, so "IT Support" matches and "it is" does not.
- **Label and note.** Matched cards get "Related to your course". A line under the job count says "Jobs related to your course and CV skills are listed first." Both appear only when at least one job is related.

## 3. Editing the mapping

Open `config/course_keywords.php`. Each block has `aliases` (how the course can be written on a profile, matched as whole words in any letter case) and `keywords` (one word each). I filled in six groups (IT, hospitality, business, accountancy, education, criminology) from common Philippine program names. Your database only has BSIT and BSHM so far, so trim or add to match what PSU Alaminos City really offers. A course that is not listed still works: its own words (for example "Psychology") are used on their own.

After editing on the live site, run `php artisan config:clear` locally; on Vercel the next deploy picks it up.

## 4. Two small things I changed

1. The list now orders by `date_posted` **and then `id`** (both newest first), so jobs posted on the same day never swap places between pages. If an older test relied on the unspecified order of same-day jobs, tell me which one.
2. The card gets the new pill through `$relatedJobIds`, the same way it gets `$savedJobIds`. Pages that include the card without it (saved jobs, landing page) show no pill.

## 5. Optional: Linky

In `resources/linky/knowledge.md`, near the line about browsing jobs, add:

```
- On Open Positions, jobs related to your course and CV skills are listed first and marked "Related to your course". All other open jobs still follow, newest first. This is a simple word match between your course, your CV skills and the job text. Add your skills to My CV to improve it.
```

## 6. Checks

- `php artisan test --filter=JobRelevanceTest`, then the whole suite.
- The full journey (company posts a job with slots, Coordinator approves, a student with both clearances applies, the company invites to interview, the job closes when the slots are full) is `RevisionJourneyTest` and `FullJourneyTest`.
- Manual: log in as the demo BSIT student (`demo.student@example.com`), open Open Positions, and check that IT jobs come first with the pill and the note. Log in as a coordinator and check the page looks as before. Try the search box and job type filter, and page 2.
- On the live site (PostgreSQL), open Open Positions once as a student. The ordering uses a plain `CASE` with whole numbers, which both SQLite and PostgreSQL accept.
