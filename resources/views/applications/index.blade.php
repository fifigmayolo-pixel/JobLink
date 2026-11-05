@extends('layouts.app')

@section('title', 'My Applications')

@section('content')
<h1 class="h2 mb-1">My Applications</h1>
<p class="text-secondary mb-4">Track the status of everything you've applied to.</p>
<hr class="jl-rule">

<div class="row g-3">
    @forelse($applications as $application)
        <div class="col-sm-6 col-lg-4">
            <div class="jl-card jl-card-hover h-100 p-4 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                    <h2 class="h6 mb-0">{{ $application->jobPosting->title }}</h2>
                    <span class="badge rounded-pill jl-badge-{{ $application->status }} px-3 py-2">{{ ucfirst($application->status) }}</span>
                </div>
                <p class="text-secondary small mb-3 flex-grow-1">
                    {{ $application->jobPosting->company->company_name }} &middot; Applied {{ $application->date_applied->format('M j, Y') }}
                </p>
                <a href="{{ route('applications.show', $application) }}" class="btn btn-jl-navy btn-sm w-100">View Application</a>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="jl-card p-5 text-center">
                <p class="mb-0 text-secondary">You haven't applied to anything yet. Browse <a href="{{ route('jobs.index') }}">open positions</a> to get started.</p>
            </div>
        </div>
    @endforelse
</div>

<div class="mt-4">
    {{ $applications->links() }}
</div>
@endsection