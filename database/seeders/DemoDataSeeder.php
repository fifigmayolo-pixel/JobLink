<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\StudentAlumniProfile;
use App\Models\User;
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
                ['Company', 'company@joblink.test', $password, 'Demo Tech Solutions, 2 approved jobs, 1 applicant.'],
                ['Student', '99-0001-01', $password, 'Full CV, ready to apply. Already applied to one job.'],
                ['Alumni', '99-0002-02', $password, 'Profile done, CV empty: fill it in live, then apply.'],
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
            ]
        );

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

        return $user->load('profile');
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
            ]
        );

        $profile->skills()->delete();
        $profile->experiences()->delete();
        $profile->educations()->delete();
        $profile->certifications()->delete();

        return $user->load('profile');
    }

    /**
     * @return array{web: JobPosting, support: JobPosting, intern: JobPosting}
     */
    private function jobs(Company $company, User $companyUser, User $coordinator): array
    {
        $approved = [
            'approval_status' => 'approved',
            'status' => 'open',
            'reviewed_by' => $coordinator->id,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ];

        $web = JobPosting::updateOrCreate(
            ['company_id' => $company->id, 'title' => 'Junior Web Developer'],
            array_merge($approved, [
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

        return ['web' => $web, 'support' => $support, 'intern' => $intern];
    }
}
