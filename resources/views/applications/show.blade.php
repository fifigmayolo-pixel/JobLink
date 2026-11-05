@extends('layouts.app')

@section('title', 'Application Details')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <a href="{{ route('applications.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back
        </a>

        <div class="jl-card p-4 p-md-5 mt-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <div>
                    <h1 class="h4 mb-1">{{ $application->jobPosting->title }}</h1>
                    <p class="text-secondary mb-0">{{ $application->jobPosting->company->company_name }}</p>
                </div>
                <span class="badge rounded-pill jl-badge-{{ $application->status }} px-3 py-2">{{ ucfirst($application->status) }}</span>
            </div>

            <p class="text-secondary small mb-4">Applied {{ $application->date_applied->format('M j, Y') }}</p>

            <a href="{{ route('applications.resume', $application) }}" class="btn btn-outline-secondary" target="_blank">
                <i class="bi bi-file-earmark-pdf"></i> View Submitted Resume
            </a>

            @if($application->status === 'interview')
                <div class="alert alert-light jl-card mt-4" style="border-left: 4px solid var(--jl-gold) !important;">
                    You've been invited to interview. Check your notifications for details from the company.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection