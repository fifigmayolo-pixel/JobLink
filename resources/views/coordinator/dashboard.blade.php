@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<h1 class="h2 mb-1">Dashboard</h1>
<p class="text-secondary mb-4">JobLink system-wide statistics.</p>
<hr class="jl-rule">
<div class="row g-3 mb-4">
    @foreach([
        ['label' => 'Total Students', 'value' => $totalStudents],
        ['label' => 'Total Alumni', 'value' => $totalAlumni],
        ['label' => 'Total Companies', 'value' => $totalCompanies],
        ['label' => 'Jobs Posted', 'value' => $totalJobsPosted],
        ['label' => 'Applications Submitted', 'value' => $totalApplications],
    ] as $stat)
        <div class="col-6 col-md-4 col-lg">
            <div class="jl-card p-4 text-center h-100">
                <div class="display-font h2 mb-1" style="color: var(--jl-gold);">{{ number_format($stat['value']) }}</div>
                <div class="text-secondary small text-uppercase">{{ $stat['label'] }}</div>
            </div>
        </div>
    @endforeach
</div>
@role('job_coordinator')
<hr class="jl-rule">
<div class="row g-3">
    <div class="col-md-6">
        <a href="{{ route('coordinator.accounts.index') }}" class="jl-card p-4 d-block text-decoration-none h-100">
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="bi bi-person-badge" style="font-size: 1.75rem; color: var(--jl-navy);"></i>
                <h2 class="h6 mb-0">Accounts</h2>
            </div>
            <p class="text-secondary small mb-0">Add students/alumni, import a roster, create Company or PESO accounts.</p>
        </a>
    </div>
    <div class="col-md-6">
        <a href="{{ route('coordinator.records.index') }}" class="jl-card p-4 d-block text-decoration-none h-100">
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="bi bi-folder2-open" style="font-size: 1.75rem; color: var(--jl-navy);"></i>
                <h2 class="h6 mb-0">Student &amp; Alumni Records</h2>
            </div>
            <p class="text-secondary small mb-0">View, correct locked fields, and bulk-convert to alumni.</p>
        </a>
    </div>
    <div class="col-md-6">
        <a href="{{ route('coordinator.announcements.create') }}" class="jl-card p-4 d-block text-decoration-none h-100">
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="bi bi-megaphone" style="font-size: 1.75rem; color: var(--jl-navy);"></i>
                <h2 class="h6 mb-0">Post Announcement</h2>
            </div>
            <p class="text-secondary small mb-0">Send a notification to students, alumni, or everyone.</p>
        </a>
    </div>
    <div class="col-md-6">
        <a href="{{ route('coordinator.tracer-study-links.index') }}" class="jl-card p-4 d-block text-decoration-none h-100">
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="bi bi-link-45deg" style="font-size: 1.75rem; color: var(--jl-navy);"></i>
                <h2 class="h6 mb-0">Tracer Study Link</h2>
            </div>
            <p class="text-secondary small mb-0">Add, update, or replace the external link.</p>
        </a>
    </div>
</div>
@endrole
@endsection