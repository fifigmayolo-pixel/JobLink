@extends('layouts.app')

@section('title', $jobPosting->title)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
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

        <div class="jl-card p-4 p-md-5 mt-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="jl-company-logo">
                        @if($jobPosting->company?->logo)
                           <img src="{{ \Illuminate\Support\Facades\Storage::url($jobPosting->company->logo) }}" alt="{{ $jobPosting->company->company_name }} logo">
                        @else
                            <div class="jl-company-logo-placeholder">
                                <i class="bi bi-building"></i>
                            </div>
                        @endif
                    </div>
                    <div>
                        <h1 class="h3 mb-1">{{ $jobPosting->title }}</h1>
                        <p class="text-secondary mb-0">{{ $jobPosting->company?->company_name ?? 'Company' }} &middot; {{ $jobPosting->location }}</p>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    @auth
                        @role('student|alumni')
                            <form method="POST" action="{{ route('saved-jobs.store', $jobPosting) }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary">
                                    <i class="bi bi-bookmark"></i> Save
                                </button>
                            </form>
                            @can('create', [\App\Models\JobApplication::class, $jobPosting])
                                <form method="POST" action="{{ route('applications.store', $jobPosting) }}" enctype="multipart/form-data" id="applyForm">
                                    @csrf
                                    <input type="file" name="resume" id="resumeInput" accept="application/pdf" class="d-none" onchange="document.getElementById('applyForm').submit()">
                                    <button type="button" class="btn btn-jl-gold" onclick="document.getElementById('resumeInput').click()">Apply with resume (PDF)</button>
                                </form>
                            @endcan
                        @endrole
                    @endauth
                    @can('update', $jobPosting)
                        <a href="{{ route('jobs.edit', $jobPosting) }}" class="btn btn-outline-secondary">Edit</a>
                    @endcan
                </div>
            </div>

            <div class="d-flex gap-4 text-secondary small mb-4">
                <span>Posted {{ $jobPosting->date_posted->format('M j, Y') }}</span>
                <span>Deadline {{ $jobPosting->application_deadline->format('M j, Y') }}</span>
            </div>

            <hr class="jl-rule">

            <h2 class="h6 text-uppercase text-secondary">Description</h2>
            <ul class="mb-0">
                @forelse($jobPosting->descriptionLines() as $line)
                    <li>{{ $line }}</li>
                @empty
                    <li class="text-secondary">No description provided.</li>
                @endforelse
            </ul>

            <h2 class="h6 text-uppercase text-secondary mt-4">Qualifications</h2>
            <ul class="mb-0">
                @forelse($jobPosting->qualificationsLines() as $line)
                    <li>{{ $line }}</li>
                @empty
                    <li class="text-secondary">No qualifications listed.</li>
                @endforelse
            </ul>

            <h2 class="h6 text-uppercase text-secondary mt-4">Requirements</h2>
            <ul class="mb-0">
                @forelse($jobPosting->requirementsLines() as $line)
                    <li>{{ $line }}</li>
                @empty
                    <li class="text-secondary">No requirements listed.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection