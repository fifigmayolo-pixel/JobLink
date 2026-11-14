@extends('layouts.app')

@section('title', $jobPosting->title)

@section('content')
@php
    $accent = \App\Support\CompanyAccent::for($jobPosting->company?->id, $jobPosting->company?->company_name);

    $deadline = $jobPosting->application_deadline->copy()->startOfDay();
    $daysLeft = now()->startOfDay()->diffInDays($deadline, false); // positive = days remaining
    $isUrgent = $daysLeft >= 0 && $daysLeft <= 14;

    $jobTypeLabels = [
        'full_time' => 'Full-time',
        'part_time' => 'Part-time',
        'internship' => 'Internship',
    ];
    $jobTypeLabel = $jobTypeLabels[$jobPosting->job_type] ?? \Illuminate\Support\Str::headline($jobPosting->job_type ?? '');

    // Students and alumni can only apply once their CV has a summary and a skill.
    $cvState = null;
    if (auth()->check() && in_array(auth()->user()->role, ['student', 'alumni'])) {
        $cvState = auth()->user()->profile?->cvCompleteness();
    }
    $cvReady = $cvState['ready'] ?? false;
    $cvMissing = $cvState['missing_required'] ?? ['Personal summary', 'Skills'];
@endphp

<div class="row justify-content-center" style="--accent: {{ $accent['base'] }}; --accent-soft: {{ $accent['soft'] }}; --accent-text: {{ $accent['text'] }}; --accent-deep: {{ $accent['deep'] }};">
    <div class="col-lg-10">
        <a href="{{ route('jobs.index') }}" class="btn btn-outline-secondary btn-sm">&larr; Back to all jobs</a>

        @if($jobPosting->isPending())
            <div class="alert alert-light jl-card mt-3 mb-0" style="border-left: 4px solid var(--jl-gold) !important;" role="alert">
                <strong>Pending approval.</strong>
                Students and alumni can't see this posting until the Job Coordinator approves it.
                @role('job_coordinator')
                    <a href="{{ route('coordinator.job-approvals.index') }}">Review it in Job Approvals</a>.
                @endrole
            </div>
        @elseif($jobPosting->isRejected())
            <div class="alert alert-light jl-card mt-3 mb-0" style="border-left: 4px solid var(--jl-rose) !important;" role="alert">
                <strong>Not approved.</strong>
                @if($jobPosting->rejection_reason)
                    Reason: {{ $jobPosting->rejection_reason }}
                @endif
                @can('update', $jobPosting)
                    <div class="small mt-1">Edit the posting and save it to resubmit it for review.</div>
                @endcan
            </div>
        @endif

        @unless($jobPosting->companyIsActive())
            <div class="alert alert-light jl-card mt-3 mb-0" style="border-left: 4px solid var(--jl-rose) !important;" role="alert">
                <strong>Company account archived.</strong>
                This posting is hidden from students and alumni. It will reappear if the account is restored and the deadline hasn't passed.
            </div>
        @endunless

        <div class="row g-4 mt-1">
            {{-- Main column --}}
            <div class="col-lg-8">
                <div class="jl-card jl-detail-header p-4 p-md-5">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="jl-logo-badge jl-logo-badge-lg {{ $jobPosting->company?->logo ? '' : 'jl-logo-badge-fallback' }}" data-fallback-initial="{{ \App\Support\CompanyAccent::initial($jobPosting->company?->company_name) }}">
                                @if($jobPosting->company?->logo)
                                    {{-- Same self-healing fallback as the job card — see its comment. --}}
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($jobPosting->company->logo) }}" alt="{{ $jobPosting->company->company_name }} logo"
                                         onerror="this.closest('.jl-logo-badge').classList.add('jl-logo-badge-fallback'); this.closest('.jl-logo-badge').textContent = this.closest('.jl-logo-badge').dataset.fallbackInitial;">
                                @else
                                    {{ \App\Support\CompanyAccent::initial($jobPosting->company?->company_name) }}
                                @endif
                            </div>
                            <div>
                                <div class="fw-semibold" style="color: var(--jl-navy);">{{ $jobPosting->company?->company_name ?? 'Company' }}</div>
                                <div class="text-secondary small"><i class="bi bi-geo-alt"></i> {{ $jobPosting->location }}</div>
                            </div>
                        </div>
                        @if($jobTypeLabel)
                            <span class="jl-pill jl-pill-neutral flex-shrink-0">{{ $jobTypeLabel }}</span>
                        @endif
                    </div>

                    <h1 class="h3 mb-3">{{ $jobPosting->title }}</h1>

                    <div class="row g-2 mb-1">
                        <div class="col-4">
                            <div class="jl-stat-box">
                                <div class="jl-stat-label">Posted</div>
                                <div class="jl-stat-value">{{ $jobPosting->date_posted->format('M j, Y') }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="jl-stat-box">
                                <div class="jl-stat-label">Deadline</div>
                                <div class="jl-stat-value">{{ $jobPosting->application_deadline->format('M j, Y') }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="jl-stat-box {{ $isUrgent ? 'jl-stat-box-urgent' : '' }}">
                                <div class="jl-stat-label">Time left</div>
                                <div class="jl-stat-value">
                                    @if($daysLeft < 0)
                                        Closed
                                    @else
                                        {{ $daysLeft }} {{ \Illuminate\Support\Str::plural('day', $daysLeft) }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @can('update', $jobPosting)
                        <div class="mt-3">
                            <a href="{{ route('jobs.edit', $jobPosting) }}" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                        </div>
                    @endcan
                </div>

                <div class="jl-card p-4 p-md-5 mt-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="jl-section-icon"><i class="bi bi-file-text"></i></span>
                        <h2 class="h6 text-uppercase text-secondary mb-0">Description</h2>
                    </div>
                    <ul class="jl-dot-list mb-0">
                        @forelse($jobPosting->descriptionLines() as $line)
                            <li>{{ $line }}</li>
                        @empty
                            <li class="text-secondary">No description provided.</li>
                        @endforelse
                    </ul>

                    <hr class="jl-rule">

                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="jl-section-icon"><i class="bi bi-mortarboard"></i></span>
                        <h2 class="h6 text-uppercase text-secondary mb-0">Qualifications</h2>
                    </div>
                    <ul class="jl-dot-list mb-0">
                        @forelse($jobPosting->qualificationsLines() as $line)
                            <li>{{ $line }}</li>
                        @empty
                            <li class="text-secondary">No qualifications listed.</li>
                        @endforelse
                    </ul>

                    <hr class="jl-rule">

                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="jl-section-icon"><i class="bi bi-check2-square"></i></span>
                        <h2 class="h6 text-uppercase text-secondary mb-0">Requirements</h2>
                    </div>
                    <ul class="jl-dot-list mb-0">
                        @forelse($jobPosting->requirementsLines() as $line)
                            <li>{{ $line }}</li>
                        @empty
                            <li class="text-secondary">No requirements listed.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="col-lg-4">
                <div class="jl-card p-4 jl-sidebar-sticky">
                    <h2 class="h6 mb-2">Interested in this role?</h2>

                    @auth
                        @role('student|alumni')
                            @can('create', [\App\Models\JobApplication::class, $jobPosting])
                                @if($cvReady)
                                    <p class="text-secondary small mb-3">
                                        Your CV from your JobLink profile is sent to the company. Apply before the deadline on {{ $jobPosting->application_deadline->format('M j, Y') }}.
                                    </p>

                                    {{-- The button opens the confirmation modal below. Nothing is sent until the student confirms. --}}
                                    <form method="POST" action="{{ route('applications.store', $jobPosting) }}" id="applyForm">
                                        @csrf
                                        <button type="button" class="btn btn-jl-accent w-100 mb-2" data-bs-toggle="modal" data-bs-target="#applyConfirmModal">
                                            Apply now <i class="bi bi-arrow-right"></i>
                                        </button>
                                    </form>
                                @else
                                    <div class="alert alert-light jl-card py-3 small mb-3" style="border-left: 4px solid var(--jl-gold) !important;" role="alert">
                                        <strong>Finish your CV to apply.</strong>
                                        <div class="mt-1">Still needed: {{ implode(' and ', $cvMissing) }}.</div>
                                    </div>
                                    <a href="{{ route('cv.edit') }}" class="btn btn-jl-accent w-100 mb-2">
                                        Finish my CV <i class="bi bi-arrow-right"></i>
                                    </a>
                                @endif
                            @else
                                <p class="text-secondary small mb-3">Applications are no longer open for this role.</p>
                            @endcan

                            <form method="POST" action="{{ route('saved-jobs.store', $jobPosting) }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary w-100">
                                    <i class="bi bi-bookmark"></i> Save job
                                </button>
                            </form>
                        @endrole
                    @endauth

                    @guest
                        <p class="text-secondary small mb-3">Log in as a student or alumni to apply for this role.</p>
                        <a href="{{ route('login') }}" class="btn btn-jl-accent w-100">Log in to apply</a>
                    @endguest

                    @auth
                        @role('company|job_coordinator')
                            <p class="text-secondary small mb-0">Applications are only open to PSU Alaminos City students and alumni.</p>
                        @endrole
                    @endauth
                </div>
            </div>
        </div>
    </div>
</div>

@auth
@role('student|alumni')
@can('create', [\App\Models\JobApplication::class, $jobPosting])
@if($cvReady)
@push('scripts')
{{-- Confirm-before-apply. Pushed to the scripts stack so the modal sits at the end of <body>. --}}
<div class="modal fade" id="applyConfirmModal" tabindex="-1" aria-labelledby="applyConfirmTitle" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content jl-card">
            <div class="modal-body p-4">
                <div class="text-center mb-3">
                    <i class="bi bi-person-lines-fill" style="font-size: 2.25rem; color: {{ $accent['base'] }};"></i>
                    <h4 class="h5 mt-2 mb-1" id="applyConfirmTitle">Submit your application?</h4>
                    <p class="text-secondary mb-0">{{ $jobPosting->title }} &middot; {{ $jobPosting->company?->company_name ?? 'Company' }}</p>
                </div>

                <p class="small text-secondary mb-2">
                    The company gets a copy of your CV as it is today: your name, course and year, email, mobile number,
                    city and province, summary, skills, experience, education and certifications.
                </p>
                <p class="small text-secondary mb-3">
                    It does not include your date of birth, gender, civil status or street address.
                    Changing your CV later does not change what you send now.
                    <a href="{{ route('cv.edit') }}" target="_blank" rel="noopener">Review my CV</a>
                </p>

                <div class="d-flex flex-column flex-sm-row gap-2 justify-content-end">
                    <button type="button" class="btn btn-outline-secondary" id="applyCancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="applyForm" class="btn btn-jl-accent" id="applyConfirmBtn">Yes, submit application</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var form = document.getElementById('applyForm');
    var confirmBtn = document.getElementById('applyConfirmBtn');
    var cancelBtn = document.getElementById('applyCancel');
    if (!form || !confirmBtn || !cancelBtn) { return; }

    var confirmLabel = confirmBtn.innerHTML;
    var submitting = false;

    // The confirm button submits the form (form="applyForm"). Lock it so a double click can't apply twice.
    form.addEventListener('submit', function (e) {
        if (submitting) { e.preventDefault(); return; }
        submitting = true;
        confirmBtn.disabled = true;
        cancelBtn.disabled = true;
        confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Submitting…';
    });

    // Coming back with the Back button: undo the locked state.
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) { return; }
        submitting = false;
        confirmBtn.disabled = false;
        cancelBtn.disabled = false;
        confirmBtn.innerHTML = confirmLabel;
    });
})();
</script>
@endpush
@endif
@endcan
@endrole
@endauth
@endsection
