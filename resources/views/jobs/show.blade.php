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

        <div class="row g-4 mt-1">
            {{-- Main column --}}
            <div class="col-lg-8">
                <div class="jl-card jl-detail-header p-4 p-md-5">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="jl-logo-badge jl-logo-badge-lg {{ $jobPosting->company?->logo ? '' : 'jl-logo-badge-fallback' }}" data-fallback-initial="{{ \App\Support\CompanyAccent::initial($jobPosting->company?->company_name) }}">
                                @if($jobPosting->company?->logo)
                                    {{-- Same self-healing fallback as the job card — see its comment. --}}
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($jobPosting->company->logo) }}" alt="{{ $jobPosting->company->company_name }} logo"
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
                                <p class="text-secondary small mb-3">
                                    Send your resume before the deadline on {{ $jobPosting->application_deadline->format('M j, Y') }}.
                                </p>

                                {{-- Picking a file no longer sends anything: it opens the confirmation modal below. --}}
                                <form method="POST" action="{{ route('applications.store', $jobPosting) }}" enctype="multipart/form-data" id="applyForm">
                                    @csrf
                                    <input type="file" name="resume" id="resumeInput" accept="application/pdf" class="d-none">
                                    <button type="button" class="btn btn-jl-accent w-100 mb-2" id="applyPickBtn">
                                        Apply with resume (PDF) <i class="bi bi-arrow-right"></i>
                                    </button>
                                </form>
                            @else
                                <p class="text-secondary small mb-3">You've already applied, or applications are no longer open for this role.</p>
                            @endcan

                            <form method="POST" action="{{ route('saved-jobs.store', $jobPosting) }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary w-100">
                                    <i class="bi bi-bookmark"></i> Save job
                                </button>
                            </form>

                            <p class="text-secondary text-center mt-3 mb-0" style="font-size: 0.78rem;">Only PDF resumes are accepted.</p>
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
@push('scripts')
{{-- Confirm-before-apply. Pushed to the scripts stack so the modal sits at the end of <body>. --}}
<div class="modal fade" id="applyConfirmModal" tabindex="-1" aria-labelledby="applyConfirmTitle" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content jl-card">
            <div class="modal-body p-4">
                <div class="text-center mb-3">
                    <i class="bi bi-file-earmark-pdf" style="font-size: 2.25rem; color: {{ $accent['base'] }};"></i>
                    <h4 class="h5 mt-2 mb-1" id="applyConfirmTitle">Submit your application?</h4>
                    <p class="text-secondary mb-0">{{ $jobPosting->title }} &middot; {{ $jobPosting->company?->company_name ?? 'Company' }}</p>
                </div>

                <div class="d-flex align-items-center gap-3 p-3 rounded border mb-3" style="background: var(--jl-cream);">
                    <i class="bi bi-file-earmark-text fs-3" style="color: var(--jl-navy);"></i>
                    <div style="min-width: 0;">
                        <div class="fw-semibold text-break" id="applyFileName"></div>
                        <div class="small text-secondary" id="applyFileSize"></div>
                    </div>
                </div>

                <div class="alert alert-danger py-2 small d-none" id="applyFileError" role="alert"></div>

                <p class="small text-secondary mb-3" id="applyConfirmHint">
                    This resume will be sent to the company. Please make sure it's the right file before you submit.
                </p>

                <div class="d-flex flex-column flex-sm-row gap-2 justify-content-end">
                    <button type="button" class="btn btn-link text-secondary" id="applyChooseAnother">Choose another file</button>
                    <button type="button" class="btn btn-outline-secondary" id="applyCancel">Cancel</button>
                    <button type="submit" form="applyForm" class="btn btn-jl-accent" id="applyConfirmBtn">Yes, submit application</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var form = document.getElementById('applyForm');
    var input = document.getElementById('resumeInput');
    var pickBtn = document.getElementById('applyPickBtn');
    var modalEl = document.getElementById('applyConfirmModal');
    if (!form || !input || !pickBtn || !modalEl || typeof bootstrap === 'undefined') { return; }

    var modal = new bootstrap.Modal(modalEl);
    var nameEl = document.getElementById('applyFileName');
    var sizeEl = document.getElementById('applyFileSize');
    var errEl = document.getElementById('applyFileError');
    var hintEl = document.getElementById('applyConfirmHint');
    var confirmBtn = document.getElementById('applyConfirmBtn');
    var cancelBtn = document.getElementById('applyCancel');
    var anotherBtn = document.getElementById('applyChooseAnother');
    var confirmLabel = confirmBtn.innerHTML;
    var submitting = false;

    function formatSize(bytes) {
        if (bytes < 1048576) { return Math.max(1, Math.round(bytes / 1024)) + ' KB'; }
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    pickBtn.addEventListener('click', function () { input.click(); });

    // A file was chosen: show it and ask first. Nothing is sent yet.
    input.addEventListener('change', function () {
        var file = input.files && input.files[0];
        if (!file) { return; }

        var problem = '';
        if (!/\.pdf$/i.test(file.name)) {
            problem = "This file isn't a PDF. Please choose your resume as a PDF.";
        } else if (file.size === 0) {
            problem = 'This file is empty. Please choose another one.';
        }

        nameEl.textContent = file.name;
        sizeEl.textContent = formatSize(file.size);
        errEl.textContent = problem;
        errEl.classList.toggle('d-none', problem === '');
        hintEl.classList.toggle('d-none', problem !== '');
        confirmBtn.classList.toggle('d-none', problem !== '');

        modal.show();
    });

    // Clearing the input means picking the same file again still triggers "change".
    cancelBtn.addEventListener('click', function () {
        input.value = '';
        modal.hide();
    });

    anotherBtn.addEventListener('click', function () {
        input.value = '';
        modal.hide();
        input.click();
    });

    // The confirm button submits the form (form="applyForm"). Lock it so a double click can't apply twice.
    form.addEventListener('submit', function (e) {
        if (submitting) { e.preventDefault(); return; }
        submitting = true;
        confirmBtn.disabled = true;
        cancelBtn.disabled = true;
        anotherBtn.disabled = true;
        confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Submitting…';
    });

    // Coming back with the Back button: undo the locked state.
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) { return; }
        submitting = false;
        confirmBtn.disabled = false;
        cancelBtn.disabled = false;
        anotherBtn.disabled = false;
        confirmBtn.innerHTML = confirmLabel;
        input.value = '';
    });
})();
</script>
@endpush
@endcan
@endrole
@endauth
@endsection 