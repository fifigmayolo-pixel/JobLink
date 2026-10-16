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

@forelse($notifications as $notification)
    <div class="jl-card p-4 mb-3 {{ $notification->read_at ? '' : 'border-start border-4' }}" style="{{ $notification->read_at ? '' : 'border-left-color: var(--jl-gold) !important;' }}">
        <div class="d-flex justify-content-between align-items-start gap-2">
            <div class="d-flex align-items-start gap-3">
                @php
                    // Application notifications get their logo via the application's posting;
                    // new-job notifications carry the posting directly. Approval decisions
                    // come from the Coordinator, so they show a status icon instead of a logo.
                    $senderImagePath = in_array($notification->type, ['job_posting_approved', 'job_posting_rejected'])
                        ? null
                        : ($notification->application?->jobPosting?->company?->logo
                            ?? $notification->jobPosting?->company?->logo);
                    $senderIcon = match ($notification->type) {
                        'announcement' => 'bi-megaphone',
                        'custom_company_message' => 'bi-chat-dots',
                        'tracer_study' => 'bi-clipboard2-check',
                        'bulk_conversion_complete' => 'bi-people',
                        'new_job_posting' => 'bi-briefcase-fill',
                        'job_posting_approved' => 'bi-check-circle-fill',
                        'job_posting_rejected' => 'bi-x-circle-fill',
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