@extends('layouts.app')

@section('title', 'Tracer Study Link')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <h1 class="h2 mb-1">Tracer Study Link</h1>
        <p class="text-secondary mb-4">Replacing the link deactivates the current one and keeps it in the history below.</p>

        <div class="jl-card p-4 mb-4">
            <form method="POST" action="{{ route('coordinator.tracer-study-links.store') }}" class="d-flex gap-2">
                @csrf
                <input type="url" name="url" value="{{ old('url') }}" class="form-control" placeholder="https://forms.gle/&hellip;" required>
                <button type="submit" class="btn btn-jl-gold text-nowrap">Save Link</button>
            </form>
        </div>

        <hr class="jl-rule">

        <h2 class="h6 text-uppercase text-secondary mb-3">History</h2>
        @foreach($links as $link)
            <div class="d-flex justify-content-between align-items-center py-2 {{ !$loop->last ? 'border-bottom' : '' }}" style="border-color: rgba(15,42,92,0.08) !important;">
                <a href="{{ $link->url }}" target="_blank" rel="noopener" class="text-truncate" style="max-width: 70%;">{{ $link->url }}</a>
                @if($link->is_active)
                    <span class="badge rounded-pill" style="background: rgba(201,162,39,0.15); color: #8A6C16;">Active</span>
                @else
                    <span class="text-secondary small">{{ $link->created_at->format('M j, Y') }}</span>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection