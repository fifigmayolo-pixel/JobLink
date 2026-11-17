@extends('layouts.app')

@section('title', 'Your Posted Jobs')

@section('content')
@php
    // How each state looks. The state itself comes from JobPosting::listingState().
    $stateUi = [
        'pending' => ['label' => 'Pending approval', 'class' => 'jl-pill-urgent', 'style' => ''],
        'rejected' => ['label' => 'Not approved', 'class' => '', 'style' => 'background: rgba(180,85,63,0.12); color: var(--jl-rose);'],
        'open' => ['label' => 'Open', 'class' => 'jl-pill-neutral', 'style' => ''],
        'closed' => ['label' => 'Closed', 'class' => '', 'style' => 'background: rgba(107,114,128,0.15); color: #4B5563;'],
    ];
    $stateOptions = [
        '' => 'All statuses',
        'pending' => 'Pending approval',
        'rejected' => 'Not approved',
        'open' => 'Open',
        'closed' => 'Closed',
    ];
@endphp

<div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h2 mb-1">Your Posted Jobs</h1>
        <p class="text-secondary mb-0">Only your company's postings. A new posting waits for the Job Coordinator's approval before students and alumni can see it.</p>
    </div>
    @can('create', \App\Models\JobPosting::class)
        <a href="{{ route('jobs.create') }}" class="btn btn-jl-gold">Post a job</a>
    @endcan
</div>

<form method="GET" action="{{ route('jobs.index') }}" class="jl-card p-3 mb-3" role="search">
    <div class="row g-2">
        <div class="col-md-6">
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search" aria-hidden="true"></i></span>
                <input type="text" name="search" value="{{ $search }}" maxlength="100" class="form-control" placeholder="Search your jobs by title" aria-label="Search your jobs by title">
            </div>
        </div>
        <div class="col-md-4">
            <select name="status" class="form-select" aria-label="Status">
                @foreach($stateOptions as $value => $label)
                    <option value="{{ $value }}" @selected(($status ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-jl-navy flex-grow-1">Filter</button>
            @if($filtersActive)
                <a href="{{ route('jobs.index') }}" class="btn btn-outline-secondary" title="Clear filters" aria-label="Clear filters">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </a>
            @endif
        </div>
    </div>
</form>

@if($jobPostings->total() > 0)
    <p class="text-secondary small mb-3">
        {{ $jobPostings->total() }} {{ \Illuminate\Support\Str::plural('posting', $jobPostings->total()) }}@if($filtersActive) matching your filters @endif
    </p>

    <div class="d-flex flex-column gap-3">
        @foreach($jobPostings as $job)
            @php
                $state = $job->listingState();
                $ui = $stateUi[$state];
                $deadlinePassed = $state === 'closed' && $job->status === 'open';
                $applicants = (int) $job->applicants_count;
            @endphp
            <article class="jl-card jl-card-hover p-3 p-md-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div style="min-width: 0;">
                        <h2 class="h5 mb-1"><a href="{{ route('jobs.show', $job) }}" class="text-decoration-none" style="color: var(--jl-navy);">{{ $job->title }}</a></h2>
                        <div class="text-secondary small">
                            <i class="bi bi-geo-alt" aria-hidden="true"></i> {{ $job->location }}
                            @if($job->job_type)
                                &middot; {{ \App\Support\JobCardData::typeLabel($job->job_type) }}
                            @endif
                            &middot; Posted {{ $job->date_posted->format('M j, Y') }}
                            &middot; Deadline {{ $job->application_deadline->format('M j, Y') }}
                        </div>
                    </div>
                    <span class="jl-pill {{ $ui['class'] }} flex-shrink-0" @if($ui['style']) style="{{ $ui['style'] }}" @endif>
                        {{ $ui['label'] }}@if($deadlinePassed) &middot; deadline passed @endif
                    </span>
                </div>

                @if($state === 'rejected' && $job->rejection_reason)
                    <p class="small mt-2 mb-0" style="color: var(--jl-rose);"><strong>Reason:</strong> {{ $job->rejection_reason }}</p>
                @elseif($state === 'pending')
                    <p class="text-secondary small mt-2 mb-0">Waiting for the Job Coordinator. Students and alumni can't see it yet.</p>
                @endif

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                    <span class="text-secondary small">
                        <i class="bi bi-people" aria-hidden="true"></i>
                        {{ $applicants }} {{ \Illuminate\Support\Str::plural('applicant', $applicants) }}
                        @if($applicants > 0)
                            &middot; <a href="{{ route('company.applications.index') }}">Review applicants</a>
                        @endif
                    </span>
                    <span class="d-flex gap-2">
                        <a href="{{ route('jobs.show', $job) }}" class="btn btn-outline-secondary btn-sm">View</a>
                        @can('update', $job)
                            <a href="{{ route('jobs.edit', $job) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil" aria-hidden="true"></i> Edit</a>
                        @endcan
                    </span>
                </div>
            </article>
        @endforeach
    </div>

    <div class="mt-4">
        {{ $jobPostings->links() }}
    </div>
@else
    <div class="jl-card p-5 text-center">
        <span class="jl-section-icon mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.5rem;">
            <i class="bi {{ $filtersActive ? 'bi-search' : 'bi-briefcase' }}" aria-hidden="true"></i>
        </span>
        <h2 class="h5 mb-1">{{ $filtersActive ? 'No postings match your filters' : 'You have not posted a job yet' }}</h2>
        <p class="text-secondary mb-3">
            {{ $filtersActive
                ? 'Try another title or status, or clear the filters to see all your postings.'
                : 'Post your first job. The Job Coordinator reviews it, then students and alumni can apply.' }}
        </p>
        @if($filtersActive)
            <a href="{{ route('jobs.index') }}" class="btn btn-jl-navy">Clear filters</a>
        @else
            @can('create', \App\Models\JobPosting::class)
                <a href="{{ route('jobs.create') }}" class="btn btn-jl-gold">Post a job</a>
            @endcan
        @endif
    </div>
@endif
@endsection
