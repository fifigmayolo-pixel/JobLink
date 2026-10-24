@extends('layouts.app')

@section('title', 'Saved Jobs')

@section('content')
<h1 class="h2 mb-1">Saved Jobs</h1>
<p class="text-secondary mb-4">Jobs you've bookmarked to review later.</p>
<hr class="jl-rule">

{{-- Flex-wrap, not row/col — see the comment in jobs/index.blade.php. --}}
<div class="d-flex flex-wrap gap-3">
    @forelse($savedJobs as $saved)
        @include('partials.job-card', ['job' => $saved->jobPosting, 'savedMode' => true])
    @empty
        <div class="jl-card p-5 text-center w-100">
            <p class="mb-0 text-secondary">Nothing saved yet. Browse <a href="{{ route('jobs.index') }}">open positions</a> and bookmark ones you're interested in.</p>
        </div>
    @endforelse
</div>

<div class="mt-4">
    {{ $savedJobs->links() }}
</div>
@endsection