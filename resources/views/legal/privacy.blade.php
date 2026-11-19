@extends('layouts.app')

@section('title', 'Privacy Policy')

@section('content')
<div style="width: 100%; max-width: 860px; margin: 0 auto;">

    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}"
       class="btn btn-outline-secondary btn-sm mb-3 js-privacy-back">&larr; Back</a>

    <h1 class="h2 mb-1">Privacy Policy</h1>
    <p class="text-secondary mb-4">Last updated: November 19, 2026</p>

    <div class="jl-card p-4 p-md-5">
        <p>
            JobLink is the job-posting and tracer study platform of Pangasinan State University,
            Alaminos City Campus (PSU ACC). This policy explains what personal information we
            collect, why, and how we protect it, in line with the Data Privacy Act of 2012
            (Republic Act No. 10173).
        </p>

        <h2 class="h5 mt-4">1. What we collect</h2>
        <ul>
            <li><strong>Students and alumni:</strong> Student ID number, full name, program, section, year graduated, contact details, address, profile picture, your CV (summary, skills, experience, education and certifications), and your job applications and saved jobs.</li>
            <li>
                <strong>Clearances (students and alumni):</strong> a PDF of your NBI clearance and a PDF of your barangay clearance,
                and the date each one is valid until. A clearance can contain sensitive personal information, such as the result of
                a records check, so JobLink asks for your agreement on the upload page before you add your first one.
            </li>
            <li><strong>Your searchable setting:</strong> whether you chose to make your profile searchable by partner companies, and when you chose it.</li>
            <li><strong>Companies:</strong> company name, logo, industry, contact person, contact number, address, email address, and the job postings you submit.</li>
            <li><strong>All users:</strong> email address, login activity, and notifications sent to you.</li>
            <li><strong>Tracer study:</strong> whether you opened the tracer study link for the current year.</li>
            <li><strong>Linky chatbot:</strong> the messages you type to Linky.</li>
            <li><strong>Audit records:</strong> changes made to records by the Job Coordinator, with the date and the reason, and a record each time a company opens a searchable profile or one of your clearances. These records do not contain the files.</li>
        </ul>

        <h2 class="h5 mt-4">2. Why we use it</h2>
        <ul>
            <li>To create and secure your account.</li>
            <li>To let students and alumni find and apply for jobs, and companies review applicants.</li>
            <li>To check that applicants have a current NBI clearance and barangay clearance. JobLink requires both before you can apply, because employers may ask for them.</li>
            <li>To let partner companies find the CVs of students and alumni who chose to be searchable, so they can invite suitable people to apply.</li>
            <li>To send notifications, announcements and interview or rejection messages by email and inside JobLink.</li>
            <li>To monitor tracer study participation and prepare campus reports.</li>
            <li>To keep the system safe and to correct records when needed.</li>
        </ul>

        <h2 class="h5 mt-4">3. Who can see it</h2>
        <ul>
            <li>The Job Coordinator can view and correct student and alumni records and company accounts. The Job Coordinator's records pages do not show your CV or your clearances.</li>
            <li>A company can see the CV you send with an application to its job posting: your name, course and year, email, mobile number, city and province, summary, skills, experience, education, certifications and links. It does not see your date of birth, gender, civil status or street address.</li>
            <li>
                <strong>Searchable profile (your choice, off by default).</strong>
                If you turn on &ldquo;Make my profile searchable by partner companies&rdquo; in Profile settings, partner
                companies can find your CV when they search, even if you have not applied to them. They can see your name,
                course and year, city and province, and your CV (summary, skills, experience, education and certifications).
                They cannot see your email, mobile number or your LinkedIn and portfolio links. A company gets those only
                when you apply to one of its jobs. They never see your street address, date of birth, gender, civil status or
                profile picture, even after you apply. Contact details you type into your CV text are hidden from search
                where JobLink can recognise them, but you should not write them there. Joining JobLink or accepting the
                Terms &amp; Conditions does not turn this on. You can turn it off at any time, and your profile disappears
                from search straight away.
            </li>
            <li>
                <strong>Your clearances.</strong>
                You can open your own files at any time. A company you applied to can open your NBI clearance and barangay
                clearance files only while your application to that company is active. It can no longer open them if you
                withdraw the application or your account is removed. The Job Coordinator, other companies and other students
                cannot open them, and they are never shown when companies search for candidates. There are no public links:
                every file is opened through JobLink, which checks who is asking each time.
            </li>
            <li>We do not sell personal information.</li>
        </ul>

        <h2 class="h5 mt-4">4. Services we rely on</h2>
        <p>
            JobLink is hosted on Vercel, and its database and stored files are kept with Supabase. Clearance files are kept in a private storage bucket that is not open to the public.
            Emails are sent through an email delivery service.
            Linky answers your questions using Google's Gemini API, so the messages you type to
            Linky are sent to Google for processing. Please do not type passwords, ID numbers or
            other sensitive details into Linky. Page fonts, styling and icons are loaded from
            Google Fonts and the jsDelivr content network, which can see your IP address when your
            browser fetches those files.
        </p>

        <h2 class="h5 mt-4">5. How long we keep it</h2>
        <p>
            We keep your information while your account is active. When you apply, the company gets a copy
            of your CV as it was that day. If you withdraw the application, that copy is deleted. Copies
            for other applications are kept so the company can finish reviewing them. The application
            record (the job, the date and the status) is kept for reporting. Records the Job Coordinator
            removes are hidden from the system but may stay in the audit history for [RETENTION PERIOD].
        </p>
        <p>
            Your clearance files are kept while your account is active. When you replace a clearance or remove it, the old file
            is deleted from storage. If you withdraw an application, that company can no longer open your clearances. If your
            account is removed, companies can no longer open them, and the files stay in private storage until the account is
            permanently deleted.
        </p>
        <p>
            Your searchable setting is kept while your account is active. The audit history keeps the date you
            turned it on or off, and which companies opened your searchable profile and when.
        </p>

        <h2 class="h5 mt-4">6. How we protect it</h2>
        <p>
            Access is limited by role, passwords are stored in hashed form, and the site is served
            over HTTPS. No system is completely secure, so please keep your password private and
            log out on shared computers.
        </p>

        <h2 class="h5 mt-4">7. Cookies and automatic logout</h2>
        <p>
            JobLink uses only the cookies it needs to work: a session cookie that keeps you logged
            in and a security cookie that protects forms. We do not use advertising or tracking
            cookies. See our <a href="{{ route('cookies') }}">Cookie Policy</a> for details.
        </p>
        <p>
            For your security, JobLink logs you out automatically after a period of inactivity
            ({{ \App\Support\IdleTimeout::DEFAULT_MINUTES }} minutes, or
            {{ \App\Support\IdleTimeout::COORDINATOR_MINUTES }} minutes for the Job Coordinator) and
            warns you shortly before it happens.
        </p>

        <h2 class="h5 mt-4">8. Your rights</h2>
        <p>
            Under the Data Privacy Act you may ask to access your information, correct it, object
            to its processing, request that it be erased or blocked, receive a copy of it, and
            claim damages where applicable. You can withdraw your consent to being searchable at any
            time in Profile settings. You can replace or remove a clearance yourself at any time under My Clearances, which deletes the file. To make any other request, contact the Job Coordinator office.
            You may also file a complaint with the National Privacy Commission (privacy.gov.ph).
        </p>

        <h2 class="h5 mt-4">9. Contact</h2>
        <p class="mb-0">
            Job Coordinator office: [EMAIL OR OFFICE LOCATION]<br>
            Data Protection Officer: [NAME AND EMAIL]
        </p>
    </div>

</div>

@push('scripts')
<script>
document.querySelectorAll('.js-privacy-back').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
        var fallback = this.href;
        var fromSite = document.referrer && document.referrer.indexOf(location.origin) === 0;

        if (!fromSite) return;            // typed or bookmarked: just follow the link

        e.preventDefault();
        if (history.length > 1) {         // came from a page in this tab (login, register)
            history.back();
        } else {                          // opened in a new tab (Complete Profile checkbox)
            window.close();
            setTimeout(function () { window.location.href = fallback; }, 150);
        }
    });
});
</script>
@endpush
@endsection
