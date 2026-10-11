@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="d-flex justify-content-between align-items-end mb-4">
    <h1 class="h2 mb-0">Notifications</h1>
    <form method="POST" action="{{ route('notifications.read-all') }}">
        @csrf
        @method('PATCH')
        <button type="submit" class="btn btn-outline-secondary btn-sm">Mark all as read</button>
    </form>
</div>
<hr class="jl-rule">

@if(auth()->user()->role === 'alumni')
    @php $currentTracerLink = \App\Models\TracerStudyLink::current(); @endphp
    @if($currentTracerLink)
        <div class="jl-card p-4 mb-3 border-start border-4" style="border-left-color: var(--jl-gold) !important;">
            <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                <div class="d-flex align-items-start gap-3">
                    <div class="d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: rgba(201,162,39,0.15); color: var(--jl-gold); border-radius: 0.75rem;">
                        <i class="bi bi-clipboard2-check" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h2 class="h6 mb-0">Tracer Study</h2>
                            <span class="badge rounded-pill" style="background: rgba(201,162,39,0.15); color: #8A6C16;">
                                <i class="bi bi-pin-angle-fill"></i> Pinned
                            </span>
                        </div>
                        <p class="mb-1">We're tracking where our graduates end up — please take a few minutes to fill out the tracer study.</p>
                        <span class="text-secondary small">If you've already completed this tracer study, please ignore this.</span>
                    </div>
                </div>
                <a href="{{ $currentTracerLink->url }}" target="_blank" rel="noopener" class="btn btn-jl-gold btn-sm flex-shrink-0">
                    Go to Tracer Study
                </a>
            </div>
        </div>
    @endif
@endif

@forelse($notifications as $notification)
    <div class="jl-card p-4 mb-3 {{ $notification->read_at ? '' : 'border-start border-4' }}" style="{{ $notification->read_at ? '' : 'border-left-color: var(--jl-gold) !important;' }}">
        <div class="d-flex justify-content-between align-items-start gap-2">
            <div class="d-flex align-items-start gap-3">
                @php
                    // Application notifications get their logo via the application's posting;
                    // new-job notifications carry the posting directly.
                    $senderImagePath = $notification->application?->jobPosting?->company?->logo
                        ?? $notification->jobPosting?->company?->logo;
                    $senderIcon = match ($notification->type) {
                        'announcement' => 'bi-megaphone',
                        'custom_company_message' => 'bi-chat-dots',
                        'tracer_study' => 'bi-clipboard2-check',
                        'bulk_conversion_complete' => 'bi-people',
                        'new_job_posting' => 'bi-briefcase-fill',
                        default => 'bi-building',
                    };
                    $linkedJob = $notification->jobPosting;
                @endphp
                @include('partials.profile-avatar', ['imagePath' => $senderImagePath, 'label' => null, 'size' => 48, 'icon' => $senderIcon])
                <div>
                    <h2 class="h6 mb-1">{{ $notification->title }}</h2>
                    <p class="mb-1">{{ $notification->message }}</p>
                    <span class="text-secondary small">{{ $notification->created_at->diffForHumans() }}</span>
                    @if($linkedJob && ! $linkedJob->trashed())
                        <div class="mt-2">
                            <a href="{{ route('jobs.show', $linkedJob) }}" class="btn btn-jl-navy btn-sm">View job</a>
                        </div>
                    @endif
                </div>
            </div>
            @unless($notification->read_at)
                <form method="POST" action="{{ route('notifications.read', $notification) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Mark read</button>
                </form>
            @endunless
        </div>
    </div>
@empty
    <div class="jl-card p-5 text-center">
        <p class="mb-0 text-secondary">No notifications yet.</p>
    </div>
@endforelse

{{ $notifications->links() }}
@endsection