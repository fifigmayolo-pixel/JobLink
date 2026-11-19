# Phase 15: Coordinator dashboard, rejected count removed

Branch: `phase-15-coordinator-dashboard` (already created). Database backup first, please.

## 1. Install

```powershell
Expand-Archive "$env:USERPROFILE\Downloads\phase15-files.zip" -DestinationPath "$env:TEMP\phase15-extract" -Force
Copy-Item "$env:TEMP\phase15-extract\phase15\*" -Destination . -Recurse -Force
php artisan test --filter=CoordinatorDashboardTest
php artisan test
```

No migration, no new route, no config. Three files:

| File | What changed |
|---|---|
| `app/Http/Controllers/Coordinator/DashboardController.php` | Replaced. The overview query now reads only Pending and Interview; `$rejectedCount` and the `rejectedCount` view value are gone; `totalForRange` = Pending + Interview. |
| `resources/views/coordinator/dashboard.blade.php` | Replaced. The Rejected legend row, `$rejectedPct`, `$deg2` and the rose slice of the donut are gone. The centre label reads "Active Applications". |
| `tests/Feature/CoordinatorDashboardTest.php` | New. 8 tests. |

If you edited either replaced file by hand since you pasted it to me, compare before copying (`git diff` after copying shows exactly what moved).

## 2. What I removed

- The query: `$statusCounts->get('rejected')`, plus the rejected rows are no longer fetched at all (`whereIn('status', ['pending', 'interview'])`).
- The count: `$rejectedCount`, the `rejectedCount` view value, and `$rejectedPct`.
- The label: the "Rejected" row (dot, name, count, percent) in the Application Overview legend.
- The chart part: the rose slice of the donut. It now has two slices, Pending and Interview.
- Rejected no longer feeds `totalForRange` (the number in the middle of the donut).

## 3. What I left untouched

- `rejected` as an application status: companies can still reject, applicants are still notified in JobLink and by email, and the student dashboard and My Applications still show Rejected.
- "Applications Submitted" (the top card) still counts every application ever sent, rejected ones included.
- Job posting rejection and registration-request rejection (Job Approvals, Registration Requests, their emails and audit entries). Those are different things.
- The company dashboard's "Not approved" job label.
- The other stat cards, Recent Job Postings, the date-range filter, the Tracer Study card and Quick Actions.

## 4. Two things I decided (tell me if you want them the other way)

1. **The donut's centre label.** It used to say "Total Applications", but the number only ever counted Pending + Interview + Rejected (never Withdrawn). With Rejected gone it would have been wrong, so it now says **Active Applications**: applications still in play.
2. **Percentages.** The Rejected slice used to take whatever was left of 100%. Now Interview does, so the two always add to exactly 100% (no "13% + 88%").

## 5. Layout

There was no separate rejected card: it was one legend row inside the Application Overview card, so nothing is left as a gap in the stat-card row. The card is one row shorter, the legend is still centred beside the donut, and the Recent Job Postings card on the left stretches to match (`h-100`). Please open the dashboard once and look at it at desktop and phone width.

## 6. Checks

- `php artisan test --filter=CoordinatorDashboardTest`, then the whole suite.
- The full journey (company posts a job with slots, Coordinator approves, student with both clearances applies, company invites to interview, job closes when the slots are full) is `RevisionJourneyTest` and `FullJourneyTest`. Both should stay green; neither reads the rejected count.
- Manual: Coordinator dashboard, change the range dropdown through all five options. Reject one application as a company and confirm the student still gets the notice.
