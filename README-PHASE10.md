# Phase 10: student and alumni dashboard

## Files

New
- `app/Support/JobSuggestions.php`: the keyword matcher
- `resources/views/dashboard/student.blade.php`: the dashboard page
- `tests/Unit/JobSuggestionsTest.php` (8 tests)
- `tests/Feature/StudentDashboardTest.php` (21 tests)

Replaced
- `app/Http/Controllers/DashboardController.php`: students and alumni get the page; company and Coordinator redirects are unchanged
- `resources/views/layouts/app.blade.php`: your file as you sent it, plus one block (below)

If you changed the layout after sending it, don't overwrite it. Add this block as the first thing inside `<nav class="jl-sidebar-nav">`, above the Jobs link:

```blade
@role('student|alumni')
    <a class="jl-sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>
@endrole
```

## What the matcher does

- Keywords come from: CV skills, the course (`course_program`), and the degree program of other education.
- Filler and degree wording are ignored ("Bachelor of Science in ..."), and so are years and one-letter words.
- Whole words only, any case, a plural "s" is ignored ("sales" = "sale"). "java" does not match "javascript".
- A keyword counts once per job, at its strongest place: title 3, requirements or qualifications 2, description 1.
- A job needs a score of 2 or more, so one passing mention in a description is not enough.
- Only approved, open jobs from active companies, and only the 300 newest are scored.
- Jobs already applied to are left out. A withdrawn application does not count.
- Top 5. Highest score first, newest first on a tie.

## Where it differs from the plan I sent

- Qualifications are matched together with requirements (same weight).
- From other education, only the degree program is used, not the free-text details.
- The minimum score of 2 is new.

## Tuning in one place

`JobSuggestions` constants: `LIMIT`, `POOL`, `MIN_SCORE`, `WEIGHT_*`, and the `STOP_WORDS` list.

## After extracting

```powershell
php artisan test
```

Any old test that expects a student's `/dashboard` to redirect to the jobs page will now fail. Change it to expect a 200 and the Dashboard page.
