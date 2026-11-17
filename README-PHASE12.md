# Phase 12 (part A): demo data, search fix, environment list, Vercel and Supabase check

Part B (the full-journey test and the list of old PDF resume leftovers) needs a few more files from you. See the end.

## Files in this drop

Replaced
- `database/seeders/DemoDataSeeder.php`
- `tests/Feature/DemoDataSeederTest.php` (12 tests, was 7)
- `app/Http/Controllers/JobPostingController.php`: the Phase 9 version plus the search fix below. Only the search lines and two small helper methods differ.

New
- `tests/Feature/JobSearchTest.php` (5 tests)

Not in the zip: the privacy policy edits (3 small insertions, see "Privacy policy" below). I did not send the whole file, so I don't overwrite the placeholders you may have filled in.

## 1. Demo data for the four logins

| Login | What the demo shows now |
|---|---|
| Coordinator | One job waiting for approval (unchanged) |
| Company | Dashboard: 1 pending, 2 open, 1 closed posting (new closed job "Office Clerk"), 1 applicant, 1 notification. Finds the student under Find Candidates. |
| Student | Full CV (100%), searchable switch on, 1 pending application, dashboard suggests "Customer Service Associate" (matches on Communication), 1 welcome notification |
| Alumni | Basic profile only, switch off. Dashboard asks to finish the CV; not listed in Find Candidates. |

Run it again shortly before the demo, as before. It stays safe to run twice: nothing is duplicated and the student's consent date is kept.

## 2. Fix found while checking PostgreSQL: job search was case sensitive on the live site

`LIKE` ignores case on SQLite (your tests) but not on PostgreSQL (Supabase). So on the live site, searching "developer" would not find "Junior Web Developer". The public Jobs search, the location filter and the company's own search now compare in lower case. The same change makes a hand-made `?search[]=x` ignored instead of breaking the company page.

To check on the live site after deploying: search for `developer` in lower case on the Jobs page.

Everything new in Phases 8 to 11 was already written to work on both databases (lower-case compare in candidate search, counts cast to numbers, plain SQL).

## 3. Environment variables (from your `.env.example` and the config files you sent)

Set in Vercel for production

| Variable | Value |
|---|---|
| `APP_NAME`, `APP_URL` | `JobLink`, your Vercel address |
| `APP_ENV` | `production` (this also stops the demo seeder running on the normal seed) |
| `APP_DEBUG` | `false` |
| `APP_KEY` | from `php artisan key:generate --show` |
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Supabase pooled connection (port 6543) |
| `DB_SSLMODE` | `require` |
| `SESSION_DRIVER`, `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `sync` (nothing on Vercel runs a worker) |
| `LOG_CHANNEL` | `stderr` |
| `FILESYSTEM_DISK`, `PUBLIC_DISK_DRIVER` | `s3` |
| `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION` | Supabase Storage S3 keys |
| `AWS_BUCKET` | private bucket (roster spreadsheets) |
| `AWS_PUBLIC_BUCKET`, `AWS_PUBLIC_URL` | public bucket (logos, profile pictures) and its public URL |
| `AWS_ENDPOINT`, `AWS_USE_PATH_STYLE_ENDPOINT` | Supabase S3 endpoint, `true` |
| `MAIL_MAILER`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | a real mailer. `log` sends nothing. |
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` | if `MAIL_MAILER=smtp` |
| `RESEND_API_KEY` | instead of the SMTP lines, if `MAIL_MAILER=resend` |
| `GEMINI_API_KEY` | for Linky |

Recommended: `SESSION_SECURE_COOKIE=true`. It is not set in your files, and the privacy policy says the site is served over HTTPS, so mark the session cookie secure too.

Optional: `GEMINI_MODEL`, `GEMINI_FALLBACK_MODEL` (have defaults), `SESSION_LIFETIME` (120), `LOG_LEVEL`, `MAIL_SCHEME`, `MAIL_URL`, `MAIL_EHLO_DOMAIN`, `DB_URL`, `BCRYPT_ROUNDS`, `DEMO_ACCOUNT_PASSWORD` (only if you ever seed demo data on the live database: then make it a strong one).

Local only (XAMPP): `DB_CONNECTION=sqlite`, `MAIL_MAILER=log`, `FILESYSTEM_DISK=local`, `PUBLIC_DISK_DRIVER=local`, `QUEUE_CONNECTION=database`.

Not used by JobLink, leave out of Vercel: `REDIS_*`, `MEMCACHED_HOST`, `DB_QUEUE*`, `BEANSTALKD_*`, `SQS_*`, `MYSQL_ATTR_SSL_CA`.

## 4. Vercel and Supabase check

I can't reach either service from here, so this is what to check yourself. Phases 8 to 11 added no environment variable, file storage, queue job or email, so nothing in them needs new setup.

1. **Deploy, then click through:** log in as each of the four demo logins on the live site (seed the demo data once if you want them there) and open the Dashboard and Jobs pages. A query that SQLite accepts and PostgreSQL rejects would show up as a 500 here.
2. **Case-insensitive search:** the check in section 2.
3. **Queue is `sync`:** with `QUEUE_CONNECTION=sync`, the roster import and the "new job" notifications to every student run inside the request. Check that `QUEUE_CONNECTION=sync` really is set in Vercel. If it says `database`, those jobs are queued and never run.
4. **Request time limit:** Vercel's current docs say a function's default and maximum on Hobby is 300 seconds when fluid compute is on (the default for new projects). An older project may have a shorter limit. Check Settings, then Functions, then Max Duration, because a big roster import runs in one request. Source: [Configuring maximum duration for Vercel Functions](https://vercel.com/docs/functions/configuring-functions/duration).
5. **Email:** with `MAIL_MAILER=log` nothing is sent. If you use Resend, confirm the `resend/resend-php` package is in `composer.json` and `config/services.php` has the `resend` key. I haven't seen those two files.
6. **Storage:** upload a profile picture and a company logo on the live site and check they show. The CV feature stores no files.
7. **Sessions over HTTPS:** after setting `SESSION_SECURE_COOKIE=true`, log in and out once to confirm sessions still work.

## 5. Privacy policy: three small edits to `resources/views/legal/privacy.blade.php`

Placeholders `[RETENTION PERIOD]`, `[EMAIL OR OFFICE LOCATION]` and `[NAME AND EMAIL]` are still yours to fill, if you haven't already.

1. Change `Last updated: November 16, 2026` to `Last updated: November 17, 2026`.
2. In section 2 ("Why we use it"), after the line starting `<li>To let partner companies find the CVs`, add:

```html
<li>To suggest open jobs on your dashboard by matching words from your skills, course and education with the job postings. This happens inside JobLink, is not shared with companies, and no decision is made about you from it.</li>
```

3. In section 3 ("Who can see it"), just before `<li>We do not sell personal information.</li>`, add:

```html
<li>A partner company's dashboard shows how many students and alumni have registered, as one number. It shows no names or other details.</li>
```

The Terms and Conditions need no change for Phases 9 to 11.

## After extracting

```powershell
php artisan test
```

## Part B: what I still need

- `app/Http/Controllers/Auth/ActivateAccountController.php` and the request classes it uses
- `app/Services/AccountProvisioningService.php`
- `app/Http/Controllers/ProfileController.php` and the request class behind `storeCompletion`
- `app/Jobs/ImportStudentRoster.php`
- `tests/Feature/CvEditTest.php` (for the exact CV form fields)
- `app/Services/NotificationService.php` and the mail class used for interview and rejection emails
- `app/Services/LinkyService.php` (its prompt may still mention uploading a resume)
- The output of this command, for the leftover resume mentions:

```powershell
cd C:\xamppp\htdocs\JobLink
Select-String -Path app,resources,routes,database,config,public,README.md -Pattern "resume|résumé" -Recurse | Select-Object Path,LineNumber,Line
```
