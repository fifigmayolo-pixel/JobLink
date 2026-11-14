@extends('layouts.app')

@section('title', 'Browse Jobs')

@section('content')
@php
    // One query for the whole page instead of one per card. partials.job-card uses
    // it to show a filled "Saved" bookmark for jobs this user already saved.
    $savedJobIds = auth()->check() ? auth()->user()->savedJobs()->pluck('job_posting_id')->all() : [];

    $filtersActive = filled(request('search')) || filled(request('location')) || filled(request('job_type'));
@endphp

<div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h2 mb-1">Open Positions</h1>
        <p class="text-secondary mb-0">Opportunities tagged for PSU Alaminos City students and alumni.</p>
    </div>
    @can('create', \App\Models\JobPosting::class)
        <a href="{{ route('jobs.create') }}" class="btn btn-jl-gold">Post a job</a>
    @endcan
</div>

<form method="GET" action="{{ route('jobs.index') }}" class="jl-card p-3 mb-3" role="search">
    <div class="row g-2">
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search" aria-hidden="true"></i></span>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by job title" aria-label="Search by job title">
            </div>
        </div>
        <div class="col-md-3">
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-geo-alt" aria-hidden="true"></i></span>
                <input type="text" name="location" value="{{ request('location') }}" class="form-control" placeholder="Location" aria-label="Location">
            </div>
        </div>
        <div class="col-md-3">
            <select name="job_type" class="form-select" aria-label="Job type">
                <option value="" @selected(request('job_type', '') === '')>All Job Types</option>
                <option value="full_time" @selected(request('job_type') === 'full_time')>Full-time</option>
                <option value="part_time" @selected(request('job_type') === 'part_time')>Part-time</option>
                <option value="internship" @selected(request('job_type') === 'internship')>Internship</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-jl-navy flex-grow-1">Search</button>
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
        {{ $jobPostings->total() }} open position{{ $jobPostings->total() === 1 ? '' : 's' }}@if($filtersActive) matching your search @endif
    </p>

    <div class="jl-job-grid">
        @foreach($jobPostings as $job)
            @include('partials.job-card', ['job' => $job, 'savedJobIds' => $savedJobIds])
        @endforeach
    </div>

    <div class="mt-4">
        {{ $jobPostings->links() }}
    </div>
@else
    {{-- Nothing to show: either no filters matched, or no approved job is open right now. --}}
    <div class="jl-card p-5 text-center">
        <span class="jl-section-icon mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.5rem;">
            <i class="bi {{ $filtersActive ? 'bi-search' : 'bi-briefcase' }}" aria-hidden="true"></i>
        </span>
        <h2 class="h5 mb-1">{{ $filtersActive ? 'No jobs match your search' : 'No open positions right now' }}</h2>
        <p class="text-secondary mb-3">
            {{ $filtersActive
                ? 'Try a different title or location, or clear the filters to see everything that is open.'
                : 'Partner companies post here once the Job Coordinator approves them. Check back soon.' }}
        </p>
        @if($filtersActive)
            <a href="{{ route('jobs.index') }}" class="btn btn-jl-navy">Clear filters</a>
        @endif
    </div>
@endif
@endsection
