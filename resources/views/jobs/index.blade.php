@extends('layouts.app')

@section('title', 'Browse Jobs')

@section('content')
@php
    // One query for the whole page instead of one per card — used by
    // partials.job-card to show a filled "Saved" icon instead of the
    // outline "Save" icon for jobs this user already bookmarked.
    $savedJobIds = auth()->check() ? auth()->user()->savedJobs()->pluck('job_posting_id')->all() : [];
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

<p class="text-secondary small mb-3">{{ $jobPostings->total() }} open position{{ $jobPostings->total() === 1 ? '' : 's' }}</p>

{{--
    Deliberately NOT Bootstrap's row/col grid. A col-lg-4 column is sized
    to a third of the row no matter how wide its content is, so once the
    card itself got a fixed max-width, it just sat in the corner of an
    oversized column — the big gap you saw. Flex-wrap instead sizes each
    card to itself and lets them sit side by side with a plain gap.
--}}
<div class="d-flex flex-wrap gap-3">
    @forelse($jobPostings as $job)
        @include('partials.job-card', ['job' => $job, 'savedJobIds' => $savedJobIds])
    @empty
        <div class="jl-card p-5 text-center w-100">
            <p class="mb-0 text-secondary">No open positions match your search right now. Check back soon.</p>
        </div>
    @endforelse
</div>

<div class="mt-4">
    {{ $jobPostings->links() }}
</div>
@endsection