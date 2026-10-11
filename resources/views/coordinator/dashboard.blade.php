@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<h1 class="h2 mb-1">Dashboard</h1>
<p class="text-secondary mb-4">JobLink system-wide statistics.</p>
<hr class="jl-rule">

<div class="row g-3 mb-4">
    @foreach([
        ['label' => 'Total Students', 'value' => $totalStudents, 'icon' => 'bi-mortarboard'],
        ['label' => 'Total Alumni', 'value' => $totalAlumni, 'icon' => 'bi-person-fill'],
        ['label' => 'Total Companies', 'value' => $totalCompanies, 'icon' => 'bi-building'],
        ['label' => 'Jobs Posted', 'value' => $totalJobsPosted, 'icon' => 'bi-briefcase'],
        ['label' => 'Applications Submitted', 'value' => $totalApplications, 'icon' => 'bi-file-earmark-text'],
    ] as $stat)
        <div class="col-6 col-md-4 col-lg">
            <div class="jl-card p-4 text-center h-100">
                <i class="bi {{ $stat['icon'] }} d-block mb-2" style="font-size: 1.75rem; color: var(--jl-gold);"></i>
                <div class="display-font h2 mb-1" style="color: var(--jl-gold);">{{ number_format($stat['value']) }}</div>
                <div class="text-secondary small text-uppercase">{{ $stat['label'] }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="jl-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">Recent Job Postings</h2>
                <a href="{{ route('jobs.index') }}" class="btn btn-outline-secondary btn-sm">View All &rarr;</a>
            </div>
            @forelse($recentJobPostings as $job)
                <div class="d-flex align-items-center justify-content-between py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <div class="d-flex align-items-center gap-3">
                        <div class="jl-company-logo" style="width: 40px; height: 40px;">
                            @if($job->company?->logo)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($job->company->logo) }}" alt="{{ $job->company->company_name }} logo">
                            @else
                                <div class="jl-company-logo-placeholder" style="font-size: 1.1rem;">
                                    <i class="bi bi-building"></i>
                                </div>
                            @endif
                        </div>
                        <div>
                            <div class="fw-semibold">{{ $job->title }}</div>
                            <div class="text-secondary small">{{ $job->company?->company_name ?? 'Company' }}</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge rounded-pill" style="background: rgba(15,42,92,0.08); color: var(--jl-navy); min-width: 80px; visibility: {{ $job->job_type ? 'visible' : 'hidden' }};">
                            {{ $job->job_type === 'full_time' ? 'Full-time' : ($job->job_type === 'part_time' ? 'Part-time' : '—') }}
                        </span>
                        <span class="text-secondary small" style="min-width: 90px; text-align: right;">{{ $job->date_posted?->format('M j, Y') }}</span>
                    </div>
                </div>
            @empty
                <p class="text-secondary small mb-0">No job postings yet.</p>
            @endforelse
        </div>
    </div>

    <div class="col-lg-4">
        <div class="jl-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">Application Overview</h2>
                <form method="GET">
                    <select name="range" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="today" @selected($applicationRange === 'today')>Today</option>
                        <option value="last_7_days" @selected($applicationRange === 'last_7_days')>Last 7 Days</option>
                        <option value="last_30_days" @selected($applicationRange === 'last_30_days')>Last 30 Days</option>
                        <option value="last_100_days" @selected($applicationRange === 'last_100_days')>Last 100 Days</option>
                        <option value="this_month" @selected($applicationRange === 'this_month')>This Month</option>
                    </select>
                </form>
            </div>

            @php
                $pendingPct = $totalForRange > 0 ? round($pendingCount / $totalForRange * 100) : 0;
                $interviewPct = $totalForRange > 0 ? round($interviewCount / $totalForRange * 100) : 0;
                $rejectedPct = $totalForRange > 0 ? max(0, 100 - $pendingPct - $interviewPct) : 0;
                $deg1 = $pendingPct * 3.6;
                $deg2 = $deg1 + $interviewPct * 3.6;
                $donutBackground = $totalForRange > 0
                    ? "conic-gradient(var(--jl-slate) 0deg {$deg1}deg, var(--jl-gold) {$deg1}deg {$deg2}deg, var(--jl-rose) {$deg2}deg 360deg)"
                    : 'rgba(107,114,128,0.12)';
            @endphp

            <div class="d-flex align-items-center gap-4 flex-wrap">
                <div style="position: relative; width: 140px; height: 140px; flex-shrink: 0;">
                    <div style="width: 100%; height: 100%; border-radius: 50%; background: {{ $donutBackground }};"></div>
                    <div style="position: absolute; inset: 16px; background: #fff; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                        <div class="display-font h4 mb-0" style="color: var(--jl-navy);">{{ $totalForRange }}</div>
                        <div class="text-secondary" style="font-size: 0.65rem;">Total Applications</div>
                    </div>
                </div>
                <div class="flex-grow-1" style="min-width: 160px;">
                    <div class="d-flex justify-content-between align-items-center py-1">
                        <span><i class="bi bi-circle-fill me-1" style="color: var(--jl-slate); font-size: 0.6rem;"></i>Pending</span>
                        <span>{{ $pendingCount }} <span class="text-secondary small">({{ $pendingPct }}%)</span></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-1">
                        <span><i class="bi bi-circle-fill me-1" style="color: var(--jl-gold); font-size: 0.6rem;"></i>Interview</span>
                        <span>{{ $interviewCount }} <span class="text-secondary small">({{ $interviewPct }}%)</span></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-1">
                        <span><i class="bi bi-circle-fill me-1" style="color: var(--jl-rose); font-size: 0.6rem;"></i>Rejected</span>
                        <span>{{ $rejectedCount }} <span class="text-secondary small">({{ $rejectedPct }}%)</span></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="jl-card p-4">
            <h2 class="h5 mb-3">Quick Actions</h2>
                        <div class="d-flex flex-wrap gap-3">
                <a href="{{ route('coordinator.accounts.company.create') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                    <i class="bi bi-building"></i> Create Company Account
                </a>
                @role('job_coordinator')
                    <a href="{{ route('coordinator.accounts.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                        <i class="bi bi-person-badge"></i> Accounts
                    </a>
                    <a href="{{ route('coordinator.announcements.create') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                        <i class="bi bi-megaphone"></i> Post Announcement
                    </a>
                    <a href="{{ route('coordinator.tracer-study-links.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                        <i class="bi bi-link-45deg"></i> Tracer Study Link
                    </a>
                @endrole
                <a href="{{ route('jobs.create') }}" class="btn btn-jl-gold d-inline-flex align-items-center gap-2">
                    <i class="bi bi-briefcase"></i> Post a Job
                </a>
            </div>
        </div>
    </div>
</div>
@endsection