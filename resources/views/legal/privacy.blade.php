@extends('layouts.app')

@section('title', 'Privacy Policy')

@section('content')
<div style="width: 100%; max-width: 860px; margin: 0 auto;">

    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}"
       class="btn btn-outline-secondary btn-sm mb-3 js-privacy-back">&larr; Back</a>

    <h1 class="h2 mb-1">Privacy Policy</h1>
    <p class="text-secondary mb-4">Last updated: October 30, 2026</p>

    <div class="jl-card p-4 p-md-5">
        <p>
            JobLink is the job-posting and tracer study platform of Pangasinan State University,
            Alaminos City Campus (PSU ACC). This policy explains what personal information we
            collect, why, and how we protect it, in line with the Data Privacy Act of 2012
            (Republic Act No. 10173).
        </p>

        <h2 class="h5 mt-4">1. What we collect</h2>
        <ul>
            <li><strong>Students and alumni:</strong> Student ID number, full name, program, section, year graduated, contact details, address, profile picture, the resumes you upload, and your job applications and saved jobs.</li>
            <li><strong>Companies:</strong> company name, logo, industry, contact person, contact number, address, email address, and the job postings you submit.</li>
            <li><strong>All users:</strong> email address, login activity, and notifications sent to you.</li>
            <li><strong>Tracer study:</strong> whether you opened the tracer study link for the current year.</li>
            <li><strong>Linky chatbot:</strong> the messages you type to Linky.</li>
            <li><strong>Audit records:</strong> changes made to records by the Job Coordinator, with the date and the reason.</li>
        </ul>

        <h2 class="h5 mt-4">2. Why we use it</h2>
        <ul>
            <li>To create and secure your account.</li>
            <li>To let students and alumni find and apply for jobs, and companies review applicants.</li>
            <li>To send notifications, announcements and interview or rejection messages by email and inside JobLink.</li>
            <li>To monitor tracer study participation and prepare campus reports.</li>
            <li>To keep the system safe and to correct records when needed.</li>
        </ul>

        <h2 class="h5 mt-4">3. Who can see it</h2>
        <ul>
            <li>The Job Coordinator can view and correct student and alumni records and company accounts.</li>
            <li>A company can see the information you include in an application to its job posting, including your resume.</li>
            <li>We do not sell personal information.</li>
        </ul>

        <h2 class="h5 mt-4">4. Services we rely on</h2>
        <p>
            JobLink is hosted on Railway. Emails are sent through an email delivery service.
            Linky answers your questions using Google's Gemini API, so the messages you type to
            Linky are sent to Google for processing. Please do not type passwords, ID numbers or
            other sensitive details into Linky. Page fonts, styling and icons are loaded from
            Google Fonts and the jsDelivr content network, which can see your IP address when your
            browser fetches those files.
        </p>

        <h2 class="h5 mt-4">5. How long we keep it</h2>
        <p>
            We keep your information while your account is active. Records the Job Coordinator
            removes are hidden from the system but may stay in the audit history for
            [RETENTION PERIOD].
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
            claim damages where applicable. To make a request, contact the Job Coordinator office.
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