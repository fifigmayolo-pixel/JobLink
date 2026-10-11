@extends('layouts.app')

@section('title', 'Browse Jobs')

@section('content')
<div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h2 mb-1">Open Positions</h1>
        <p class="text-secondary mb-0">Opportunities tagged for PSU Alaminos City students and alumni.</p>
    </div>
    @can('create', \App\Models\JobPosting::class)
        <a href="{{ route('jobs.create') }}" class="btn btn-jl-gold">Post a job</a>
    @endcan
</div>

<form method="GET" class="row g-2 mb-4">
    <div class="col-md-4">
        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by job title">
    </div>
    <div class="col-md-3">
        <input type="text" name="location" value="{{ request('location') }}" class="form-control" placeholder="Location">
    </div>
    <div class="col-md-3">
        <select name="job_type" class="form-select">
            <option value="" @selected(request('job_type', '') === '')>All Job Types</option>
            <option value="full_time" @selected(request('job_type') === 'full_time')>Full-time</option>
            <option value="part_time" @selected(request('job_type') === 'part_time')>Part-time</option>
        </select>
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-jl-navy w-100">Search</button>
    </div>
</form>

<hr class="jl-rule">

<div class="row g-3">
    @forelse($jobPostings as $job)
        <div class="col-sm-6 col-lg-4">
                        <div class="jl-card jl-card-hover h-100 p-4 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                    <div class="d-flex align-items-center gap-2">
                        <div class="jl-company-logo" style="width: 40px; height: 40px;">
                            @if($job->company?->logo)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($job->company->logo) }}" alt="{{ $job->company->company_name }} logo">
                            @else
                                <div class="jl-company-logo-placeholder" style="font-size: 1.1rem;">
                                    <i class="bi bi-building"></i>
                                </div>
                            @endif
                        </div>
                        <h2 class="h5 mb-0">{{ $job->title }}</h2>
                    </div>
                    @if($job->job_type)
                        <span class="badge rounded-pill flex-shrink-0" style="background: rgba(15,42,92,0.08); color: var(--jl-navy); font-weight: 600;">
                            {{ $job->job_type === 'full_time' ? 'Full-time' : 'Part-time' }}
                        </span>
                    @endif
                </div>
                <p class="text-secondary small mb-3">{{ $job->company?->company_name ?? 'Company' }} &middot; {{ $job->location }}</p>
                <p class="mb-3 flex-grow-1">{{ \Illuminate\Support\Str::limit($job->description, 110) }}</p>
                <p class="text-secondary small mb-3">Deadline {{ $job->application_deadline->format('M j, Y') }}</p>
                <a href="{{ route('jobs.show', $job) }}" class="btn btn-jl-navy btn-sm w-100">View</a>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="jl-card p-5 text-center">
                <p class="mb-0 text-secondary">No open positions match your search right now. Check back soon.</p>
            </div>
        </div>
    @endforelse
</div>

<div class="mt-4">
    {{ $jobPostings->links() }}
</div>
@endsection