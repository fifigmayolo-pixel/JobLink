@extends('layouts.app')

@section('title', 'Saved Jobs')

@section('content')
<h1 class="h2 mb-1">Saved Jobs</h1>
<p class="text-secondary mb-4">Jobs you've bookmarked to review later.</p>
<hr class="jl-rule">

<div class="row g-3">
    @forelse($savedJobs as $saved)
        <div class="col-sm-6 col-lg-4">
            <div class="jl-card jl-card-hover h-100 p-4 d-flex flex-column">
                <h2 class="h6 mb-1">{{ $saved->jobPosting->title }}</h2>
                <p class="text-secondary small mb-3 flex-grow-1">{{ $saved->jobPosting->company->company_name }} &middot; {{ $saved->jobPosting->location }}</p>
                <div class="d-flex gap-2">
                    <a href="{{ route('jobs.show', $saved->jobPosting) }}" class="btn btn-jl-navy btn-sm flex-grow-1">View</a>
                    <form method="POST" action="{{ route('saved-jobs.destroy', $saved->jobPosting) }}" class="flex-grow-1">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-secondary btn-sm w-100">Remove</button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="jl-card p-5 text-center">
                <p class="mb-0 text-secondary">Nothing saved yet. Browse <a href="{{ route('jobs.index') }}">open positions</a> and bookmark ones you're interested in.</p>
            </div>
        </div>
    @endforelse
</div>

<div class="mt-4">
    {{ $savedJobs->links() }}
</div>
@endsection