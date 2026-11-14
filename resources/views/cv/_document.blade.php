{{--
    Renders a CV from a snapshot array (see App\Support\CvSnapshot). Pass it as $cv.

    Used by: the company's application page, the printable page, and the
    student's "CV I sent" page. It only reads the array it is given, never the
    live profile, and it copes with missing keys so an older or smaller snapshot
    can't break the page. Everything is escaped, and links are only drawn when
    they start with https://.
--}}
@php
    $personal = (array) ($cv['personal'] ?? []);
    $isAlumni = ($personal['status'] ?? null) === 'alumni';

    $fmtMonth = function ($date) {
        if (! $date) {
            return null;
        }
        try {
            return \Illuminate\Support\Carbon::parse($date)->format('M Y');
        } catch (\Throwable $e) {
            return null;
        }
    };
    $safeLink = fn ($url) => is_string($url) && str_starts_with($url, 'https://') ? $url : null;

    $skills = array_values(array_filter((array) ($cv['skills'] ?? []), 'is_string'));
    $experiences = array_values(array_filter((array) ($cv['experiences'] ?? []), 'is_array'));
    $educations = array_values(array_filter((array) ($cv['educations'] ?? []), 'is_array'));
    $certifications = array_values(array_filter((array) ($cv['certifications'] ?? []), 'is_array'));

    $place = implode(', ', array_filter([$personal['city'] ?? null, $personal['province'] ?? null]));
    $linkedin = $safeLink($cv['links']['linkedin'] ?? null);
    $portfolio = $safeLink($cv['links']['portfolio'] ?? null);

    $schoolLine = $isAlumni
        ? trim('Graduated '.($personal['year_graduated'] ?? ''))
        : ($personal['year_level'] ?? '');

    $submitted = null;
    if (! empty($cv['taken_at'])) {
        try {
            $submitted = \Illuminate\Support\Carbon::parse($cv['taken_at'])->format('M j, Y');
        } catch (\Throwable $e) {
            $submitted = null;
        }
    }
@endphp

@once
<style>
    .cv-doc { color: var(--jl-ink); font-size: 0.95rem; line-height: 1.55; }
    .cv-doc-name { font-family: 'Lora', Georgia, serif; font-size: 1.8rem; line-height: 1.2; color: var(--jl-navy); margin: 0; }
    .cv-doc-sub { color: var(--jl-slate); margin: 0.25rem 0 0; }
    .cv-doc-contact { display: flex; flex-wrap: wrap; gap: 0.3rem 1.4rem; margin-top: 0.85rem; font-size: 0.88rem; }
    .cv-doc-contact a { color: var(--jl-navy); text-decoration: none; word-break: break-all; }
    .cv-doc-contact a:hover { text-decoration: underline; }
    .cv-doc-contact i { color: var(--jl-gold); margin-right: 0.35rem; }
    .cv-doc-section { margin-top: 1.6rem; }
    .cv-doc-heading { font-size: 0.78rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--jl-navy); border-bottom: 2px solid var(--jl-gold); padding-bottom: 0.3rem; margin: 0 0 0.9rem; }
    .cv-doc-entry { margin-bottom: 1rem; break-inside: avoid; }
    .cv-doc-entry-top { display: flex; justify-content: space-between; align-items: baseline; gap: 0.25rem 1rem; flex-wrap: wrap; }
    .cv-doc-entry-title { font-weight: 600; }
    .cv-doc-entry-meta { color: var(--jl-slate); font-size: 0.85rem; }
    .cv-doc-entry-org { color: var(--jl-slate); }
    .cv-doc-text { margin: 0.3rem 0 0; }
    .cv-doc-skills { display: flex; flex-wrap: wrap; gap: 0.4rem; }
    .cv-doc-skill { background: rgba(15, 42, 92, 0.08); color: var(--jl-navy); border-radius: 50rem; padding: 0.2rem 0.75rem; font-size: 0.82rem; font-weight: 600; }
    .cv-doc-foot { margin-top: 1.75rem; color: var(--jl-slate); font-size: 0.78rem; }
    @media print {
        .cv-doc-skill { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
</style>
@endonce

<div class="cv-doc">
    {{-- Header --}}
    <h2 class="cv-doc-name">{{ $personal['full_name'] ?? 'Applicant' }}</h2>
    @if(! empty($personal['course_program']))
        <p class="cv-doc-sub">{{ $personal['course_program'] }}@if($schoolLine) &middot; {{ $schoolLine }}@endif</p>
    @endif

    <div class="cv-doc-contact">
        @if(! empty($personal['email']))
            <span><i class="bi bi-envelope" aria-hidden="true"></i><a href="mailto:{{ $personal['email'] }}">{{ $personal['email'] }}</a></span>
        @endif
        @if(! empty($personal['mobile']))
            <span><i class="bi bi-telephone" aria-hidden="true"></i><a href="tel:{{ $personal['mobile'] }}">{{ $personal['mobile'] }}</a></span>
        @endif
        @if($place !== '')
            <span><i class="bi bi-geo-alt" aria-hidden="true"></i>{{ $place }}</span>
        @endif
        @if($linkedin)
            <span><i class="bi bi-linkedin" aria-hidden="true"></i><a href="{{ $linkedin }}" target="_blank" rel="noopener noreferrer">{{ $linkedin }}</a></span>
        @endif
        @if($portfolio)
            <span><i class="bi bi-globe2" aria-hidden="true"></i><a href="{{ $portfolio }}" target="_blank" rel="noopener noreferrer">{{ $portfolio }}</a></span>
        @endif
    </div>

    {{-- Summary --}}
    @if(! empty($cv['summary']))
        <div class="cv-doc-section">
            <h3 class="cv-doc-heading">Summary</h3>
            <p class="cv-doc-text mb-0">{!! nl2br(e($cv['summary'])) !!}</p>
        </div>
    @endif

    {{-- Skills --}}
    @if(count($skills))
        <div class="cv-doc-section">
            <h3 class="cv-doc-heading">Skills</h3>
            <div class="cv-doc-skills">
                @foreach($skills as $skill)
                    <span class="cv-doc-skill">{{ $skill }}</span>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Experience --}}
    @if(count($experiences))
        <div class="cv-doc-section">
            <h3 class="cv-doc-heading">Experience</h3>
            @foreach($experiences as $experience)
                @php
                    $kindLabel = \App\Models\ProfileExperience::KINDS[$experience['kind'] ?? ''] ?? null;
                    $start = $fmtMonth($experience['start_date'] ?? null);
                    $end = $fmtMonth($experience['end_date'] ?? null) ?? 'Present';
                @endphp
                <div class="cv-doc-entry">
                    <div class="cv-doc-entry-top">
                        <span class="cv-doc-entry-title">{{ $experience['position'] ?? '' }}</span>
                        @if($start)
                            <span class="cv-doc-entry-meta">{{ $start }} &ndash; {{ $end }}</span>
                        @endif
                    </div>
                    <div class="cv-doc-entry-org">
                        {{ $experience['organization'] ?? '' }}@if($kindLabel) &middot; {{ $kindLabel }}@endif
                    </div>
                    @if(! empty($experience['description']))
                        <p class="cv-doc-text">{!! nl2br(e($experience['description'])) !!}</p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Education: the PSU program first (from the school record), then anything else they added --}}
    @if(! empty($personal['course_program']) || count($educations))
        <div class="cv-doc-section">
            <h3 class="cv-doc-heading">Education</h3>

            @if(! empty($personal['course_program']))
                <div class="cv-doc-entry">
                    <div class="cv-doc-entry-top">
                        <span class="cv-doc-entry-title">{{ $personal['course_program'] }}</span>
                        @if($schoolLine)
                            <span class="cv-doc-entry-meta">{{ $schoolLine }}</span>
                        @endif
                    </div>
                    <div class="cv-doc-entry-org">Pangasinan State University &middot; Alaminos City Campus</div>
                </div>
            @endif

            @foreach($educations as $education)
                <div class="cv-doc-entry">
                    <div class="cv-doc-entry-top">
                        <span class="cv-doc-entry-title">{{ $education['school_name'] ?? '' }}</span>
                        @if(! empty($education['start_year']))
                            <span class="cv-doc-entry-meta">{{ $education['start_year'] }} &ndash; {{ $education['end_year'] ?? 'Present' }}</span>
                        @endif
                    </div>
                    <div class="cv-doc-entry-org">
                        {{ $education['level'] ?? '' }}@if(! empty($education['degree_program'])) &middot; {{ $education['degree_program'] }}@endif
                    </div>
                    @if(! empty($education['details']))
                        <p class="cv-doc-text">{!! nl2br(e($education['details'])) !!}</p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Certifications and seminars --}}
    @if(count($certifications))
        <div class="cv-doc-section">
            <h3 class="cv-doc-heading">Certifications and seminars</h3>
            @foreach($certifications as $certification)
                @php
                    $certKind = \App\Models\ProfileCertification::KINDS[$certification['kind'] ?? ''] ?? null;
                    $received = $fmtMonth($certification['date_received'] ?? null);
                @endphp
                <div class="cv-doc-entry">
                    <div class="cv-doc-entry-top">
                        <span class="cv-doc-entry-title">{{ $certification['title'] ?? '' }}</span>
                        @if($received)
                            <span class="cv-doc-entry-meta">{{ $received }}</span>
                        @endif
                    </div>
                    @if(! empty($certification['issuer']) || $certKind)
                        <div class="cv-doc-entry-org">
                            {{ $certification['issuer'] ?? '' }}@if(! empty($certification['issuer']) && $certKind) &middot; @endif{{ $certKind }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @if($submitted)
        <p class="cv-doc-foot">CV as submitted on {{ $submitted }}.</p>
    @endif
</div>
