@extends('layouts.app')

@section('title', 'Application Review')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <a href="{{ route('company.applications.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back
        </a>

        <div class="jl-card p-4 p-md-5 mt-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <div>
                    <h1 class="h4 mb-1">{{ $application->applicant->profile?->full_name ?? $application->applicant->student_id_number }}</h1>
                    <p class="text-secondary mb-0">Applied for {{ $application->jobPosting->title }} &middot; {{ $application->date_applied->format('M j, Y') }}</p>
                </div>
                <span class="badge rounded-pill jl-badge-{{ $application->status }} px-3 py-2">{{ ucfirst($application->status) }}</span>
            </div>

            <a href="{{ route('company.applications.resume', $application) }}" class="btn btn-outline-secondary mb-4" target="_blank">
                <i class="bi bi-file-earmark-pdf"></i> View Resume (PDF)
            </a>

            <hr class="jl-rule">

            <h2 class="h6 text-uppercase text-secondary mb-3">Update Status</h2>
            <form method="POST" action="{{ route('company.applications.status', $application) }}" class="d-flex gap-2 mb-5">
                @csrf
                @method('PATCH')
                <button type="submit" name="status" value="interview" class="btn btn-jl-gold">Invite to Interview</button>
                <button type="submit" name="status" value="rejected" class="btn btn-outline-secondary">Reject</button>
            </form>

            <h2 class="h6 text-uppercase text-secondary mb-3">Send a Message</h2>
            <form method="POST" action="{{ route('company.applications.message', $application) }}">
                @csrf
                <div class="mb-3">
                    <input type="text" name="title" value="{{ old('title') }}" class="form-control" placeholder="Subject" required>
                </div>
                <div class="mb-3">
                    <textarea name="message" rows="3" class="form-control" placeholder="Message" required>{{ old('message') }}</textarea>
                </div>
                <button type="submit" class="btn btn-jl-navy">Send Message</button>
            </form>
        </div>
    </div>
</div>
@endsection