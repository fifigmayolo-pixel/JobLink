@extends('layouts.app')

@section('title', 'Tracer Study Link')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <h1 class="h2 mb-1">Tracer Study Link</h1>
        <p class="text-secondary mb-4">Replacing the link deactivates the current one. Past links are recorded in the <a href="{{ route('coordinator.audit-log.index', ['action' => 'tracer_study_update']) }}">Audit Log</a>.</p>

        <div class="jl-card p-4 mb-4">
            <form method="POST" action="{{ route('coordinator.tracer-study-links.store') }}">
                @csrf
                <label class="form-label">New link</label>
                <input type="url" name="url" value="{{ old('url') }}" class="form-control mb-3" placeholder="https://forms.gle/...">
                <button type="submit" class="btn btn-jl-gold">Save Link</button>
            </form>
        </div>

        <hr class="jl-rule">

        <h2 class="h6 text-uppercase text-secondary mb-3">Current Link</h2>

        @if($currentLink)
            <div class="jl-card p-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <a href="{{ $currentLink->url }}" target="_blank" rel="noopener" class="text-truncate">{{ $currentLink->url }}</a>
                <span class="badge rounded-pill flex-shrink-0" style="background: rgba(15,42,92,0.08); color: var(--jl-navy);">Active</span>
            </div>
        @else
            <div class="jl-card p-5 text-center">
                <p class="mb-0 text-secondary">No tracer study link set yet.</p>
            </div>
        @endif
    </div>
</div>
@endsection