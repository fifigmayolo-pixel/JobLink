@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    // How each posting state looks. Same words and colors as Your Posted Jobs.
    $stateUi = [
        'pending' => ['label' => 'Pending approval', 'icon' => 'bi-hourglass-split', 'hint' => 'Waiting for the Coordinator', 'class' => 'jl-pill-urgent', 'style' => '', 'chip' => 'background: rgba(201,162,39,0.18); color: #8A6C16;'],
        'open' => ['label' => 'Open', 'icon' => 'bi-door-open', 'hint' => 'Students can apply', 'class' => 'jl-pill-neutral', 'style' => '', 'chip' => 'background: rgba(15,42,92,0.08); color: var(--jl-navy);'],
        'closed' => ['label' => 'Closed', 'icon' => 'bi-lock', 'hint' => 'Closed or past deadline', 'class' => '', 'style' => 'background: rgba(107,114,128,0.15); color: #4B5563;', 'chip' => 'background: rgba(107,114,128,0.15); color: #4B5563;'],
        'rejected' => ['label' => 'Not approved', 'icon' => 'bi-x-circle', 'hint' => 'Edit and resubmit', 'class' => '', 'style' => 'background: rgba(180,85,63,0.12); color: var(--jl-rose);', 'chip' => 'background: rgba(180,85,63,0.12); color: var(--jl-rose);'],
    ];
@endphp

<div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h2 mb-1">Dashboard</h1>
        <p class="text-secondary mb-0">Welcome back, {{ $company?->company_name ?? $user->email }}.</p>
    </div>
    @can('create', \App\Models\JobPosting::class)
        <a href="{{ route('jobs.create') }}" class="btn btn-jl-gold"><i class="bi bi-plus-lg" aria-hidden="true"></i> Post a new job</a>
    @endcan
</div>

{{-- ============ Overview numbers ============ --}}
<section class="mb-4" aria-label="Overview">
    <div class="row g-3">
        <div class="col-md-6">
            <div class="jl-card p-4 h-100">
                <div class="d-flex align-items-center gap-3">
                    <span class="jl-section-icon" style="width: 48px; height: 48px; font-size: 1.3rem;"><i class="bi bi-mortarboard" aria-hidden="true"></i></span>
                    <div style="min-width: 0;">
                        <div class="display-font" style="font-size: 2rem; line-height: 1.1; color: var(--jl-navy);">{{ number_format($registeredCount) }}</div>
                        <div class="fw-semibold">Registered students and alumni</div>
                    </div>
                </div>
                <div class="text-secondary small mt-2">Active accounts on JobLink, so you know how many people your postings can reach.</div>
            </div>
        </div>
        <div class="col-md-6">
            <a href="{{ route('company.applications.index') }}" class="text-decoration-none d-block h-100">
                <div class="jl-card jl-card-hover p-4 h-100">
                    <div class="d-flex align-items-center gap-3">
                        <span class="jl-section-icon" style="width: 48px; height: 48px; font-size: 1.3rem;"><i class="bi bi-people" aria-hidden="true"></i></span>
                        <div style="min-width: 0;">
                            <div class="display-font" style="font-size: 2rem; line-height: 1.1; color: var(--jl-navy);">{{ number_format($applicantsTotal) }}</div>
                            <div class="fw-semibold" style="color: var(--jl-ink);">{{ \Illuminate\Support\Str::plural('Applicant', $applicantsTotal) }} on your jobs</div>
                        </div>
                    </div>
                    <div class="small mt-2">
                        @if($waitingCount > 0)
                            <span class="jl-pill jl-pill-urgent">{{ $waitingCount }} waiting for your review</span>
                        @else
                            <span class="text-secondary">Nobody is waiting for your review.</span>
                        @endif
                    </div>
                </div>
            </a>
        </div>
    </div>
</section>

{{-- ============ Jobs by status ============ --}}
<section class="mb-4" aria-labelledby="dash-job-counts">
    <div class="d-flex justify-content-between align-items-baseline mb-2">
        <h2 class="h5 mb-0" id="dash-job-counts">Your jobs</h2>
        <span class="text-secondary small">{{ $totalJobs }} {{ \Illuminate\Support\Str::plural('posting', $totalJobs) }}</span>
    </div>
    <div class="row g-3">
        @foreach($stateUi as $state => $ui)
            <div class="col-6 col-lg-3">
                <a href="{{ route('jobs.index', ['status' => $state]) }}" class="text-decoration-none d-block h-100">
                    <div class="jl-card jl-card-hover p-3 h-100">
                        <div class="d-flex align-items-center gap-3">
                            <span class="jl-section-icon" style="width: 40px; height: 40px; font-size: 1.1rem; {{ $ui['chip'] }}">
                                <i class="bi {{ $ui['icon'] }}" aria-hidden="true"></i>
                            </span>
                            <div style="min-width: 0;">
                                <div class="display-font" style="font-size: 1.6rem; line-height: 1.1; color: var(--jl-navy);">{{ $jobCounts[$state] }}</div>
                                <div class="small fw-semibold" style="color: var(--jl-ink);">{{ $ui['label'] }}</div>
                            </div>
                        </div>
                        <div class="text-secondary small mt-2">{{ $ui['hint'] }}</div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
</section>

<div class="row g-4">
    {{-- ============ Latest postings ============ --}}
    <div class="col-lg-7">
        <section class="jl-card p-4 h-100" aria-labelledby="dash-latest-jobs">
            <div class="d-flex justify-content-between align-items-baseline mb-2">
                <h2 class="h5 mb-0" id="dash-latest-jobs">Your latest postings</h2>
                <a href="{{ route('jobs.index') }}" class="small">View all</a>
            </div>

            @forelse($latestJobs as $job)
                @php
                    $state = $job->listingState();
                    $ui = $stateUi[$state];
                    $applicants = (int) $job->applicants_count;
                @endphp
                <div class="d-flex justify-content-between align-items-center gap-3 py-3 {{ $loop->first ? '' : 'border-top' }}" style="border-color: rgba(15,42,92,0.08) !important;">
                    <div style="min-width: 0;">
                        <a href="{{ route('jobs.show', $job) }}" class="fw-semibold text-decoration-none d-block text-truncate" style="color: var(--jl-navy);">{{ $job->title }}</a>
                        <div class="text-secondary small">
                            Posted {{ $job->date_posted->format('M j, Y') }}
                            &middot; Deadline {{ $job->application_deadline->format('M j, Y') }}
                            &middot; {{ $applicants }} {{ \Illuminate\Support\Str::plural('applicant', $applicants) }}
                        </div>
                    </div>
                    <span class="jl-pill {{ $ui['class'] }} flex-shrink-0" @if($ui['style']) style="{{ $ui['style'] }}" @endif>{{ $ui['label'] }}</span>
                </div>
            @empty
                <div class="text-center py-4">
                    <span class="jl-section-icon mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.5rem;"><i class="bi bi-briefcase" aria-hidden="true"></i></span>
                    <h3 class="h6 mb-1">You have not posted a job yet</h3>
                    <p class="text-secondary small mb-3">Post your first job. The Job Coordinator reviews it, then students and alumni can apply.</p>
                    @can('create', \App\Models\JobPosting::class)
                        <a href="{{ route('jobs.create') }}" class="btn btn-jl-gold btn-sm">Post a new job</a>
                    @endcan
                </div>
            @endforelse
        </section>
    </div>

    {{-- ============ Recent applicants ============ --}}
    <div class="col-lg-5">
        <section class="jl-card p-4 h-100" aria-labelledby="dash-recent-applicants">
            <div class="d-flex justify-content-between align-items-baseline mb-2">
                <h2 class="h5 mb-0" id="dash-recent-applicants">Recent applicants</h2>
                <a href="{{ route('company.applications.index') }}" class="small">View all</a>
            </div>

            @forelse($recentApplicants as $application)
                @php $name = $application->applicantName(); @endphp
                <a href="{{ route('company.applications.show', $application) }}" class="d-flex align-items-center gap-3 py-3 text-decoration-none {{ $loop->first ? '' : 'border-top' }}" style="border-color: rgba(15,42,92,0.08) !important; color: inherit;">
                    @include('partials.profile-avatar', ['imagePath' => null, 'label' => $name, 'size' => 40])
                    <div class="flex-grow-1" style="min-width: 0;">
                        <div class="fw-semibold text-truncate" style="color: var(--jl-navy);">{{ $name }}</div>
                        <div class="text-secondary small text-truncate">
                            {{ $application->jobPosting?->title ?? 'Job removed' }} &middot; {{ $application->date_applied->format('M j, Y') }}
                        </div>
                        @if($application->applicantRemoved())
                            <span class="badge rounded-pill mt-1" style="background: rgba(107,114,128,0.12); color: var(--jl-slate);">Account removed</span>
                        @endif
                    </div>
                    <span class="badge rounded-pill jl-badge-{{ $application->status }} px-3 py-2 flex-shrink-0">{{ ucfirst($application->status) }}</span>
                </a>
            @empty
                <p class="text-secondary mb-0 py-2">No applicants yet. They show up here as soon as someone applies to one of your jobs.</p>
            @endforelse
        </section>
    </div>
</div>
@endsection
