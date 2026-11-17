@extends('layouts.app')

@section('title', $cv['personal']['full_name'] ?? 'Candidate')

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-9 col-lg-10">
        <a href="{{ route('company.candidates.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to candidates
        </a>

        <div class="alert alert-light jl-card mt-3 mb-3 small" style="border-left: 4px solid var(--jl-gold) !important;" role="note">
            <i class="bi bi-shield-check me-1" aria-hidden="true"></i>
            <strong>Contact details are hidden.</strong>
            This candidate chose to be searchable, so you can read their CV. Their email, mobile number and web links are shared when they apply to one of your jobs.
            @if($application)
                <div class="mt-2">
                    They applied to <strong>{{ $application->jobPosting->title }}</strong>.
                    <a href="{{ route('company.applications.show', $application) }}">Open the application</a> to see the CV and contact details they sent.
                </div>
            @endif
        </div>

        <div class="jl-card p-4 p-md-5">
            @include('cv._document', ['cv' => $cv])
        </div>
    </div>
</div>
@endsection
