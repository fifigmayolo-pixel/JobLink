<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\Notification;
use App\Models\ProfileClearance;
use App\Models\StudentAlumniProfile;
use App\Models\User;
use App\Support\ClearanceStore;
use App\Support\CvSnapshot;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo data for development and for the thesis defense. Safe to run again: it
 * updates the same rows instead of adding new ones, and it resets the demo jobs'
 * deadlines, so run it again shortly before a demo.
 *
 *   php artisan db:seed --class=DemoDataSeeder
 *
 * The password for all four logins is "password" unless DEMO_ACCOUNT_PASSWORD is
 * set in .env. Use a stronger one if this ever runs on the live database.
 *
 * It never overwrites the password of a Coordinator account that has already been
 * changed from the bootstrap default.
 *
 * What the demo shows, per login:
 *   Coordinator  one job waiting for approval
 *   Company      dashboard with a pending, two open and one closed posting, one applicant,
 *                and the student findable under Find Candidates
 *   Student      full CV and both clearances (sample PDFs), dashboard with a suggested job,
 *                one pending application, searchable by partner companies (switch on)
 *   Alumni       basic profile only: dashboard asks to finish the CV, no clearances, switch off,
 *                so the alumni does not show up in Find Candidates
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Roles, and coordinator@joblink.test if it does not exist yet.
        $this->call(RolesAndPermissionsSeeder::class);

        $password = env('DEMO_ACCOUNT_PASSWORD', 'password');

        $coordinator = $this->coordinator($password);
        $companyUser = $this->companyAccount($password, $coordinator);
        $student = $this->student($password);
        $alumni = $this->alumni($password);

        $company = $companyUser->company;
        $jobs = $this->jobs($company, $companyUser, $coordinator);

        $this->notifications($student, $companyUser, $jobs['web']);

        // One application already sent, so the company's Applicants page is not empty.
        // The student can still apply to the other open job live (and gets the
        // duplicate warning if they try this one again).
        JobApplication::updateOrCreate(
            ['job_posting_id' => $jobs['web']->id, 'applicant_id' => $student->id],
            [
                'profile_id' => $student->profile->id,
                'cv_snapshot' => CvSnapshot::fromProfile($student->profile),
                'date_applied' => now(),
                'status' => 'pending',
            ]
        );

        $this->command?->table(
            ['Role', 'Log in with', 'Password', 'Notes'],
            [
                ['Job Coordinator', 'coordinator@joblink.test', $password, 'Approves jobs. One job is waiting for approval.'],
                ['Company', 'company@joblink.test', $password, 'Demo Tech Solutions: 2 open jobs, 1 pending, 1 closed, 1 applicant. Can find the student under Find Candidates.'],
                ['Student', '99-0001-01', $password, 'Full CV and both clearances (sample PDFs), ready to apply, searchable by companies. Already applied to one job; the dashboard suggests another.'],
                ['Alumni', '99-0002-02', $password, 'Profile done, CV and clearances empty, not searchable: fill the CV in live and upload both clearances, then apply.'],
            ]
        );
    }

    private function coordinator(string $password): User
    {
        $coordinator = User::where('email', 'coordinator@joblink.test')->firstOrFail();

        // Only a Coordinator that is still on the bootstrap password is treated as a
        // demo login. One whose password was changed is left exactly as it is.
        if (Hash::check('password', $coordinator->password)) {
            $coordinator->forceFill([
                'password' => $password,
                'must_change_password' => false,
                'activated_at' => $coordinator->activated_at ?? now(),
            ])->save();
        }

        return $coordinator;
    }

    private function companyAccount(string $password, User $coordinator): User
    {
        $user = User::updateOrCreate(
            ['email' => 'company@joblink.test'],
            [
                'role' => 'company',
                'password' => $password,
                'must_change_password' => false,
                'activated_at' => now(),
            ]
        );

        // unguarded(): the Company model's $fillable list isn't assumed here.
        Company::unguarded(fn () => Company::updateOrCreate(
            ['user_id' => $user->id],
            [
                'company_name' => 'Demo Tech Solutions',
                'industry' => 'IT',
                'contact_person' => 'Maria Santos',
                'contact_number' => '09181234567',
                'address' => 'Alaminos City, Pangasinan',
                'created_by' => $coordinator->id,
            ]
        ));

        return $user->load('company');
    }

    private function student(string $password): User
    {
        $user = User::updateOrCreate(
            ['student_id_number' => '99-0001-01'],
            [
                'role' => 'student',
                'full_name' => 'Demo Student',
                'password' => $password,
                'must_change_password' => false,
                'activated_at' => now(),
                'terms_accepted_at' => now(),
            ]
        );

        $profile = StudentAlumniProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'status' => 'student',
                'full_name' => 'Demo Student',
                'date_of_birth' => now()->subYears(21)->toDateString(),
                'age' => 21,
                'gender' => 'Female',
                'civil_status' => 'Single',
                'nationality' => 'Filipino',
                'course_program' => 'BSIT',
                'year_level' => 'Fourth Year',
                'section' => 'A',
                'year_graduated' => null,
                'personal_email' => 'demo.student@example.com',
                'mobile_number' => '09171234567',
                'house_street' => '123 Rizal Street',
                'barangay' => 'Poblacion',
                'municipality_city' => 'Alaminos City',
                'province' => 'Pangasinan',
                'postal_code' => '2404',
                'profile_completed_at' => now(),
                'cv_summary' => 'Fourth-year BSIT student with hands-on OJT experience in web development and office IT support. Looking for an entry-level developer role where I can learn quickly and contribute to the team.',
                'linkedin_url' => 'https://www.linkedin.com/in/demo-student',
                'portfolio_url' => null,
                // The opt-in is on, so the demo company can find this CV (Phase 8).
                'searchable_by_companies' => true,
            ]
        );

        // The date the student chose it, kept as it is when the seeder runs again.
        if ($profile->searchable_consented_at === null) {
            $profile->forceFill(['searchable_consented_at' => now()])->save();
        }

        // A full CV (100%). Each section is replaced, so running this again is safe.
        $profile->skills()->delete();
        $profile->skills()->createMany(array_map(
            fn ($name) => ['name' => $name],
            ['HTML & CSS', 'JavaScript', 'PHP', 'Laravel', 'MySQL', 'Troubleshooting', 'Communication']
        ));

        $profile->experiences()->delete();
        $profile->experiences()->create([
            'kind' => 'ojt',
            'position' => 'IT Support Intern',
            'organization' => 'Alaminos City Hall',
            'start_date' => now()->subMonths(8)->startOfMonth()->toDateString(),
            'end_date' => now()->subMonths(6)->endOfMonth()->toDateString(),
            'description' => "Fixed printers and workstations for 40+ staff.\nHelped build an internal request-tracking web page using PHP and MySQL.",
        ]);

        $profile->educations()->delete();
        $profile->educations()->create([
            'level' => 'Senior High School',
            'school_name' => 'Alaminos City National High School',
            'degree_program' => 'ICT strand',
            'start_year' => now()->year - 8,
            'end_year' => now()->year - 6,
            'details' => 'With Honors',
        ]);

        $profile->certifications()->delete();
        $profile->certifications()->create([
            'kind' => 'certification',
            'title' => 'NC II Computer Systems Servicing',
            'issuer' => 'TESDA',
            'date_received' => now()->subYear()->toDateString(),
        ]);

        $this->clearances($profile);

        return $user->load('profile');
    }

    /**
     * Both required clearances, valid for most of a year, so the demo student can apply.
     * The PDFs are tiny samples that say so. They are written to the clearance disk unless
     * the tests are running, where the rows alone are enough.
     */
    private function clearances(StudentAlumniProfile $profile): void
    {
        foreach (config('clearances.types') as $type => $meta) {
            $path = trim((string) config('clearances.folder', 'clearances'), '/').'/'.$profile->id.'/'.$type.'-demo.pdf';
            $pdf = $this->samplePdf('SAMPLE '.strtoupper($meta['label']).' - JobLink demo only');

            if (! app()->runningUnitTests()) {
                ClearanceStore::disk()->put($path, $pdf);
            }

            ProfileClearance::updateOrCreate(
                ['profile_id' => $profile->id, 'type' => $type],
                [
                    'file_path' => $path,
                    'size_bytes' => strlen($pdf),
                    'valid_until' => now()->addMonths(10)->toDateString(),
                ]
            );
        }
    }

    /**
     * A one-page PDF with a line of text, built here so the demo needs no file in the repository.
     */
    private function samplePdf(string $text): string
    {
        $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        $stream = 'BT /F1 14 Tf 24 100 Td ('.$text.') Tj ET';

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 420 200] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($stream)." >>\nstream\n".$stream."\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $index => $body) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$body."\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";
    }

    private function alumni(string $password): User
    {
        $user = User::updateOrCreate(
            ['student_id_number' => '99-0002-02'],
            [
                'role' => 'alumni',
                'full_name' => 'Demo Alumni',
                'password' => $password,
                'must_change_password' => false,
                'activated_at' => now(),
                'terms_accepted_at' => now(),
            ]
        );

        // Basic profile only. The CV is left empty on purpose, so the demo can show
        // "Finish your CV to apply", then fill it in and apply.
        $profile = StudentAlumniProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'status' => 'alumni',
                'full_name' => 'Demo Alumni',
                'date_of_birth' => now()->subYears(23)->toDateString(),
                'age' => 23,
                'gender' => 'Male',
                'civil_status' => 'Single',
                'nationality' => 'Filipino',
                'course_program' => 'BSBA - Operations Management',
                'year_level' => null,
                'section' => null,
                'year_graduated' => '2025-2026',
                'personal_email' => 'demo.alumni@example.com',
                'mobile_number' => '09191234567',
                'house_street' => '45 Quezon Avenue',
                'barangay' => 'Poblacion',
                'municipality_city' => 'Alaminos City',
                'province' => 'Pangasinan',
                'postal_code' => '2404',
                'profile_completed_at' => now(),
                'cv_summary' => null,
                'linkedin_url' => null,
                'portfolio_url' => null,
                // The opt-in is off (the default), so this profile is never listed for companies.
                'searchable_by_companies' => false,
                'searchable_consented_at' => null,
            ]
        );

        $profile->skills()->delete();
        $profile->experiences()->delete();
        $profile->educations()->delete();
        $profile->certifications()->delete();

        return $user->load('profile');
    }

    /**
     * @return array{web: JobPosting, support: JobPosting, intern: JobPosting, closed: JobPosting}
     */
    private function jobs(Company $company, User $companyUser, User $coordinator): array
    {
        $approved = [
            'approval_status' => 'approved',
            'status' => 'open',
            'closed_by_slots' => false,
            'reviewed_by' => $coordinator->id,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ];

        $web = JobPosting::updateOrCreate(
            ['company_id' => $company->id, 'title' => 'Junior Web Developer'],
            array_merge($approved, [
                'slots' => 3,
                'job_type' => 'full_time',
                'description' => "Build and maintain web applications for our clients.\nWork with a small team using PHP, Laravel and MySQL.",
                'qualifications' => "Graduate of BSIT or a related course.\nKnowledge of HTML, CSS, JavaScript and PHP.",
                'requirements' => "Willing to work in Alaminos City.\nEager to learn new tools and frameworks.",
                'location' => 'Alaminos City, Pangasinan',
                'date_posted' => now()->toDateString(),
                'application_deadline' => now()->addDays(30)->toDateString(),
                'posted_by' => $companyUser->id,
            ])
        );

        $support = JobPosting::updateOrCreate(
            ['company_id' => $company->id, 'title' => 'Customer Service Associate'],
            array_merge($approved, [
                'slots' => 5,
                'job_type' => 'full_time',
                'description' => "Answer customer questions by phone, chat and email.\nKeep accurate records of every conversation.",
                'qualifications' => "College level or graduate of any business-related course.\nGood communication skills in English and Filipino.",
                'requirements' => "Comfortable with computers.\nAble to work in shifts.",
                'location' => 'Alaminos City, Pangasinan',
                'date_posted' => now()->toDateString(),
                'application_deadline' => now()->addDays(21)->toDateString(),
                'posted_by' => $companyUser->id,
            ])
        );

        // Waiting for the Coordinator, so "company posts, Coordinator approves" can be shown live.
        $intern = JobPosting::updateOrCreate(
            ['company_id' => $company->id, 'title' => 'IT Support Intern'],
            [
                'slots' => 2,
                'closed_by_slots' => false,
                'job_type' => 'internship',
                'description' => "Assist the IT team with day-to-day support.\nLearn how a real help desk works.",
                'qualifications' => 'Third or fourth year BSIT student.',
                'requirements' => 'Available at least 3 days a week.',
                'location' => 'Alaminos City, Pangasinan',
                'date_posted' => now()->toDateString(),
                'application_deadline' => now()->addDays(14)->toDateString(),
                'status' => 'open',
                'approval_status' => 'pending',
                'reviewed_by' => null,
                'reviewed_at' => null,
                'rejection_reason' => null,
                'posted_by' => $companyUser->id,
            ]
        );

        // Approved, then closed after its deadline: fills the "Closed" card on the
        // company dashboard. Students never see it.
        $closed = JobPosting::updateOrCreate(
            ['company_id' => $company->id, 'title' => 'Office Clerk'],
            array_merge($approved, [
                'slots' => 1,
                'job_type' => 'full_time',
                'description' => "Handle filing and front-desk tasks.\nKeep the office records in order.",
                'qualifications' => 'Any college level.',
                'requirements' => 'Neat and organized.',
                'location' => 'Alaminos City, Pangasinan',
                'date_posted' => now()->subDays(40)->toDateString(),
                'application_deadline' => now()->subDays(10)->toDateString(),
                'status' => 'closed',
                'posted_by' => $companyUser->id,
            ])
        );

        return ['web' => $web, 'support' => $support, 'intern' => $intern, 'closed' => $closed];
    }

    /**
     * One notification each for the student and the company, so the dashboards' notification
     * panels are not empty. Found by recipient, type and title, so running again adds nothing,
     * and each one is unread again after a run.
     */
    private function notifications(User $student, User $companyUser, JobPosting $approvedJob): void
    {
        Notification::updateOrCreate(
            ['recipient_id' => $student->id, 'type' => 'announcement', 'title' => 'Welcome to JobLink'],
            [
                'message' => 'Build your CV, browse open jobs and apply. Companies can only find you if you turn on "searchable" in your profile settings.',
                'read_at' => null,
            ]
        );

        Notification::updateOrCreate(
            ['recipient_id' => $companyUser->id, 'type' => 'job_posting_approved', 'related_job_posting_id' => $approvedJob->id],
            [
                'title' => 'Job posting approved',
                'message' => 'Your posting "'.$approvedJob->title.'" was approved. Students and alumni can now apply.',
                'read_at' => null,
            ]
        );
    }
}
