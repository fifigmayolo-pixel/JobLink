@extends('layouts.app')

@section('title', 'Applicants')

@section('content')
<h1 class="h2 mb-1">Applicants</h1>
<p class="text-secondary mb-4">Everyone who has applied to your job postings. Rejected and withdrawn applications are under Past.</p>

<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('company.applications.index') }}"
       class="btn btn-sm {{ $tab === 'active' ? 'btn-jl-navy' : 'btn-outline-secondary' }}">
        Active
        <span class="badge rounded-pill ms-1" style="background: {{ $tab === 'active' ? 'rgba(255,255,255,0.2)' : 'rgba(107,114,128,0.15)' }}; color: inherit;">{{ $activeCount }}</span>
    </a>
    <a href="{{ route('company.applications.index', ['tab' => 'past']) }}"
       class="btn btn-sm {{ $tab === 'past' ? 'btn-jl-navy' : 'btn-outline-secondary' }}">
        Past
        <span class="badge rounded-pill ms-1" style="background: {{ $tab === 'past' ? 'rgba(255,255,255,0.2)' : 'rgba(107,114,128,0.15)' }}; color: inherit;">{{ $pastCount }}</span>
    </a>
</div>

<form method="GET" class="mb-4" style="max-width: 260px;">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <select name="status" class="form-select" onchange="this.form.submit()">
        @if($tab === 'past')
            <option value="">All past</option>
            <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
            <option value="withdrawn" @selected(request('status') === 'withdrawn')>Withdrawn</option>
        @else
            <option value="">All active</option>
            <option value="pending" @selected(request('status') === 'pending')>Pending</option>
            <option value="interview" @selected(request('status') === 'interview')>Interview</option>
        @endif
    </select>
</form>

<hr class="jl-rule">

<div class="row g-3">
    @forelse($applications as $application)
        <div class="col-sm-6 col-lg-4">
            <div class="jl-card jl-card-hover h-100 p-4 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                    <h2 class="h6 mb-0">{{ $application->applicantName() }}</h2>
                    <span class="badge rounded-pill jl-badge-{{ $application->status }} px-3 py-2">{{ ucfirst($application->status) }}</span>
                </div>
                @if($application->applicantRemoved())
                    <div class="mb-2">
                        <span class="badge rounded-pill" style="background: rgba(107,114,128,0.12); color: var(--jl-slate);">Account removed</span>
                    </div>
                @endif
                <p class="text-secondary small mb-3 flex-grow-1">
                    {{ $application->jobPosting->title }} &middot; Applied {{ $application->date_applied->format('M j, Y') }}
                    @if($application->resumeRemoved())
                        &middot; Resume removed
                    @endif
                </p>
                <a href="{{ route('company.applications.show', $application) }}" class="btn btn-jl-navy btn-sm w-100">
                    {{ $tab === 'past' ? 'View Details' : 'Review Application' }}
                </a>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="jl-card p-5 text-center">
                <p class="mb-0 text-secondary">
                    {{ $tab === 'past' ? 'No past applications. Rejected and withdrawn applicants show up here.' : 'No active applications right now.' }}
                </p>
            </div>
        </div>
    @endforelse
</div>

<div class="mt-4">
    {{ $applications->links() }}
</div>
@endsection